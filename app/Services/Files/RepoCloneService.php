<?php

namespace Pterodactyl\Services\Files;

use Pterodactyl\Models\Server;
use Pterodactyl\Models\Setting;
use Illuminate\Support\Facades\Http;
use Pterodactyl\Repositories\Wings\DaemonFileRepository;
use Pterodactyl\Exceptions\DisplayException;
use Psr\Http\Message\RequestInterface;

class RepoCloneService
{
    public const SETTING_ENABLED   = 'settings::feature:repo_clone_enabled';
    public const SETTING_ALLOWLIST = 'settings::feature:repo_clone_allowlist';
    public const DEFAULT_ALLOWLIST = 'github.com,gitlab.com,bitbucket.org';
    public const MAX_ARCHIVE_BYTES = 250 * 1024 * 1024;

    public function __construct(private DaemonFileRepository $fileRepository) {}

    public static function isEnabled(): bool
    {
        return (Setting::where('key', self::SETTING_ENABLED)->value('value') ?? '1') === '1';
    }

    public static function getAllowlist(): array
    {
        $raw = Setting::where('key', self::SETTING_ALLOWLIST)->value('value') ?? self::DEFAULT_ALLOWLIST;
        return array_values(array_filter(array_map(
            fn ($h) => strtolower(trim($h)),
            explode(',', $raw)
        )));
    }

    /**
     * @throws DisplayException
     */
    public function handle(Server $server, string $repoUrl, ?string $branch, ?string $directory): array
    {
        if (!self::isEnabled()) {
            throw new DisplayException('Repository cloning has been disabled by the administrator.');
        }

        $parsed = $this->parseRepoUrl($repoUrl);
        $allowlist = self::getAllowlist();
        if (!$this->isHostSafe($parsed['host'], $allowlist)) {
            throw new DisplayException(sprintf(
                'The host "%s" is not in the allowed list (%s).',
                $parsed['host'],
                implode(', ', $allowlist)
            ));
        }

        if ($branch) {
            $candidates = $branch === 'master' ? ['master'] : [$branch, 'master'];
        } else {
            $candidates = ['main', 'master'];
        }

        $resolved = null;
        foreach ($candidates as $candidate) {
            $url = $this->buildArchiveUrl($parsed, $candidate);
            $finalUrl = $this->resolveFinalUrl($url, $allowlist);
            if ($finalUrl !== null) {
                $resolved = ['branch' => $candidate, 'url' => $finalUrl];
                break;
            }
        }
        if (!$resolved) {
            throw new DisplayException(
                $branch
                    ? sprintf('Branch "%s" was not found in that repository (or the repository is private).', $branch)
                    : 'Could not find a "main" or "master" branch — please specify a branch explicitly. The repository may also be private.'
            );
        }

        // Wings v1.12 requires a Content-Length header on pull responses, but GitHub/GitLab/Bitbucket
        // archive endpoints use chunked transfer encoding (no Content-Length). We therefore stage the
        // archive on the panel — nginx serves it statically with a proper Content-Length — and have
        // Wings pull from us. Same-host pulls also remove the need for nodes to reach external Git hosts.
        $root      = $directory ?: '/';
        $token     = bin2hex(random_bytes(16));
        $filename  = sprintf('__wxn_clone_%s.tar.gz', substr($token, 0, 12));
        $stageDir  = public_path('wxn-staged');
        $stagePath = $stageDir . '/' . $token . '.tar.gz';
        $stageUrl  = rtrim((string) config('app.url'), '/') . '/wxn-staged/' . $token . '.tar.gz';

        if (!is_dir($stageDir)) {
            @mkdir($stageDir, 0755, true);
        }

        try {
            $this->streamDownload($resolved['url'], $stagePath, $allowlist);
        } catch (\Throwable $e) {
            @unlink($stagePath);
            throw new DisplayException('Failed to download the repository archive: ' . $e->getMessage());
        }

        try {
            $this->fileRepository->setServer($server)->pull($stageUrl, $root, [
                'filename'   => $filename,
                'foreground' => true,
            ]);
        } catch (\Throwable $e) {
            @unlink($stagePath);
            throw new DisplayException('Failed to download the repository archive: ' . $e->getMessage());
        }

        @unlink($stagePath);

        try {
            $this->fileRepository->setServer($server)->decompressFile($root, $filename);
        } catch (\Throwable $e) {
            try { $this->fileRepository->setServer($server)->deleteFiles($root, [$filename]); } catch (\Throwable) {}
            throw new DisplayException('Failed to extract the repository archive: ' . $e->getMessage());
        }

        try {
            $this->fileRepository->setServer($server)->deleteFiles($root, [$filename]);
        } catch (\Throwable) {
            // Non-fatal
        }

        return [
            'host'      => $parsed['host'],
            'owner'     => $parsed['owner'],
            'repo'      => $parsed['repo'],
            'branch'    => $resolved['branch'],
            'directory' => $root,
        ];
    }

