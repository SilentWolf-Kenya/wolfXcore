<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\View\View;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Setting;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\WxnBotHealth;
use Pterodactyl\Models\WxnNotification;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\View\Factory as ViewFactory;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\PaystackService;

class SuperAdminController extends Controller
{
    const LOGO_KEY          = 'settings::app:logo';
    const DEFAULT_LOGO      = '/wolf-logo.jpg';
    const DISABLED_TABS_KEY = 'settings::theme:disabled_tabs';

    const ALL_SERVER_TABS = [
        'files', 'databases', 'schedules', 'users',
        'backups', 'network', 'startup', 'settings', 'activity',
    ];

    // All theme settings with their defaults
    const THEME_DEFAULTS = [
        'accent_color'     => '#00ff00',
        'custom_css'       => '',
        'page_bg'          => '#030a03',
        'sidebar_bg'       => '#020802',
        'sidebar_text'     => '#b0ffb0',
        'sidebar_active_bg'=> 'rgba(0,255,0,0.12)',
        'nav_bg'           => '#000800',
        'nav_text'         => '#ffffff',
        'card_bg'          => 'rgba(0,0,0,0.45)',
        'card_border'      => 'rgba(0,255,0,0.18)',
        'console_bg'       => '#020702',
        'console_cursor'   => '#00e676',
        'console_green'    => '#00e676',
        'console_red'      => '#ff5370',
        'console_yellow'   => '#facc15',
        'console_cyan'     => '#89ddff',
        'console_white'    => '#d0d0d0',
        'btn_start_bg'     => 'rgba(0,230,118,0.15)',
        'btn_start_text'   => '#00e676',
        'btn_start_border' => 'rgba(0,230,118,0.5)',
        'btn_stop_bg'      => 'rgba(180,20,20,0.2)',
        'btn_stop_text'    => '#ff6b6b',
        'btn_stop_border'  => 'rgba(220,38,38,0.45)',
        'btn_restart_bg'   => 'rgba(3,15,3,0.8)',
        'btn_restart_text' => 'rgba(255,255,255,0.75)',
        'btn_restart_border'=> 'rgba(0,230,118,0.2)',
        'btn_order'        => '["start","restart","stop"]',
        'btn_position'     => 'right',
        'font_body'        => 'JetBrains Mono',
        'font_heading'     => 'Orbitron',
        'font_size_base'   => '14',
        'grid_enable'      => '1',
        'scan_enable'      => '1',
    ];

    public function __construct(
        protected AlertsMessageBag $alert,
        protected ViewFactory $view,
    ) {}

    public function showAuth(Request $request): \Illuminate\Http\RedirectResponse|View
    {
        if (!$request->user() || !$request->user()->root_admin) {
            return redirect('/auth/login?redirect=' . urlencode($request->fullUrl()))
                ->with('flashes', [['type' => 'error', 'message' => 'Please log in as an administrator first.']]);
        }
        return $this->view->make('admin.super.auth');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        if (!$request->user() || !$request->user()->root_admin) {
            return redirect('/auth/login?redirect=' . urlencode(route('admin.super.auth')));
        }

        $key = config('wolfxcore.super_admin_key');
        if (empty($key)) {
            return redirect()->route('admin.super.auth')
                ->with('super_error', 'Super Admin key is not configured on this server.');
        }
        if (!hash_equals($key, (string) $request->input('key', ''))) {
            return redirect()->route('admin.super.auth')
                ->with('super_error', 'Invalid key. Access denied.');
        }
        $request->session()->put('wxn_super', true);
        $request->session()->put('wxn_super_at', now()->toISOString());
        return redirect()->route('admin.super.index');
    }

