<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configure Bot &mdash; {{ config('app.name', 'wolfXcore') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=JetBrains+Mono:wght@400;700&display=swap">
    @php
        use Pterodactyl\Http\Controllers\Admin\SuperAdminController;
        $logoUrl = SuperAdminController::getSiteLogo();
    @endphp
    <style>{!! SuperAdminController::getThemeCssBlock() !!}</style>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: var(--wxn-bg, #030a03); color: var(--wxn-text, #e8ffe8); font-family: 'JetBrains Mono', monospace; min-height: 100vh; }
        a { color: var(--wxn-neon, #00ff00); text-decoration: none; }
        .topbar { display: flex; align-items: center; justify-content: space-between; padding: 12px 28px; background: var(--wxn-nav-bg, rgba(0,0,0,0.92)); border-bottom: 1px solid rgba(0,255,0,0.18); position: sticky; top: 0; z-index: 100; }
        .topbar-logo { display: flex; align-items: center; gap: 10px; }
        .topbar-logo img { width: 32px; height: 32px; border-radius: 4px; border: 1px solid rgba(0,255,0,0.3); }
        .topbar-logo span { font-family: 'Orbitron',monospace; font-size: 0.95rem; color: var(--wxn-neon,#00ff00); letter-spacing: 2px; }
        .topbar-nav { display: flex; align-items: center; gap: 20px; font-size: 0.78rem; }
        .topbar-nav a { color: rgba(255,255,255,0.55); transition: color .15s; }
        .topbar-nav a:hover, .topbar-nav a.active { color: var(--wxn-neon,#00ff00); }
        .wrap { max-width: 580px; margin: 0 auto; padding: 40px 20px 80px; }

        .bot-header { text-align: center; margin-bottom: 32px; }
        .bot-avatar { width: 80px; height: 80px; border-radius: 16px; object-fit: cover; border: 2px solid var(--wxn-neon,#00ff00); box-shadow: 0 0 24px rgba(0,255,0,0.3); margin-bottom: 14px; }
        .bot-avatar-ph { width: 80px; height: 80px; border-radius: 16px; background: rgba(0,255,0,0.06); border: 2px solid rgba(0,255,0,0.25); display: inline-flex; align-items: center; justify-content: center; font-size: 36px; margin-bottom: 14px; }
        .bot-title { font-family: 'Orbitron',monospace; font-size: 1.2rem; color: var(--wxn-neon,#00ff00); letter-spacing: 2px; margin-bottom: 6px; }
        .bot-desc  { font-size: 0.72rem; color: rgba(255,255,255,0.4); margin-bottom: 12px; }
        .server-badge { display: inline-block; background: rgba(0,255,0,0.07); border: 1px solid rgba(0,255,0,0.2); border-radius: 20px; padding: 5px 14px; font-size: 0.68rem; color: rgba(0,255,0,0.8); }
        .github-pill {
            display: inline-flex; align-items: center; gap: 7px; margin-top: 10px;
            font-size: 0.68rem; color: rgba(255,255,255,0.5);
            border: 1px solid rgba(255,255,255,0.13); border-radius: 20px; padding: 4px 13px;
            transition: all .2s; text-decoration: none;
        }
        .github-pill:hover { border-color: rgba(0,255,0,0.4); color: var(--wxn-neon,#00ff00); }
        .github-pill svg  { width: 13px; height: 13px; fill: currentColor; flex-shrink: 0; }

        .config-card { background: rgba(0,15,0,0.8); border: 2px solid rgba(0,255,0,0.14); border-radius: 14px; padding: 28px; }
        .config-card h3 { font-family: 'Orbitron',monospace; font-size: 0.7rem; letter-spacing: 3px; color: var(--wxn-neon,#00ff00); border-bottom: 1px solid rgba(0,255,0,0.1); padding-bottom: 10px; margin-bottom: 6px; }
        .config-card p { font-size: 0.7rem; color: rgba(255,255,255,0.35); margin-bottom: 24px; }

        .field { margin-bottom: 22px; }
        .field label { display: block; font-size: 0.72rem; color: rgba(255,255,255,0.7); font-weight: 700; margin-bottom: 4px; letter-spacing: 0.5px; }
        .field .field-desc { font-size: 0.65rem; color: rgba(255,255,255,0.35); margin: 0 0 6px 0; }
        .field-wrap { display: flex; gap: 0; }
        .field-wrap input { flex: 1; }
        .field-wrap .toggle-btn { background: rgba(0,255,0,0.07); border: 1px solid rgba(0,255,0,0.2); border-left: none; border-radius: 0 6px 6px 0; padding: 0 12px; cursor: pointer; color: rgba(0,255,0,0.7); font-size: 14px; }
        .field input { width: 100%; background: rgba(0,20,0,0.9); border: 1px solid rgba(0,255,0,0.2); border-radius: 6px; padding: 10px 14px; color: #fff; font-family: 'JetBrains Mono',monospace; font-size: 0.8rem; outline: none; transition: border-color .2s; }
        .field input:focus { border-color: var(--wxn-neon,#00ff00); }
        .field .req { color: #ff5555; margin-left: 4px; }

        .start-btn {
            width: 100%; margin-top: 10px; padding: 14px;
            background: linear-gradient(135deg, #00cc00, #009900);
            border: none; border-radius: 8px; color: #000;
            font-weight: 700; font-size: 0.85rem; cursor: pointer;
            letter-spacing: 1px; font-family: 'Orbitron',monospace;
            transition: transform .15s, opacity .2s;
        }
        .start-btn:hover { transform: translateY(-1px); }
        .start-btn:disabled { opacity: 0.5; transform: none; cursor: not-allowed; }

        .status-msg { display: none; margin-top: 14px; padding: 12px 16px; border-radius: 8px; font-size: 0.75rem; text-align: center; }
        .status-msg.ok  { background: rgba(0,255,0,0.08); border: 1px solid rgba(0,255,0,0.25); color: #00ff00; }
        .status-msg.err { background: rgba(255,60,60,0.08); border: 1px solid rgba(255,60,60,0.25); color: #ff7070; }

        .already-configured { margin-top: 20px; background: rgba(0,40,0,0.6); border: 1px solid rgba(0,255,0,0.25); border-radius: 10px; padding: 14px 18px; text-align: center; }
        .already-configured strong { color: var(--wxn-neon,#00ff00); }
        .already-configured small { font-size: 0.68rem; color: rgba(255,255,255,0.4); display: block; margin-top: 4px; }

        .console-link { margin-top: 22px; padding-top: 16px; border-top: 1px solid rgba(0,255,0,0.08); text-align: center; font-size: 0.72rem; color: rgba(255,255,255,0.3); }
        .console-link a { color: rgba(0,255,0,0.6); }

        @media (max-width: 540px) {
            .topbar { padding: 10px 14px; }
            .topbar-nav { gap: 12px; font-size: 0.72rem; }
            .topbar-nav a:not(.active):not(:last-child) { display: none; }
        }
    </style>
</head>
<body>

<div class="topbar">
    <div class="topbar-logo">
        @if($logoUrl)<img src="{{ $logoUrl }}" alt="Logo">@endif
        <span>wolfXcore</span>
    </div>
    <nav class="topbar-nav">
        <a href="/">Dashboard</a>
        <a href="/servers">Servers</a>
        <a href="/bots" class="active">Bots</a>
        <a href="/billing">Billing</a>
        <a href="/account">Account</a>
        <a href="#" onclick="event.preventDefault();document.getElementById('logout-form').submit()">Logout</a>
    </nav>
    <form id="logout-form" action="/auth/logout" method="POST" style="display:none">@csrf</form>
</div>

<div class="wrap">
    <div class="bot-header">
        @if($repo->image_url)
            <div><img src="{{ $repo->image_url }}" alt="{{ $repo->name }}" class="bot-avatar"></div>
        @else
            <div class="bot-avatar-ph">🤖</div>
        @endif
        <div class="bot-title">{{ $repo->name }}</div>
        @if($repo->description)
            <div class="bot-desc">{{ $repo->description }}</div>
        @endif
        @if($repo->git_address)
            @php
                $gitUrl = $repo->git_address;
                if (!str_starts_with($gitUrl, 'http')) $gitUrl = 'https://github.com/' . $gitUrl;
            @endphp
            <a href="{{ $gitUrl }}" target="_blank" class="github-pill">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0 0 24 12c0-6.63-5.37-12-12-12z"/>
                </svg>
                View Source on GitHub
            </a><br>
        @endif
        <span class="server-badge" style="margin-top:8px;display:inline-block;">⚡ Server: {{ substr($uuid, 0, 8) }}...</span>
    </div>

    <div class="config-card">
        <h3>⚙ CONFIGURE YOUR BOT</h3>
        <p>Fill in the fields below then hit <strong style="color:rgba(0,255,0,0.8);">Save &amp; Start Bot</strong> — your bot will be online within seconds.</p>

        @if(!empty($schema))
        <form id="configForm">
            @foreach($schema as $field)
            <div class="field">
                <label>
                    {{ $field['key'] }}
                    @if($field['required'])<span class="req">*</span>@endif
                </label>
                @if(!empty($field['description']) && $field['description'] !== $field['key'])
                    <p class="field-desc">{{ $field['description'] }}</p>
                @endif

                @php
                    $isSecret = in_array(strtolower($field['key']), ['session_id','session','token','secret','password','api_key','apikey'])
                                || str_contains(strtolower($field['key']), 'token')
                                || str_contains(strtolower($field['key']), 'secret')
                                || str_contains(strtolower($field['key']), 'session');
                @endphp

                @if($isSecret)
                <div class="field-wrap">
                    <input type="password"
                           id="f_{{ $field['key'] }}" name="{{ $field['key'] }}"
                           class="config-field"
                           style="border-radius:6px 0 0 6px;"
                           placeholder="{{ $field['default'] ?? 'Enter ' . $field['key'] }}"
                           value="{{ $saved[$field['key']] ?? '' }}"
                           {{ $field['required'] ? 'required' : '' }}>
                    <button type="button" class="toggle-btn" onclick="toggleVis('{{ $field['key'] }}')">👁</button>
                </div>
                @else
                <input type="text"
                       id="f_{{ $field['key'] }}" name="{{ $field['key'] }}"
                       class="config-field"
                       placeholder="{{ $field['default'] ?? 'Enter ' . $field['key'] }}"
                       value="{{ $saved[$field['key']] ?? ($field['default'] ?? '') }}"
                       {{ $field['required'] ? 'required' : '' }}>
                @endif
            </div>
            @endforeach
        </form>
        @else
        <p style="color:rgba(255,255,255,0.3);text-align:center;padding:16px 0;">
            No configuration fields required for this bot.
        </p>
        @endif

        <button id="startBtn" class="start-btn" onclick="saveConfig()">
            ▶ SAVE &amp; START BOT
        </button>
        <div id="statusMsg" class="status-msg"></div>

        @if(in_array($botConfig->status, ['configured','starting','running']))
        <div class="already-configured">
            <strong>✓ Bot {{ ucfirst($botConfig->status) }}</strong>
            <small><a href="/server/{{ $uuid }}">Open server console</a> to see live output</small>
        </div>
        @endif

        <div class="console-link">
            <a href="/server/{{ $uuid }}">⟩_ Open Server Console</a>
        </div>
    </div>
</div>

<script>
const CSRF = document.querySelector('meta[name=csrf-token]').content;

function toggleVis(key) {
    const f = document.getElementById('f_' + key);
    f.type = f.type === 'password' ? 'text' : 'password';
}

function saveConfig() {
    const btn    = document.getElementById('startBtn');
    const status = document.getElementById('statusMsg');
    const fields = document.querySelectorAll('.config-field');
    const configs = {};
    let valid = true;

    fields.forEach(f => {
        if (f.required && !f.value.trim()) { valid = false; f.style.borderColor = '#ff5555'; }
        else { f.style.borderColor = ''; configs[f.name] = f.value.trim(); }
    });

    if (!valid) {
        status.className = 'status-msg err'; status.style.display = '';
        status.textContent = 'Please fill in all required fields.';
        return;
    }

    btn.disabled = true;
    btn.textContent = '⏳ Saving...';

    fetch('/bots/configure/{{ $uuid }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify(configs),
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        if (res.status === 'success') {
            btn.textContent = '✓ Saved!';
            status.className = 'status-msg ok'; status.style.display = '';
            status.textContent = res.message || 'Bot configured and starting!';
            setTimeout(() => { window.location.href = res.redirect || '/server/{{ $uuid }}'; }, 1500);
        } else {
            btn.textContent = '▶ SAVE & START BOT';
            status.className = 'status-msg err'; status.style.display = '';
            status.textContent = res.error || 'Something went wrong.';
        }
    })
    .catch(e => {
        btn.disabled = false;
        btn.textContent = '▶ SAVE & START BOT';
        status.className = 'status-msg err'; status.style.display = '';
        status.textContent = 'Network error: ' + e.message;
    });
}
</script>
</body>
</html>
