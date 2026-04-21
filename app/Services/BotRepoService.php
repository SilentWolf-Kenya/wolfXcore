<?php

namespace Pterodactyl\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BotRepoService
{
    /**
     * Fetch and parse app.json from a GitHub repo URL.
     * Accepts: https://github.com/owner/repo or https://github.com/owner/repo.git
     * Returns parsed array or throws exception on failure.
     */
    public function fetchAppJson(string $repoUrl): array
    {
        foreach (['main', 'master'] as $branch) {
            $raw      = $this->toRawUrl($repoUrl, 'app.json', $branch);
            $response = Http::timeout(10)->get($raw);
            if ($response->successful()) {
                $json = $response->json();
                if ($json) return $json;
            }
        }

        throw new \RuntimeException("Could not fetch app.json from {$repoUrl} (tried main and master branches)");
    }

    /**
     * Parse app.json into the fields we care about.
     */
    public function parseAppJson(array $json, string $repoUrl): array
    {
        $name       = $json['name']        ?? basename(rtrim($repoUrl, '/'));
        $desc       = $json['description'] ?? null;
        $image      = $json['image']       ?? $json['logo'] ?? null;
        $mainFile   = $json['main']        ?? $json['scripts']['start'] ?? 'index.js';
        $mainFile   = $this->extractMainFile($mainFile);
        $gitAddress = $this->toGitAddress($repoUrl);
        $envSchema  = $this->parseEnvSchema($json['env'] ?? $json['config_vars'] ?? []);

        return [
            'name'         => $name,
            'description'  => $desc,
            'image_url'    => $image,
            'git_address'  => $gitAddress,
            'main_file'    => $mainFile,
            'env_schema'   => json_encode($envSchema),
            'app_json_raw' => json_encode($json),
        ];
    }

    /**
     * Parse env/config_vars section into a flat schema array.
     * Each item: ['key' => ..., 'description' => ..., 'required' => ..., 'default' => ...]
     */
    private function parseEnvSchema(array $env): array
    {
        $schema = [];
        foreach ($env as $key => $def) {
            if (is_array($def)) {
                $schema[] = [
                    'key'         => $key,
                    'description' => $def['description'] ?? $def['desc'] ?? $key,
                    'required'    => (bool) ($def['required'] ?? false),
                    'default'     => $def['value'] ?? $def['default'] ?? '',
                ];
            } elseif (is_string($def)) {
                $schema[] = [
                    'key'         => $key,
                    'description' => $key,
                    'required'    => false,
                    'default'     => $def,
                ];
            }
        }
        return $schema;
    }

    /**
     * Convert a GitHub repo URL to a raw content URL.
     */
    private function toRawUrl(string $repoUrl, string $file, string $branch = 'main'): string
    {
        $url = rtrim(str_replace('.git', '', $repoUrl), '/');
        // https://github.com/owner/repo → https://raw.githubusercontent.com/owner/repo/main/file
        $url = str_replace('https://github.com/', 'https://raw.githubusercontent.com/', $url);
        return "{$url}/{$branch}/{$file}";
    }

    /**
     * Normalise repo URL to a git-clonable address.
     */
    private function toGitAddress(string $repoUrl): string
    {
        $url = rtrim($repoUrl, '/');
        if (!str_ends_with($url, '.git')) {
            $url .= '.git';
        }
        return $url;
    }

    /**
     * Extract just the filename from a npm start script like "node index.js".
     */
    private function extractMainFile(string $raw): string
    {
        // e.g. "node index.js" → "index.js" or just "index.js"
        $parts = explode(' ', trim($raw));
        foreach ($parts as $part) {
            if (str_ends_with($part, '.js') || str_ends_with($part, '.ts') || str_ends_with($part, '.mjs')) {
                return $part;
            }
        }
        return 'index.js';
    }
}