    public function index(): View
    {
        $admins        = User::where('root_admin', true)->orderBy('name_last')->get();
        $allUsers      = User::orderBy('name_last')->paginate(25);
        $logoUrl       = self::getSiteLogo();
        $disabledTabs  = self::getDisabledTabs();
        $allTabs       = self::ALL_SERVER_TABS;
        $theme         = self::getAllThemeSettings();
        $notifications = WxnNotification::orderByDesc('created_at')->get();
        $maintenanceOn       = Setting::where('key', 'settings::app:maintenance')->value('value') === '1';
        $announcementActive  = Setting::where('key', 'settings::app:announcement_active')->value('value') ?? '0';
        $announcementType    = Setting::where('key', 'settings::app:announcement_type')->value('value') ?? 'info';
        $announcementText    = Setting::where('key', 'settings::app:announcement_text')->value('value') ?? '';

        $serverConfig  = DB::table('wxn_server_config')->first();
        $nodes         = DB::table('nodes')->select('id', 'name', 'fqdn')->get();
        $nests         = DB::table('nests')->select('id', 'name')->get();
        $eggs          = DB::table('eggs')->select('id', 'name', 'nest_id')->orderBy('nest_id')->orderBy('name')->get();

        return $this->view->make('admin.super.index', compact(
            'admins', 'allUsers', 'logoUrl', 'disabledTabs', 'allTabs', 'theme', 'notifications', 'maintenanceOn',
            'serverConfig', 'nodes', 'nests', 'eggs',
            'announcementActive', 'announcementType', 'announcementText'
        ));
    }

    public function toggleMaintenance(Request $request): RedirectResponse
    {
        $current = Setting::where('key', 'settings::app:maintenance')->value('value') ?? '0';
        $new     = $current === '1' ? '0' : '1';
        Setting::updateOrCreate(['key' => 'settings::app:maintenance'], ['value' => $new]);

        $msg = $new === '1'
            ? 'Site is now in MAINTENANCE MODE. Users will see the maintenance page.'
            : 'Site is back ONLINE. Maintenance mode disabled.';
        $this->alert->success($msg)->flash();

        return redirect()->route('admin.super.index');
    }