    /**
     * @throws DisplayException
     */
    protected function parseRepoUrl(string $url): array
    {
        $url = preg_replace('/\.git$/', '', trim($url));
        $parts = parse_url($url);
        if (!$parts || empty($parts['host']) || empty($parts['path'])) {
            throw new DisplayException('That URL could not be parsed. Use https://host/owner/repo format.');
        }
        if (!in_array(strtolower($parts['scheme'] ?? ''), ['https', 'http'], true)) {
            throw new DisplayException('Repository URL must start with https://.');
        }
        $segments = array_values(array_filter(explode('/', $parts['path'])));
        if (count($segments) < 2) {
            throw new DisplayException('Repository URL must include both owner and repo (e.g. github.com/owner/repo).');
        }
        return [
            'host'  => strtolower($parts['host']),
            'owner' => $segments[0],
            'repo'  => $segments[1],
        ];
    }

    /**
     * @throws DisplayException
     */
    protected function buildArchiveUrl(array $parsed, string $branch): string
    {
        $owner = rawurlencode($parsed['owner']);
        $repo  = rawurlencode($parsed['repo']);
        $b     = rawurlencode($branch);
        return match ($parsed['host']) {
            'github.com'    => "https://github.com/{$owner}/{$repo}/archive/refs/heads/{$b}.tar.gz",
            'gitlab.com'    => "https://gitlab.com/{$owner}/{$repo}/-/archive/{$b}/{$repo}-{$b}.tar.gz",
            'bitbucket.org' => "https://bitbucket.org/{$owner}/{$repo}/get/{$b}.tar.gz",
            default         => throw new DisplayException("Unsupported host: {$parsed['host']}."),
        };
    }

    /**
     * Build the redirect-following options used by every outbound HTTP call.
     * The on_redirect callback enforces the host/IP allowlist on every hop.
     */
    protected function safeRedirectOptions(array $allowlist): array
    {
        return [
            'max'             => 5,
            'strict'          => false,
            'referer'         => false,
            'protocols'       => ['https'],
            'track_redirects' => true,
            'on_redirect'     => function (RequestInterface $request, $response, $uri) use ($allowlist) {
                $host = strtolower($uri->getHost());
                if (!$this->isRedirectHostSafe($host, $allowlist)) {
                    throw new \RuntimeException("Refusing to follow redirect to disallowed host: {$host}");
                }
            },
        ];
    }

