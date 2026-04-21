<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bot Marketplace &mdash; {{ config('app.name', 'wolfXcore') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=JetBrains+Mono:wght@400;700&display=swap">
    @php
        use wolfXcore\Http\Controllers\Admin\SuperAdminController;
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

        .wrap { max-width: 1160px; margin: 0 auto; padding: 36px 20px 100px; }

        .page-title { font-family: 'Orbitron',monospace; font-size: 1.4rem; color: var(--wxn-neon,#00ff00); text-shadow: 0 0 14px rgba(0,255,0,0.5); letter-spacing: 3px; text-align: center; margin-bottom: 6px; }
        .page-sub   { text-align: center; color: rgba(255,255,255,0.4); font-size: 0.75rem; margin-bottom: 36px; }

        /* ── Bot Grid ── */
        .bot-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px,1fr)); gap: 16px; margin-bottom: 40px; }
        .bot-card {
            background: rgba(0,20,0,0.7); border: 2px solid rgba(0,255,0,0.12);
            border-radius: 12px; padding: 18px; cursor: pointer; position: relative;
            transition: border-color .2s, box-shadow .2s, transform .15s;
        }
        .bot-card:hover  { border-color: rgba(0,255,0,0.45); box-shadow: 0 0 16px rgba(0,255,0,0.1); transform: translateY(-2px); }
        .bot-card.active { border-color: var(--wxn-neon,#00ff00) !important; box-shadow: 0 0 22px rgba(0,255,0,0.22) !important; }
        .bot-avatar { width: 48px; height: 48px; border-radius: 10px; object-fit: cover; border: 2px solid rgba(0,255,0,0.35); }
        .bot-avatar-ph { width: 48px; height: 48px; border-radius: 10px; background: rgba(0,255,0,0.05); border: 2px solid rgba(0,255,0,0.18); display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .bot-name  { font-weight: 700; color: var(--wxn-neon,#00ff00); font-size: 0.88rem; }
        .bot-slots { font-size: 0.65rem; color: rgba(255,255,255,0.32); margin-top: 2px; }
        .bot-desc  { font-size: 0.7rem; color: rgba(255,255,255,0.42); margin-top: 8px; line-height: 1.55; }
        .sel-dot { position: absolute; top: 10px; right: 10px; width: 18px; height: 18px; background: var(--wxn-neon,#00ff00); border-radius: 50%; display: none; align-items: center; justify-content: center; }
        .sel-dot svg { width: 9px; height: 9px; fill: none; stroke: #000; stroke-width: 2.5; stroke-linecap: round; }

        /* ── Section Label ── */
        .sec-label { font-family: 'Orbitron',monospace; font-size: 0.62rem; letter-spacing: 3px; text-transform: uppercase; color: var(--wxn-neon,#00ff00); border-bottom: 1px solid rgba(0,255,0,0.13); padding-bottom: 8px; margin-bottom: 20px; }

        /* ── Detail Panel (Heroku-like) ── */
        #detailPanel {
            display: none;
            background: rgba(0,15,0,0.85); border: 1px solid rgba(0,255,0,0.22);
            border-radius: 14px; padding: 28px 28px 32px; margin-bottom: 32px;
            animation: fadeIn .25s ease;
        }
        @keyframes fadeIn { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:none; } }

        .detail-header { display: flex; align-items: center; gap: 16px; margin-bottom: 22px; }
        .detail-avatar { width: 60px; height: 60px; border-radius: 12px; object-fit: cover; border: 2px solid rgba(0,255,0,0.4); }
        .detail-avatar-ph { width: 60px; height: 60px; border-radius: 12px; background: rgba(0,255,0,0.05); border: 2px solid rgba(0,255,0,0.2); display: flex; align-items: center; justify-content: center; font-size: 26px; }
        .detail-name { font-family: 'Orbitron',monospace; font-size: 1.05rem; color: var(--wxn-neon,#00ff00); }
        .detail-desc { font-size: 0.72rem; color: rgba(255,255,255,0.45); margin-top: 3px; }
        .github-link {
            display: inline-flex; align-items: center; gap: 7px; margin-top: 7px;
            font-size: 0.7rem; color: rgba(255,255,255,0.55);
            border: 1px solid rgba(255,255,255,0.12); border-radius: 6px;
            padding: 4px 10px; transition: all .2s;
        }
        .github-link:hover { border-color: rgba(0,255,0,0.4); color: var(--wxn-neon,#00ff00); }
        .github-link svg { width: 14px; height: 14px; fill: currentColor; flex-shrink: 0; }

        /* ── Config Variables (like Heroku Config Vars) ── */
        .config-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        @media(max-width:640px){ .config-grid { grid-template-columns: 1fr; } }

        .cfg-field label { display: block; font-size: 0.65rem; color: rgba(0,255,0,0.7); letter-spacing: 1px; text-transform: uppercase; margin-bottom: 5px; }
        .cfg-field input, .cfg-field textarea {
            width: 100%; background: rgba(0,20,0,0.9); border: 1px solid rgba(0,255,0,0.18);
            border-radius: 6px; padding: 9px 12px; color: #e8ffe8;
            font-family: 'JetBrains Mono',monospace; font-size: 0.78rem; outline: none;
            transition: border-color .15s;
        }
        .cfg-field input:focus, .cfg-field textarea:focus { border-color: var(--wxn-neon,#00ff00); }
        .cfg-field .cfg-hint { font-size: 0.62rem; color: rgba(255,255,255,0.3); margin-top: 4px; }
        .cfg-req { color: rgba(255,80,80,0.8); margin-left: 2px; }

        .no-config-note { font-size: 0.72rem; color: rgba(255,255,255,0.3); padding: 10px 0; font-style: italic; }

        /* ── Plan Grid ── */
        .plan-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px,1fr)); gap: 12px; margin-bottom: 28px; }
        .plan-card {
            background: rgba(0,20,0,0.7); border: 2px solid rgba(0,255,0,0.1);
            border-radius: 10px; padding: 16px; cursor: pointer;
            transition: border-color .2s, box-shadow .2s;
        }
        .plan-card:hover   { border-color: rgba(0,255,0,0.38); }
        .plan-card.active  { border-color: var(--wxn-neon,#00ff00) !important; box-shadow: 0 0 14px rgba(0,255,0,0.18) !important; }
        .plan-name  { font-size: 0.68rem; color: var(--wxn-neon,#00ff00); font-weight: 700; letter-spacing: 1px; margin-bottom: 6px; }
        .plan-price { font-size: 1.25rem; font-weight: 700; color: #fff; margin-bottom: 8px; }
        .plan-specs { font-size: 0.62rem; color: rgba(255,255,255,0.38); line-height: 1.75; }

        /* ── Payment ── */
        .pay-box { background: rgba(0,15,0,0.8); border: 1px solid rgba(0,255,0,0.14); border-radius: 10px; padding: 22px; max-width: 500px; }
        .method-btns { display: flex; gap: 9px; flex-wrap: wrap; margin-bottom: 16px; }
        .method-btn {
            flex: 1; min-width: 85px; padding: 10px 8px;
            background: rgba(0,20,0,0.8); border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px; color: rgba(255,255,255,0.48); font-size: 0.7rem;
            cursor: pointer; text-align: center; font-family: 'JetBrains Mono',monospace;
            transition: all .2s;
        }
        .method-btn:hover  { border-color: rgba(0,255,0,0.38); color: rgba(255,255,255,0.78); }
        .method-btn.active { border-color: var(--wxn-neon,#00ff00) !important; color: var(--wxn-neon,#00ff00) !important; background: rgba(0,255,0,0.05); }
        .input-wrap { margin-bottom: 14px; }
        .input-wrap label { display: block; font-size: 0.68rem; color: rgba(255,255,255,0.48); margin-bottom: 5px; }
        .input-wrap input { width: 100%; background: rgba(0,20,0,0.9); border: 1px solid rgba(0,255,0,0.18); border-radius: 6px; padding: 10px 14px; color: #fff; font-family: 'JetBrains Mono',monospace; font-size: 0.8rem; outline: none; }
        .input-wrap input:focus { border-color: var(--wxn-neon,#00ff00); }
        .wallet-info { background: rgba(0,255,0,0.04); border: 1px solid rgba(0,255,0,0.18); border-radius: 8px; padding: 9px 13px; margin-bottom: 14px; font-size: 0.7rem; color: rgba(0,255,0,0.85); }

        .deploy-btn {
            width: 100%; padding: 15px; margin-top: 4px;
            background: linear-gradient(135deg, #00cc00, #009900);
            border: none; border-radius: 8px; color: #000;
            font-weight: 700; font-size: 0.88rem; cursor: pointer;
            letter-spacing: 1px; font-family: 'Orbitron',monospace;
            transition: opacity .2s, transform .15s;
        }
        .deploy-btn:disabled { opacity: 0.35; cursor: not-allowed; transform: none !important; }
        .deploy-btn:not(:disabled):hover { transform: translateY(-1px); opacity: 0.92; }

        .err-msg { color: #ff5555; font-size: 0.72rem; margin-top: 10px; display: none; }

        /* ── Overlay ── */
        .overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.88); z-index: 9999; align-items: center; justify-content: center; flex-direction: column; gap: 18px; }
        .overlay.show { display: flex; }
        .spinner { width: 46px; height: 46px; border: 3px solid rgba(0,255,0,0.12); border-top-color: var(--wxn-neon,#00ff00); border-radius: 50%; animation: spin .8s linear infinite; }
        .overlay-msg { color: var(--wxn-neon,#00ff00); font-size: 0.78rem; letter-spacing: 1px; text-align:center; max-width: 300px; }
        @keyframes spin { to { transform: rotate(360deg); } }

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
    <h1 class="page-title">&#9679; BOT MARKETPLACE</h1>
    <p class="page-sub">Select a bot, configure your variables, choose a plan — deploy in seconds.</p>

    {{-- Step 1: Bot Grid --}}
    <div class="sec-label">&#9312; SELECT A BOT</div>
    <div class="bot-grid">
        @forelse($repos as $repo)
        <div class="bot-card" id="bot-card-{{ $repo->id }}" onclick="selectBot({{ $repo->id }})">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
                @if($repo->image_url)
                    <img src="{{ $repo->image_url }}" alt="" class="bot-avatar">
                @else
                    <div class="bot-avatar-ph">🤖</div>
                @endif
                <div>
                    <div class="bot-name">{{ $repo->name }}</div>
                    <div class="bot-slots">
                        @if($repo->available_count > 0)
                            {{ $repo->available_count }} slot{{ $repo->available_count != 1 ? 's' : '' }} ready
                        @else
                            On-demand
                        @endif
                    </div>
                </div>
            </div>
            @if($repo->description)
                <div class="bot-desc">{{ Str::limit($repo->description, 80) }}</div>
            @endif
            <div class="sel-dot" id="dot-{{ $repo->id }}">
                <svg viewBox="0 0 12 10"><polyline points="1,5 4,9 11,1"/></svg>
            </div>
        </div>
        @empty
        <div style="grid-column:1/-1;text-align:center;padding:56px;color:rgba(255,255,255,0.22);">
            No bots available yet — check back soon.
        </div>
        @endforelse
    </div>

    {{-- Step 2: Bot Detail + Config (shown when bot selected) --}}
    <div id="detailPanel">
        <div class="detail-header">
            <div id="detailAvatarWrap"></div>
            <div>
                <div class="detail-name" id="detailName"></div>
                <div class="detail-desc" id="detailDesc"></div>
                <a id="githubLink" href="#" target="_blank" class="github-link" style="display:none;">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0 0 24 12c0-6.63-5.37-12-12-12z"/>
                    </svg>
                    View Source
                </a>
            </div>
        </div>

        {{-- Config Variables --}}
        <div class="sec-label" style="margin-bottom:14px;">&#9313; CONFIGURE VARIABLES</div>
        <div id="configFields" class="config-grid"></div>
        <div id="noConfigNote" class="no-config-note" style="display:none;">No variables required for this bot.</div>

        {{-- Step 3: Plan Selection --}}
        <div class="sec-label" style="margin-top:30px;margin-bottom:14px;">&#9314; CHOOSE A PLAN</div>
        <div class="plan-grid">
            @foreach($plans as $plan)
            <div class="plan-card" id="plan-{{ $plan->id }}" onclick="selectPlan({{ $plan->id }},{{ $plan->price }},'{{ addslashes($plan->name) }}')">
                <div class="plan-name">{{ $plan->name }}</div>
                <div class="plan-price">{{ $currency }} {{ number_format($plan->price) }}</div>
                <div class="plan-specs">
                    {{ $plan->memory == 0 ? 'Unlimited' : $plan->memory.'MB' }} RAM<br>
                    {{ $plan->disk   == 0 ? 'Unlimited' : $plan->disk.'MB'   }} Disk<br>
                    {{ $plan->cpu    == 0 ? 'Unlimited' : $plan->cpu.'%'     }} CPU
                </div>
            </div>
            @endforeach
        </div>

        {{-- Step 4: Payment --}}
        <div class="sec-label" style="margin-bottom:14px;">&#9315; PAYMENT &amp; DEPLOY</div>
        <div class="pay-box">
            @if($isSuperAdmin)
            <div class="wallet-info" style="background:rgba(0,255,0,0.07);border-color:rgba(0,255,0,0.35);">
                ⚡ Super Admin — deployments are <strong>free</strong>
            </div>
            <button id="deployBtn" class="deploy-btn" onclick="initiatePurchase()" disabled>
                ⚡ DEPLOY FREE
            </button>
            @else
            <div class="wallet-info">
                🪙 Wallet Balance: <strong>{{ $currency }} {{ number_format($walletBalance, 2) }}</strong>
                @if($walletBalance <= 0)
                    &nbsp;— <a href="/billing" style="color:rgba(0,255,0,0.6);font-size:0.68rem;">Top up</a>
                @endif
            </div>

            <div class="method-btns">
                <button class="method-btn" data-method="card"   onclick="setMethod('card')">💳 Card</button>
                <button class="method-btn" data-method="mpesa"  onclick="setMethod('mpesa')">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/15/M-PESA_LOGO-01.svg/60px-M-PESA_LOGO-01.svg.png" height="12" style="vertical-align:middle;margin-right:4px;">M-Pesa
                </button>
                <button class="method-btn" data-method="airtel" onclick="setMethod('airtel')">📱 Airtel</button>
                <button class="method-btn" data-method="wallet" onclick="setMethod('wallet')">🪙 Wallet</button>
            </div>

            <div id="phoneField" class="input-wrap" style="display:none;">
                <label>Phone Number</label>
                <input type="tel" id="phoneInput" placeholder="07XXXXXXXX or 254XXXXXXXXX">
            </div>

            <button id="deployBtn" class="deploy-btn" onclick="initiatePurchase()" disabled>
                🚀 DEPLOY BOT — <span id="payAmount">{{ $currency }} 0</span>
            </button>
            @endif
            <div id="errMsg" class="err-msg"></div>
        </div>
    </div>

</div>

<div class="overlay" id="overlay">
    <div class="spinner"></div>
    <div class="overlay-msg" id="overlayMsg">Processing...</div>
</div>

@if($paystackConfigured)
<script src="https://js.paystack.co/v1/inline.js"></script>
@endif

<script>
const CSRF           = document.querySelector('meta[name=csrf-token]').content;
const CURRENCY       = '{{ $currency }}';
const IS_SUPER_ADMIN = {{ $isSuperAdmin ? 'true' : 'false' }};

let selBot    = null;
let selPlan   = null;
let selPrice  = 0;
let selMethod = null;

const REPOS = @json($repos->keyBy('id'));

/* ─── Step 1: Select Bot ─── */
function selectBot(id) {
    selBot = id;
    document.querySelectorAll('.bot-card').forEach(c => {
        const match = parseInt(c.id.split('-')[2]) === id;
        c.classList.toggle('active', match);
        const dot = document.getElementById('dot-' + c.id.split('-')[2]);
        if (dot) dot.style.display = match ? 'flex' : 'none';
    });
    renderDetail(id);
    document.getElementById('detailPanel').style.display = 'block';
    document.getElementById('detailPanel').scrollIntoView({ behavior: 'smooth', block: 'start' });

    // Super admins: auto-select the best available plan
    if (IS_SUPER_ADMIN) {
        const planCards = document.querySelectorAll('.plan-card');
        if (planCards.length > 0) {
            planCards[planCards.length - 1].click();
        }
    }

    checkReady();
}

/* ─── Render Bot Detail + Config Fields ─── */
function renderDetail(id) {
    const repo = REPOS[id];
    if (!repo) return;

    // Avatar
    const avatarWrap = document.getElementById('detailAvatarWrap');
    if (repo.image_url) {
        avatarWrap.innerHTML = `<img src="${repo.image_url}" class="detail-avatar" alt="">`;
    } else {
        avatarWrap.innerHTML = `<div class="detail-avatar-ph">🤖</div>`;
    }

    document.getElementById('detailName').textContent = repo.name;
    document.getElementById('detailDesc').textContent = repo.description || '';

    // GitHub link
    const ghLink = document.getElementById('githubLink');
    if (repo.git_address) {
        let url = repo.git_address;
        if (!url.startsWith('http')) url = 'https://github.com/' + url;
        ghLink.href = url;
        ghLink.style.display = 'inline-flex';
    } else {
        ghLink.style.display = 'none';
    }

    // Config fields
    const schema = repo.env_schema ? JSON.parse(repo.env_schema) : [];
    const grid   = document.getElementById('configFields');
    const noNote = document.getElementById('noConfigNote');
    grid.innerHTML = '';

    if (!schema.length) {
        noNote.style.display = '';
    } else {
        noNote.style.display = 'none';
        schema.forEach(f => {
            const req = f.required ? '<span class="cfg-req">*</span>' : '<span style="font-size:0.58rem;color:rgba(255,255,255,0.25);margin-left:4px;">(optional)</span>';
            const div = document.createElement('div');
            div.className = 'cfg-field';
            div.innerHTML = `
                <label>${escHtml(f.key)}${req}</label>
                <input type="${f.secret ? 'password' : 'text'}"
                       id="cfg_${escAttr(f.key)}"
                       name="${escAttr(f.key)}"
                       data-required="${f.required ? '1' : '0'}"
                       placeholder="${f.required ? 'Required' : (f.default ? escAttr(f.default) : 'Optional')}"
                       value="${escAttr(f.default || '')}"
                       autocomplete="off">
                ${f.description ? `<div class="cfg-hint">${escHtml(f.description)}</div>` : ''}
            `;
            grid.appendChild(div);
        });

        // Live re-check and visual feedback as user types
        grid.querySelectorAll('input').forEach(inp => {
            inp.addEventListener('input', () => {
                if (inp.dataset.required === '1') {
                    inp.style.borderColor = inp.value.trim()
                        ? 'rgba(0,255,0,0.55)'
                        : 'rgba(255,80,80,0.55)';
                }
                checkReady();
            });
            // Initial state for required fields
            if (inp.dataset.required === '1' && !inp.value.trim()) {
                inp.style.borderColor = 'rgba(255,80,80,0.4)';
            }
        });
    }
}

/* ─── Step 3: Select Plan ─── */
function selectPlan(id, price, name) {
    selPlan  = id;
    selPrice = price;
    document.querySelectorAll('.plan-card').forEach(c => c.classList.toggle('active', c.id === 'plan-' + id));
    const amtEl = document.getElementById('payAmount');
    if (amtEl) amtEl.textContent = CURRENCY + ' ' + price.toLocaleString();
    checkReady();
}

/* ─── Step 4: Select Payment Method ─── */
function setMethod(m) {
    selMethod = m;
    document.querySelectorAll('.method-btn').forEach(b => b.classList.toggle('active', b.dataset.method === m));
    document.getElementById('phoneField').style.display = ['mpesa','airtel'].includes(m) ? '' : 'none';
    checkReady();
}

function requiredFieldsFilled() {
    const inputs = document.querySelectorAll('#configFields input[data-required="1"]');
    for (const inp of inputs) {
        if (!inp.value.trim()) return false;
    }
    return true;
}

function checkReady() {
    const cfgOk  = requiredFieldsFilled();
    const ready  = IS_SUPER_ADMIN
        ? (selBot && selPlan && cfgOk)
        : (selBot && selPlan && selMethod && cfgOk);
    document.getElementById('deployBtn').disabled = !ready;
}

/* ─── Helpers ─── */
function escHtml(s)  { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function escAttr(s)  { return String(s).replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
function showErr(m)  { const e = document.getElementById('errMsg'); e.textContent = m; e.style.display = ''; }
function hideErr()   { document.getElementById('errMsg').style.display = 'none'; }
function showOverlay(m) { document.getElementById('overlayMsg').textContent = m||'Processing...'; document.getElementById('overlay').classList.add('show'); }
function hideOverlay()  { document.getElementById('overlay').classList.remove('show'); }

/* ─── Collect config values ─── */
function collectConfigs() {
    const repo   = REPOS[selBot];
    const schema = repo?.env_schema ? JSON.parse(repo.env_schema) : [];
    const configs = {};
    for (const f of schema) {
        const el = document.getElementById('cfg_' + f.key);
        if (el) configs[f.key] = el.value.trim();
    }
    return configs;
}

/* ─── Initiate Purchase ─── */
function initiatePurchase() {
    hideErr();
    const phone = document.getElementById('phoneInput').value.trim();
    if (['mpesa','airtel'].includes(selMethod) && !phone) { showErr('Enter your phone number.'); return; }

    // Validate required config fields
    const repo   = REPOS[selBot];
    const schema = repo?.env_schema ? JSON.parse(repo.env_schema) : [];
    for (const f of schema) {
        if (f.required) {
            const el = document.getElementById('cfg_' + f.key);
            if (!el || !el.value.trim()) { showErr(`"${f.key}" is required.`); el?.focus(); return; }
        }
    }

    const configs = collectConfigs();
    showOverlay('Initiating payment...');

    fetch('/bots/initiate', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ repo_id: selBot, plan_id: selPlan, payment_method: selMethod, phone, configs }),
    })
    .then(r => r.json())
    .then(res => {
        hideOverlay();
        if (res.status === 'success') { window.location.href = res.redirect; return; }
        if (res.type === 'card')      { openPaystackCard(res); return; }
        if (res.type === 'mobile')    { pollMobile(res.reference, res.provider, res.phone); return; }
        showErr(res.error || 'Payment initiation failed. Please try again.');
    })
    .catch(e => { hideOverlay(); showErr('Network error: ' + e.message); });
}

/* ─── Card Payment (Paystack Inline) ─── */
function openPaystackCard(res) {
    @if($paystackConfigured)
    const h = PaystackPop.setup({
        key:      res.public_key,
        email:    res.email,
        amount:   res.amount_kobo,
        currency: res.currency,
        ref:      res.reference,
        onSuccess: t => { showOverlay('Verifying payment...'); verifyPayment(t.reference); },
        onCancel:  () => showErr('Payment cancelled.'),
    });
    h.openIframe();
    @else
    showErr('Card payment not configured.');
    @endif
}

/* ─── Mobile Money Polling ─── */
function pollMobile(ref, provider, phone) {
    showOverlay('Waiting for ' + provider + ' prompt on\n' + phone + '...\n\nApprove on your phone.');
    let tries = 0;
    const t = setInterval(() => {
        tries++;
        fetch('/bots/verify', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ reference: ref }),
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') { clearInterval(t); window.location.href = res.redirect; }
            else if (res.status === 'failed') { clearInterval(t); hideOverlay(); showErr('Payment failed or cancelled.'); }
            else if (tries >= 24) { clearInterval(t); hideOverlay(); showErr('Payment timed out. Contact support if amount was deducted.'); }
        })
        .catch(() => {});
    }, 5000);
}

/* ─── Card Payment Verification ─── */
function verifyPayment(ref) {
    fetch('/bots/verify', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ reference: ref }),
    })
    .then(r => r.json())
    .then(res => {
        hideOverlay();
        if (res.status === 'success') window.location.href = res.redirect;
        else showErr(res.message || 'Verification failed.');
    })
    .catch(e => { hideOverlay(); showErr('Network error: ' + e.message); });
}
</script>
</body>
</html>