    public function createNotification(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => 'required|string|max:120',
            'body'  => 'required|string|max:2000',
            'type'  => 'required|in:info,success,warning,danger',
        ]);

        WxnNotification::create([
            'title'     => $request->input('title'),
            'body'      => $request->input('body'),
            'type'      => $request->input('type'),
            'is_active' => true,
        ]);

        $this->alert->success('Notification created and is now live for all users.')->flash();
        return redirect()->route('admin.super.index');
    }

    public function deleteNotification(Request $request, int $id): RedirectResponse
    {
        WxnNotification::findOrFail($id)->delete();
        $this->alert->success('Notification deleted.')->flash();
        return redirect()->route('admin.super.index');
    }

    public function toggleNotification(Request $request, int $id): RedirectResponse
    {
        $n = WxnNotification::findOrFail($id);
        $n->is_active = !$n->is_active;
        $n->save();
        $this->alert->success('Notification ' . ($n->is_active ? 'activated' : 'deactivated') . '.')->flash();
        return redirect()->route('admin.super.index');
    }

    public function toggleAdmin(Request $request, User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            $this->alert->danger('You cannot change your own admin status from this panel.')->flash();
            return redirect()->route('admin.super.index');
        }
        $user->root_admin = !$user->root_admin;
        $user->save();
        $status = $user->root_admin ? 'granted admin access to' : 'revoked admin access from';
        $this->alert->success("Successfully {$status} {$user->username}.")->flash();
        return redirect()->route('admin.super.index');
    }

    public function updateBranding(Request $request): RedirectResponse
    {
        $request->validate(['logo_url' => 'required|string|max:500']);
        Setting::updateOrCreate(['key' => self::LOGO_KEY], ['value' => trim($request->input('logo_url'))]);
        $this->alert->success('Site branding updated.')->flash();
        return redirect()->route('admin.super.index');
    }

    public function updateTheme(Request $request): RedirectResponse
    {
        $request->validate([
            'accent_color' => 'required|regex:/^#[0-9a-fA-F]{6}$/',
            'custom_css'   => 'nullable|string|max:20000',
        ]);

        $stringFields = [
            'accent_color', 'custom_css', 'page_bg', 'sidebar_bg', 'sidebar_text',
            'sidebar_active_bg', 'nav_bg', 'nav_text', 'card_bg', 'card_border',
            'console_bg', 'console_cursor', 'console_green', 'console_red',
            'console_yellow', 'console_cyan', 'console_white',
            'btn_start_bg', 'btn_start_text', 'btn_start_border',
            'btn_stop_bg', 'btn_stop_text', 'btn_stop_border',
            'btn_restart_bg', 'btn_restart_text', 'btn_restart_border',
            'font_body', 'font_heading', 'font_size_base', 'grid_enable', 'scan_enable',
        ];

        foreach ($stringFields as $field) {
            $value = (string) ($request->input($field) ?? self::THEME_DEFAULTS[$field] ?? '');
            Setting::updateOrCreate(
                ['key' => "settings::theme:{$field}"],
                ['value' => $value]
            );
        }

        // Clamp font size to safe range
        $clampedSize = max(10, min(24, (int) ($request->input('font_size_base', 14))));
        Setting::updateOrCreate(
            ['key' => 'settings::theme:font_size_base'],
            ['value' => (string) $clampedSize]
        );

        $this->alert->success('Theme updated. Changes are live immediately.')->flash();
        return redirect()->route('admin.super.index');
    }

    public function updateTabs(Request $request): RedirectResponse
    {
        $disabled = array_intersect($request->input('disabled_tabs', []), self::ALL_SERVER_TABS);
        Setting::updateOrCreate(
            ['key' => self::DISABLED_TABS_KEY],
            ['value' => json_encode(array_values($disabled))]
        );

        // Save button order
        $validBtns = ['start', 'restart', 'stop'];
        $rawOrder  = $request->input('btn_order', '["start","restart","stop"]');
        $parsed    = json_decode($rawOrder, true) ?: $validBtns;
        $filtered  = array_values(array_intersect($parsed, $validBtns));
        // Ensure all 3 are present
        foreach ($validBtns as $b) {
            if (!in_array($b, $filtered)) $filtered[] = $b;
        }
        Setting::updateOrCreate(
            ['key' => 'settings::theme:btn_order'],
            ['value' => json_encode(array_slice($filtered, 0, 3))]
        );

        // Save button position
        $validPositions = ['right', 'left', 'top', 'bottom'];
        $btnPos = $request->input('btn_position', 'right');
        if (!in_array($btnPos, $validPositions)) $btnPos = 'right';
        Setting::updateOrCreate(
            ['key' => 'settings::theme:btn_position'],
            ['value' => $btnPos]
        );

        $count = count($disabled);
        $this->alert->success("Tab visibility and button order updated. {$count} tab(s) disabled.")->flash();
        return redirect()->route('admin.super.index');
    }

    public function savePaymentSettings(Request $request): RedirectResponse
    {
        $gateway = $request->input('gateway', 'paystack');

        if ($gateway === 'paystack') {
            $public  = trim($request->input('paystack_public', ''));
            $secret  = trim($request->input('paystack_secret', ''));
            $currency = $request->input('currency', 'KES');

            if (!empty($public)) {
                Setting::updateOrCreate(['key' => 'settings::payment:paystack_public'], ['value' => $public]);
            }

            // Only update secret if user entered a real key (not the masked display value)
            if (!empty($secret) && !str_starts_with($secret, '••••')) {
                Setting::updateOrCreate(['key' => 'settings::payment:paystack_secret'], ['value' => $secret]);
            }

            Setting::updateOrCreate(['key' => 'settings::payment:currency'], ['value' => $currency]);

            $this->alert->success('Paystack settings saved successfully.')->flash();
        }

        return redirect()->route('admin.super.index');
    }

    public function saveProvisioningSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'node_id'              => 'required|integer|min:1',
            'nest_id'              => 'required|integer|min:1',
            'egg_id'               => 'required|integer|min:1',
            'startup_override'     => 'nullable|string|max:500',
            'docker_image_override'=> 'nullable|string|max:255',
        ]);

        $existing = DB::table('wxn_server_config')->first();
        $data = [
            'node_id'               => (int) $request->input('node_id'),
            'nest_id'               => (int) $request->input('nest_id'),
            'egg_id'                => (int) $request->input('egg_id'),
            'startup_override'      => trim($request->input('startup_override', '')) ?: null,
            'docker_image_override' => trim($request->input('docker_image_override', '')) ?: null,
            'updated_at'            => now(),
        ];

        if ($existing) {
            DB::table('wxn_server_config')->where('id', $existing->id)->update($data);
        } else {
            $data['created_at'] = now();
            DB::table('wxn_server_config')->insert($data);
        }

        $this->alert->success('Server provisioning settings saved successfully.')->flash();
        return redirect()->route('admin.super.index');
    }

    public function saveAnnouncement(Request $request): RedirectResponse
    {
        $request->validate([
            'announcement_text' => 'nullable|string|max:1000',
            'announcement_type' => 'required|in:success,info,warning,danger',
        ]);

        $active = $request->has('announcement_active') ? '1' : '0';
        $type   = $request->input('announcement_type', 'info');
        $text   = trim($request->input('announcement_text', ''));

        Setting::updateOrCreate(['key' => 'settings::app:announcement_active'], ['value' => $active]);
        Setting::updateOrCreate(['key' => 'settings::app:announcement_type'],   ['value' => $type]);
        Setting::updateOrCreate(['key' => 'settings::app:announcement_text'],   ['value' => $text]);

        $this->alert->success('Sitewide announcement saved.')->flash();
        return redirect()->route('admin.super.index');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(['wxn_super', 'wxn_super_at']);
        return redirect()->route('admin.index')->with('success', 'Exited Super Admin mode.');
    }

    // ── Static helpers (used in layouts) ──────────────────────────────────

    public static function getAnnouncement(): ?array
    {
        $active = Setting::where('key', 'settings::app:announcement_active')->value('value') ?? '0';
        $text   = Setting::where('key', 'settings::app:announcement_text')->value('value') ?? '';
        $type   = Setting::where('key', 'settings::app:announcement_type')->value('value') ?? 'info';
        if ($active !== '1' || $text === '') return null;
        return ['text' => $text, 'type' => $type];
    }

    public static function getSiteLogo(): string
    {
        return Setting::where('key', self::LOGO_KEY)->value('value') ?? self::DEFAULT_LOGO;
    }

    public static function getDisabledTabs(): array
    {
        return json_decode(Setting::where('key', self::DISABLED_TABS_KEY)->value('value') ?? '[]', true) ?: [];
    }

    public static function getAllThemeSettings(): array
    {
        $keys = array_map(fn($k) => "settings::theme:{$k}", array_keys(self::THEME_DEFAULTS));
        $rows = Setting::whereIn('key', $keys)->pluck('value', 'key');
        $result = [];
        foreach (self::THEME_DEFAULTS as $field => $default) {
            $result[$field] = $rows["settings::theme:{$field}"] ?? $default;
        }
        return $result;
    }

    public static function getThemeCssBlock(): string
    {
        $t = self::getAllThemeSettings();
        $fontBody    = addslashes($t['font_body']);
        $fontHeading = addslashes($t['font_heading']);
        return ":root {
    --wxn-bg:               {$t['page_bg']};
    --wxn-neon:             {$t['accent_color']};
    --wxn-neon-dim:         {$t['accent_color']}30;
    --wxn-neon-faint:       {$t['accent_color']}1a;
    --wxn-sidebar-bg:       {$t['sidebar_bg']};
    --wxn-sidebar-text:     {$t['sidebar_text']};
    --wxn-sidebar-active:   {$t['sidebar_active_bg']};
    --wxn-nav-bg:           {$t['nav_bg']};
    --wxn-nav-text:         {$t['nav_text']};
    --wxn-card-bg:          {$t['card_bg']};
    --wxn-card-border:      {$t['card_border']};
    --wxn-border:           {$t['card_border']};
    --wxn-console-bg:       {$t['console_bg']};
    --wxn-console-cursor:   {$t['console_cursor']};
    --wxn-console-green:    {$t['console_green']};
    --wxn-console-red:      {$t['console_red']};
    --wxn-console-yellow:   {$t['console_yellow']};
    --wxn-console-cyan:     {$t['console_cyan']};
    --wxn-console-white:    {$t['console_white']};
    --wxn-btn-start-bg:     {$t['btn_start_bg']};
    --wxn-btn-start-text:   {$t['btn_start_text']};
    --wxn-btn-start-border: {$t['btn_start_border']};
    --wxn-btn-stop-bg:      {$t['btn_stop_bg']};
    --wxn-btn-stop-text:    {$t['btn_stop_text']};
    --wxn-btn-stop-border:  {$t['btn_stop_border']};
    --wxn-btn-restart-bg:   {$t['btn_restart_bg']};
    --wxn-btn-restart-text: {$t['btn_restart_text']};
    --wxn-btn-restart-border:{$t['btn_restart_border']};
    --wxn-font:             '{$fontBody}', 'JetBrains Mono', monospace;
    --wxn-font-display:     '{$fontHeading}', 'Orbitron', sans-serif;
    --wxn-font-size-base:   {$t['font_size_base']}px;
    --wxn-input-bg:         {$t['accent_color']}08;
    --wxn-text:             {$t['nav_text']};
    --wxn-grid-enable:      {$t['grid_enable']};
    --wxn-scan-enable:      {$t['scan_enable']};
}
body, .content-wrapper, .main-header, .main-sidebar, .sidebar-menu,
.box, .nav, .navbar, p, span, label, td, th, input, select, textarea, button {
    font-size: var(--wxn-font-size-base) !important;
}";
    }

    public static function getThemeJson(): array
    {
        $t = self::getAllThemeSettings();
        return [
            'accentColor'   => $t['accent_color'],
            'customCss'     => $t['custom_css'],
            'disabledTabs'  => self::getDisabledTabs(),
            'consoleBg'     => $t['console_bg'],
            'consoleCursor' => $t['console_cursor'],
            'consoleGreen'  => $t['console_green'],
            'consoleRed'    => $t['console_red'],
            'consoleYellow' => $t['console_yellow'],
            'consoleCyan'   => $t['console_cyan'],
            'consoleWhite'  => $t['console_white'],
            'btnStartBg'    => $t['btn_start_bg'],
            'btnStartText'  => $t['btn_start_text'],
            'btnStopBg'     => $t['btn_stop_bg'],
            'btnStopText'   => $t['btn_stop_text'],
            'btnRestartBg'  => $t['btn_restart_bg'],
            'btnRestartText'=> $t['btn_restart_text'],
            'btnOrder'      => json_decode($t['btn_order'] ?? '["start","restart","stop"]', true) ?: ['start','restart','stop'],
            'btnPosition'   => $t['btn_position'] ?? 'right',
            'gridEnable'      => $t['grid_enable'],
            'scanEnable'      => $t['scan_enable'],
            'christmasTheme'  => Setting::where('key', 'settings::christmas:mode')->value('value') ?? 'auto',
        ];
    }

    public function saveChristmasSettings(Request $request): RedirectResponse
    {
        $mode = $request->input('christmas_mode', 'auto');
        if (!in_array($mode, ['on', 'off', 'auto'])) $mode = 'auto';
        Setting::updateOrCreate(['key' => 'settings::christmas:mode'], ['value' => $mode]);
        return redirect()->route('admin.super.index')->with('success', 'Christmas theme settings saved!');
    }

    public function saveRepoCloneSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'repo_clone_allowlist' => 'nullable|string|max:500',
        ]);

        $enabled = $request->has('repo_clone_enabled') ? '1' : '0';
        $rawList = trim($request->input('repo_clone_allowlist', \Pterodactyl\Services\Files\RepoCloneService::DEFAULT_ALLOWLIST));

        // Sanitize: lowercase hostnames, strip schemes, strip paths
        $hosts = array_filter(array_map(function ($h) {
            $h = strtolower(trim($h));
            $h = preg_replace('#^https?://#', '', $h);
            $h = preg_replace('#/.*$#', '', $h);
            return $h;
        }, explode(',', $rawList)));
        // Refuse to silently lock everyone out — fall back to the default hosts
        // when the admin clears the field.
        if (empty($hosts)) {
            $hosts = explode(',', \Pterodactyl\Services\Files\RepoCloneService::DEFAULT_ALLOWLIST);
        }
        $clean = implode(', ', array_unique($hosts));

        Setting::updateOrCreate(['key' => \Pterodactyl\Services\Files\RepoCloneService::SETTING_ENABLED], ['value' => $enabled]);
        Setting::updateOrCreate(['key' => \Pterodactyl\Services\Files\RepoCloneService::SETTING_ALLOWLIST], ['value' => $clean]);

        $this->alert->success('Repository clone settings saved.')->flash();
        return redirect()->route('admin.super.index');
    }

    public static function getRepoCloneConfig(): array
    {
        return [
            'enabled'   => \Pterodactyl\Services\Files\RepoCloneService::isEnabled(),
            'allowlist' => \Pterodactyl\Services\Files\RepoCloneService::getAllowlist(),
        ];
    }

    /**
     * Bot Health dashboard — shows VPS capacity vs commitment, top RAM consumers,
     * paused bots, recent crashes, and lets the admin reset the circuit breaker.
     */
    public function botHealth(): View
    {
        $node = DB::table('nodes')->orderBy('id')->first();
        $nodeMem = (int) ($node->memory ?? 0);
        $overallocate = (int) ($node->memory_overallocate ?? 0);
        $effectiveCap = $nodeMem + ((int) round($nodeMem * $overallocate / 100));

        $committed = (int) DB::table('servers')->sum('memory');

        $servers = DB::table('servers')
            ->orderByDesc('memory')
            ->limit(15)
            ->get(['id','uuid','name','memory','swap','owner_id','status']);

        $health = WxnBotHealth::orderByDesc('last_crash_at')->limit(50)->get()->keyBy('server_id');
        $paused = WxnBotHealth::whereNotNull('circuit_paused_until')
            ->where('circuit_paused_until', '>', now())
            ->orderBy('circuit_paused_until')
            ->get();

        // True 24h crash counts from the append-only event log, keyed by server_id.
        $serverIds = $servers->pluck('id')->merge($paused->pluck('server_id'))->unique()->values();
        $crashes24hByServer = DB::table('wxn_bot_crashes')
            ->select('server_id', DB::raw('COUNT(*) as n'))
            ->where('occurred_at', '>=', now()->subHours(24))
            ->whereIn('event', ['server:power.crashed', 'server:power.oom_killed', 'server:installer.crashed'])
            ->whereIn('server_id', $serverIds)
            ->groupBy('server_id')
            ->pluck('n', 'server_id');

        // Live RSS for top-15 servers AND paused bots — bounded N, polled with per-call
        // exception handling so a flaky Wings daemon shows "—" instead of hanging the page.
        $liveRss = [];
        $repo = app(\Pterodactyl\Repositories\Wings\DaemonServerRepository::class);
        $rssTargets = $servers->pluck('id')->merge($paused->pluck('server_id'))->unique();
        foreach ($rssTargets as $sid) {
            $srv = \Pterodactyl\Models\Server::find($sid);
            if (!$srv) continue;
            try {
                $details = $repo->setServer($srv)->getDetails();
                $bytes = (int) ($details['utilization']['memory_bytes'] ?? 0);
                $liveRss[$sid] = $bytes > 0 ? round($bytes / 1024 / 1024) : null;
            } catch (\Throwable $e) {
                $liveRss[$sid] = null;
            }
        }

        return $this->view->make('admin.super.bot_health', compact(
            'node','nodeMem','overallocate','effectiveCap','committed','servers',
            'health','paused','liveRss','crashes24hByServer'
        ));
    }

    public function resetBotHealth(Request $request, int $serverId): RedirectResponse
    {
        WxnBotHealth::clearForServer($serverId);
        $this->alert->success("Circuit breaker cleared for server #{$serverId}.")->flash();
        return redirect()->route('admin.super.bot-health');
    }

    /**
     * Paystack live transaction records — fetched directly from Paystack API
     * and cross-referenced against the local wxn_payments table.
     */
    public function paystackTransactions(Request $request): View
    {
        $paystack = app(PaystackService::class);
        $page     = max(1, (int) $request->query('page', 1));
        $status   = $request->query('status', '');
        $perPage  = 50;

        $transactions = [];
        $meta         = [];
        $error        = null;

        if (!$paystack->isConfigured()) {
            $error = 'Paystack secret key is not configured. Add it in Payment Settings.';
        } else {
            try {
                $result       = $paystack->listTransactions($perPage, $page, $status);
                $transactions = $result['data']  ?? [];
                $meta         = $result['meta']  ?? [];
            } catch (\Exception $e) {
                $error = 'Could not fetch from Paystack: ' . $e->getMessage();
            }
        }

        // Cross-reference: collect references that exist in local DB.
        $refs = collect($transactions)->pluck('reference')->filter()->values()->all();
        $localRefs = DB::table('wxn_payments')
            ->whereIn('reference', $refs)
            ->pluck('status', 'reference');

        return $this->view->make('admin.super.paystack_transactions', compact(
            'transactions', 'meta', 'error', 'page', 'perPage', 'status', 'localRefs'
        ));
    }

    // Keep old accessor for backwards compat with layouts that call it directly
    public static function getAccentColor(): string
    {
        return Setting::where('key', 'settings::theme:accent_color')->value('value') ?? '#00ff00';
    }

    public static function getCustomCss(): string
    {
        return (string) (Setting::where('key', 'settings::theme:custom_css')->value('value') ?? '');
    }
}