    /**
     * Stream-download a URL to disk with a hard size cap. SSRF safeguards are
     * applied via safeRedirectOptions().
     *
     * @throws \RuntimeException
     */
    protected function streamDownload(string $url, string $destination, array $allowlist): void
    {
        $cap  = self::MAX_ARCHIVE_BYTES;
        $sink = fopen($destination, 'wb');
        if (!$sink) {
            throw new \RuntimeException("Could not open staging file for writing.");
        }

        try {
            $resp = Http::timeout(120)
                ->withOptions([
                    'allow_redirects' => $this->safeRedirectOptions($allowlist),
                    'progress'        => function ($total, $downloaded) use ($cap) {
                        if (($total > 0 && $total > $cap) || $downloaded > $cap) {
                            throw new \RuntimeException(sprintf(
                                'Repository archive exceeded the %s MB size limit.',
                                intval($cap / 1024 / 1024)
                            ));
                        }
                    },
                    'sink' => $sink,
                ])
                ->get($url);
            if (!$resp->successful()) {
                throw new \RuntimeException("Source returned HTTP {$resp->status()}");
            }
        } finally {
            if (is_resource($sink)) fclose($sink);
        }

        $size = filesize($destination);
        if ($size === 0) {
            throw new \RuntimeException('Downloaded archive is empty.');
        }
        if ($size > $cap) {
            @unlink($destination);
            throw new \RuntimeException(sprintf(
                'Repository archive exceeded the %s MB size limit.',
                intval($cap / 1024 / 1024)
            ));
        }
    }

    /**
     * Strict check used on the user-supplied repo host: must be in the allowlist
     * AND resolve only to public IPs.
     */
    protected function isHostSafe(string $host, array $allowlist): bool
    {
        return in_array($host, $allowlist, true) && $this->resolvesToPublicIpOnly($host);
    }

    /**
     * Slightly looser check used on redirect targets: the host must either be
     * an allowlisted host OR a subdomain of one (e.g. github.com → codeload.github.com),
     * AND must still resolve only to public IPs. This keeps real-world download flows
     * working while preventing SSRF to internal infrastructure.
     */
    protected function isRedirectHostSafe(string $host, array $allowlist): bool
    {
        $matchesAllowlistFamily = false;
        foreach ($allowlist as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.' . $allowed)) {
                $matchesAllowlistFamily = true;
                break;
            }
        }
        return $matchesAllowlistFamily && $this->resolvesToPublicIpOnly($host);
    }

    protected function resolvesToPublicIpOnly(string $host): bool
    {
        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        if (!$records) return false;
        foreach ($records as $rec) {
            $ip = $rec['ip'] ?? $rec['ipv6'] ?? null;
            if (!$ip) return false;
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Resolve a download URL by following redirects (with SSRF safeguards) and
     * return the final URL — Wings does not follow 302s. Returns null on any failure.
     */
    protected function resolveFinalUrl(string $url, array $allowlist): ?string
    {
        $extractFinal = function ($resp, string $original): string {
            $chain = $resp->header('X-Guzzle-Redirect-History');
            if ($chain) {
                $urls = array_filter(array_map('trim', explode(',', $chain)));
                if (!empty($urls)) return end($urls);
            }
            return $original;
        };

        try {
            $resp = Http::timeout(15)
                ->withOptions(['allow_redirects' => $this->safeRedirectOptions($allowlist)])
                ->head($url);
            if ($resp->successful()) {
                return $extractFinal($resp, $url);
            }
            if (!in_array($resp->status(), [403, 405], true)) {
                return null;
            }
        } catch (\Throwable) {
            // fall through
        }

        // Fallback: ranged GET piped to /dev/null, hard-capped at 1 MB so we
        // never buffer a real archive in memory during preflight.
        $sink = @fopen('php://temp', 'w+');
        if (!$sink) return null;
        $cap = 1024 * 1024;
        try {
            $resp = Http::timeout(15)
                ->withOptions([
                    'allow_redirects' => $this->safeRedirectOptions($allowlist),
                    'sink'            => $sink,
                    'progress'        => function ($_, $downloaded) use ($cap) {
                        if ($downloaded > $cap) {
                            throw new \RuntimeException('preflight cap exceeded');
                        }
                    },
                ])
                ->withHeaders(['Range' => 'bytes=0-0'])
                ->get($url);
            if (!$resp->successful() && $resp->status() !== 206) return null;
            return $extractFinal($resp, $url);
        } catch (\Throwable) {
            return null;
        } finally {
            if (is_resource($sink)) fclose($sink);
        }
    }
}
