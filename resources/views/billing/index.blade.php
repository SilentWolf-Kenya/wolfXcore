<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Billing &mdash; {{ config('app.name', 'wolfXcore') }}</title>
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

        /* Topbar */
        .topbar { display: flex; align-items: center; justify-content: space-between; padding: 12px 28px; background: var(--wxn-nav-bg, rgba(0,0,0,0.92)); border-bottom: 1px solid rgba(0,255,0,0.18); position: sticky; top: 0; z-index: 100; }
        .topbar-logo { display: flex; align-items: center; gap: 10px; }
        .topbar-logo img { width: 32px; height: 32px; border-radius: 4px; border: 1px solid rgba(0,255,0,0.3); }
        .topbar-logo span { font-family: 'Orbitron',monospace; font-size: 0.95rem; color: var(--wxn-neon,#00ff00); letter-spacing: 2px; }
        .topbar-nav { display: flex; align-items: center; gap: 20px; font-size: 0.78rem; }
        .topbar-nav a { color: rgba(255,255,255,0.55); transition: color .15s; }
        .topbar-nav a:hover, .topbar-nav a.active { color: var(--wxn-neon,#00ff00); }

        /* Wrap */
        .wrap { max-width: 1080px; margin: 0 auto; padding: 36px 20px 80px; }

        /* Alerts */
        .alert { padding: 12px 18px; border-radius: 6px; margin-bottom: 24px; font-size: 0.82rem; border: 1px solid; }
        .alert.ok  { background: rgba(0,255,0,0.07); border-color: rgba(0,255,0,0.28); color: #00ff00; }
        .alert.err { background: rgba(255,60,60,0.08); border-color: rgba(255,60,60,0.28); color: #ff7070; }

        /* Section title */
        .sec-title { font-family: 'Orbitron',monospace; font-size: 0.7rem; letter-spacing: 3px; text-transform: uppercase; color: var(--wxn-neon,#00ff00); border-bottom: 1px solid rgba(0,255,0,0.14); padding-bottom: 8px; margin-bottom: 22px; }

        /* Sub banner */
        .sub-banner { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; padding: 18px 24px; border: 1px solid rgba(0,255,0,0.28); border-radius: 8px; background: rgba(0,255,0,0.04); margin-bottom: 36px; }
        .sub-banner h3 { font-family: 'Orbitron',monospace; font-size: 0.82rem; color: #00ff00; margin-bottom: 4px; }
        .sub-banner p  { font-size: 0.74rem; color: rgba(255,255,255,0.45); }
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 0.68rem; letter-spacing: 1px; font-weight: 700; }
        .badge-active { background: rgba(0,255,0,0.13); color: #00ff00; border: 1px solid rgba(0,255,0,0.3); }
        .badge-none   { background: rgba(255,255,255,0.05); color: rgba(255,255,255,0.35); border: 1px solid rgba(255,255,255,0.1); }

        /* Two-column layout */
        .billing-layout { display: grid; grid-template-columns: 1fr 360px; gap: 28px; align-items: start; }
        @media (max-width: 820px) { .billing-layout { grid-template-columns: 1fr; } }

        /* Plan cards */
        .plan-card { border: 1.5px solid rgba(0,255,0,0.15); border-radius: 10px; background: rgba(0,0,0,0.32); padding: 22px 20px; position: relative; cursor: pointer; transition: border-color .18s, box-shadow .18s, background .18s; margin-bottom: 16px; }
        .plan-card:last-child { margin-bottom: 0; }
        .plan-card:hover { border-color: rgba(0,255,0,0.42); box-shadow: 0 0 20px rgba(0,255,0,0.08); }
        .plan-card.selected { border-color: #00ff00; box-shadow: 0 0 28px rgba(0,255,0,0.18); background: rgba(0,255,0,0.04); }
        .feat-tag { position: absolute; top: -1px; right: 16px; background: #00ff00; color: #000; font-family: 'Orbitron',monospace; font-size: 0.58rem; font-weight: 900; padding: 3px 10px; border-radius: 0 0 6px 6px; letter-spacing: 2px; }
        .plan-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
        .plan-name { font-family: 'Orbitron',monospace; font-size: 0.9rem; color: #00ff00; }
        .plan-price-row { display: flex; align-items: baseline; gap: 6px; }
        .plan-price { font-family: 'Orbitron',monospace; font-size: 1.6rem; color: #fff; font-weight: 700; }
        .plan-per { font-size: 0.7rem; color: rgba(255,255,255,0.35); }
        .plan-desc { font-size: 0.74rem; color: rgba(255,255,255,0.42); margin-bottom: 12px; line-height: 1.6; }
        .plan-features { list-style: none; display: flex; flex-wrap: wrap; gap: 8px 18px; }
        .plan-features li { font-size: 0.74rem; color: rgba(255,255,255,0.65); display: flex; gap: 5px; align-items: center; }
        .plan-features li:before { content: '▶'; color: #00ff00; font-size: 0.5rem; flex-shrink: 0; }
        .discount-tag { font-size: 0.68rem; color: rgba(0,255,0,0.8); background: rgba(0,255,0,0.07); border: 1px solid rgba(0,255,0,0.2); border-radius: 3px; padding: 2px 8px; white-space: nowrap; }

        /* Checkout panel */
        .checkout-panel { background: rgba(0,0,0,0.42); border: 1px solid rgba(0,255,0,0.22); border-radius: 10px; padding: 24px 22px; position: sticky; top: 80px; }

        /* Form elements */
        .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 18px; }
        .form-label { font-size: 0.65rem; letter-spacing: 1.5px; color: rgba(255,255,255,0.45); text-transform: uppercase; }
        .form-select, .form-input { background: rgba(0,0,0,0.5); border: 1px solid rgba(0,255,0,0.2); border-radius: 5px; color: #fff; font-family: 'JetBrains Mono',monospace; font-size: 0.84rem; padding: 10px 12px; width: 100%; transition: border-color .15s; }
        .form-select:focus, .form-input:focus { outline: none; border-color: #00ff00; }
        .form-select option { background: #0a1a0a; }

        /* Payment method tiles */
        .pay-methods { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 18px; }
        .pay-method { border: 1.5px solid rgba(0,255,0,0.14); border-radius: 7px; padding: 12px 8px; cursor: pointer; text-align: center; transition: all .16s; background: rgba(0,0,0,0.3); user-select: none; }
        .pay-method:hover { border-color: rgba(0,255,0,0.38); }
        .pay-method.active { border-color: #00ff00; background: rgba(0,255,0,0.07); }
        .pay-icon { font-size: 1.35rem; margin-bottom: 4px; }
        .pay-name { font-family: 'Orbitron',monospace; font-size: 0.58rem; letter-spacing: 0.5px; color: rgba(255,255,255,0.75); }
        .pay-sub { font-size: 0.62rem; color: rgba(255,255,255,0.35); margin-top: 1px; }
        .phone-group.hidden { display: none; }

        /* Order summary */
        .order-summary { background: rgba(0,255,0,0.04); border: 1px solid rgba(0,255,0,0.18); border-radius: 7px; padding: 14px 16px; margin-bottom: 16px; font-size: 0.8rem; }
        .sum-row { display: flex; justify-content: space-between; padding: 3px 0; }
        .sum-row span:first-child { color: rgba(255,255,255,0.4); }
        .sum-row.discount span:last-child { color: #00ff00; }
        .sum-row.total { font-family: 'Orbitron',monospace; font-size: 0.9rem; color: #00ff00; border-top: 1px solid rgba(0,255,0,0.18); margin-top: 8px; padding-top: 10px; }

        /* Pay button */
        .pay-btn { width: 100%; padding: 13px; font-family: 'Orbitron',monospace; font-size: 0.72rem; font-weight: 900; letter-spacing: 2px; background: #00ff00; color: #000; border: none; border-radius: 6px; cursor: pointer; transition: opacity .18s; }
        .pay-btn:hover:not(:disabled) { opacity: 0.88; }
        .pay-btn:disabled { opacity: 0.35; cursor: not-allowed; }
        .dep-method-btn { padding:7px 4px;font-family:'JetBrains Mono',monospace;font-size:0.62rem;background:rgba(0,255,0,0.04);color:rgba(255,255,255,0.5);border:1px solid rgba(0,255,0,0.15);border-radius:5px;cursor:pointer;transition:all .18s;white-space:nowrap; }
        .dep-method-btn.active { background:rgba(0,200,255,0.12);color:#00c8ff;border-color:rgba(0,200,255,0.4); }
        .dep-method-btn:hover:not(.active) { border-color:rgba(0,255,0,0.3);color:rgba(255,255,255,0.8); }
        .pay-error { display: none; background: rgba(255,60,60,0.09); border: 1px solid rgba(255,60,60,0.35); color: #ff7070; border-radius: 6px; padding: 10px 14px; font-size: 0.78rem; line-height: 1.5; margin-bottom: 12px; }
        .pay-error.visible { display: block; }

        /* ── STK Push / Processing overlay ── */
        .stk-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.88); z-index: 999; align-items: center; justify-content: center; }
        .stk-overlay.visible { display: flex; }
        .stk-box {
            background: #0a1a0a;
            border: 1.5px solid rgba(0,255,0,0.45);
            border-radius: 16px;
            padding: 40px 36px 32px;
            max-width: 460px; width: 92%;
            text-align: center;
            box-shadow: 0 0 60px rgba(0,255,0,0.12), 0 24px 64px rgba(0,0,0,0.7);
            animation: popIn 0.22s cubic-bezier(0.34,1.56,0.64,1);
        }
        @keyframes popIn { from { transform: scale(0.88); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .stk-icon { font-size: 3.2rem; margin-bottom: 14px; }
        .stk-icon.pulse { animation: pulse 1.5s ease-in-out infinite; }
        @keyframes pulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.12)} }
        .stk-title { font-family: 'Orbitron',monospace; font-size: 0.92rem; color: #00ff00; margin-bottom: 12px; letter-spacing: 2px; }
        .stk-msg { font-size: 0.83rem; color: rgba(255,255,255,0.68); line-height: 1.65; margin-bottom: 18px; }
        .stk-ref { font-family: 'JetBrains Mono',monospace; font-size: 0.67rem; color: rgba(0,255,0,0.5); background: rgba(0,0,0,0.45); padding: 6px 12px; border-radius: 4px; margin-bottom: 20px; word-break: break-all; letter-spacing: 0.5px; }
        .stk-spinner { width: 38px; height: 38px; border: 3px solid rgba(0,255,0,0.15); border-top-color: #00ff00; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 14px; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .stk-poll-status { font-size: 0.76rem; color: rgba(255,255,255,0.38); margin-bottom: 22px; }
        /* Action row — always two slots */
        .stk-actions { display: flex; gap: 10px; justify-content: center; }
        .stk-btn-primary {
            font-family: 'Orbitron',monospace; font-size: 0.65rem; letter-spacing: 1.5px;
            padding: 10px 22px; border-radius: 5px; cursor: pointer; transition: all .18s;
            background: #00ff00; color: #000; border: none; font-weight: 700;
        }
        .stk-btn-primary:hover { opacity: 0.85; }
        .stk-btn-secondary {
            font-family: 'Orbitron',monospace; font-size: 0.65rem; letter-spacing: 1px;
            padding: 10px 22px; border-radius: 5px; cursor: pointer; transition: all .18s;
            background: none; color: rgba(255,255,255,0.35); border: 1px solid rgba(255,255,255,0.15);
        }
        .stk-btn-secondary:hover { color: #ff7070; border-color: rgba(255,60,60,0.4); }
        .stk-btn-secondary.hidden { display: none; }
        .stk-success { color: #00ff00; font-family: 'Orbitron',monospace; font-size: 0.85rem; }
        .stk-fail { color: #ff7070; font-size: 0.82rem; margin-bottom: 18px; }

        /* History table */
        .history-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .history-table { width: 100%; min-width: 540px; border-collapse: collapse; font-size: 0.78rem; margin-top: 8px; }
        .history-table th { text-align: left; padding: 8px 12px; font-family: 'Orbitron',monospace; font-size: 0.58rem; letter-spacing: 1.5px; color: rgba(0,255,0,0.6); border-bottom: 1px solid rgba(0,255,0,0.14); }
        .history-table td { padding: 10px 12px; border-bottom: 1px solid rgba(255,255,255,0.04); color: rgba(255,255,255,0.65); white-space: nowrap; }
        .status-badge { padding: 2px 8px; border-radius: 3px; font-size: 0.66rem; font-weight: 700; }
        .status-success { background: rgba(0,255,0,0.12); color: #00ff00; }
        .status-pending { background: rgba(255,200,0,0.12); color: #ffc800; }
        .status-failed  { background: rgba(255,60,60,0.12); color: #ff7070; }

        /* Mobile responsive */
        @media (max-width: 640px) {
            .topbar { padding: 10px 14px; }
            .topbar-nav { gap: 12px; font-size: 0.72rem; }
            .topbar-nav a:not(.active):not(:last-child) { display: none; }
            .wrap { padding: 20px 12px 60px; }
            .plan-card { padding: 16px 14px; }
            .checkout-panel { padding: 18px 14px; }
            .pay-methods { gap: 6px; }
            .pay-method { padding: 10px 4px; }
            .plan-price { font-size: 1.25rem !important; }
            .order-summary { padding: 10px 12px; }
        }
        @media (max-width: 480px) {
            .topbar-nav { gap: 8px; }
            .sub-banner { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>

    <!-- Topbar -->
    <div class="topbar">
        <div class="topbar-logo">
            @if($logoUrl)<img src="{{ $logoUrl }}" alt="Logo">@endif
            <span>wolfXcore</span>
        </div>
        <nav class="topbar-nav">
            <a href="/">Dashboard</a>
            <a href="/servers">Servers</a>
            <a href="/bots">Bots</a>
            <a href="/billing" class="active">Billing</a>
            <a href="/account">Account</a>
            <a href="#" onclick="event.preventDefault();document.getElementById('logout-form').submit()">Logout</a>
        </nav>
        <form id="logout-form" action="/auth/logout" method="POST" style="display:none">@csrf</form>
    </div>

    <!-- STK Push / Processing overlay -->
    <div class="stk-overlay" id="stkOverlay">
        <div class="stk-box">
            <div class="stk-icon pulse" id="stkIcon">📱</div>
            <div class="stk-title"      id="stkTitle">CHECK YOUR PHONE</div>
            <div class="stk-msg"        id="stkMsg">Sending STK push...</div>
            <div class="stk-ref"        id="stkRef"></div>
            <div class="stk-spinner"    id="stkSpinner"></div>
            <div class="stk-poll-status" id="stkPollStatus">Waiting for confirmation...</div>
            <div class="stk-actions">
                <button class="stk-btn-primary"             id="stkPrimaryBtn"   onclick="stkPrimaryAction()">CANCEL</button>
                <button class="stk-btn-secondary hidden"    id="stkSecondaryBtn" onclick="document.getElementById('stkSecondaryBtn')._action && document.getElementById('stkSecondaryBtn')._action()">CLOSE</button>
            </div>
        </div>
    </div>

    <div class="wrap">

        @if(session('success'))
            <div class="alert ok">✓ {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert err">✗ {{ session('error') }}</div>
        @endif
        @if(!$paystackConfigured)
            <div class="alert err" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                <span>⚠ Payment gateway is not configured. Payments cannot be processed until an admin saves the Paystack API keys.</span>
                @if(Auth::user()->root_admin)
                    <a href="/admin/wxn-super/auth" style="color:#ff7070;text-decoration:underline;font-size:0.78rem;white-space:nowrap;">Configure now →</a>
                @endif
            </div>
        @endif

        <!-- Subscription status -->
        <div class="sec-title">Your Subscription</div>
        <div class="sub-banner">
            <div>
                <h3>{{ $currentPlan ? strtoupper($currentPlan->name) . ' PLAN' : 'No Active Subscription' }}</h3>
                <p>
                    @if($subscription)
                        Active &bull; Expires {{ \Carbon\Carbon::parse($subscription->expires_at)->format('d M Y') }}
                    @else
                        You are on the free tier. Subscribe below to unlock full resources.
                    @endif
                </p>
            </div>
            @if($subscription)
                <span class="badge badge-active">ACTIVE</span>
            @else
                <span class="badge badge-none">FREE TIER</span>
            @endif
        </div>

        <div class="billing-layout">

            <!-- LEFT: Plans -->
            <div>
                <div class="sec-title">Choose a Plan</div>
                @foreach($plans as $plan)
                @php $rule = $discountRules[strtoupper($plan->name)] ?? null; @endphp
                <div class="plan-card"
                     data-plan-id="{{ $plan->id }}"
                     data-plan-name="{{ strtoupper($plan->name) }}"
                     data-plan-price="{{ $plan->price }}"
                     data-plan-type="{{ strtoupper($plan->name) }}"
                     onclick="selectPlan(this)">
                    @if($plan->is_featured)<div class="feat-tag">POPULAR</div>@endif
                    <div class="plan-header">
                        <div>
                            <div class="plan-name">{{ strtoupper($plan->name) }}</div>
                            <div class="plan-price-row">
                                <span class="plan-price">KES {{ number_format($plan->price, 0) }}</span>
                                <span class="plan-per">/ month</span>
                            </div>
                            <div class="plan-local-price" data-kes="{{ $plan->price }}" style="font-size:0.68rem;color:rgba(0,255,0,0.55);margin-top:2px;min-height:1em;"></div>
                        </div>
                        @if($rule)
                            <div class="discount-tag">{{ $rule['qty'] }}mo+ save KES {{ $rule['discount_each'] }}/mo</div>
                        @endif
                    </div>
                    <div class="plan-desc">{{ $plan->description }}</div>
                    <ul class="plan-features">
                        @if(strtoupper($plan->name) === 'ADMIN PANEL')
                        <li>Full Admin Panel Access</li>
                        <li>Create &amp; manage servers</li>
                        <li>Manage all users</li>
                        <li>Node &amp; egg management</li>
                        <li>No server resource limits</li>
                        @else
                        <li>{{ $plan->memory == 0 ? '∞ Unlimited' : number_format($plan->memory / 1024, 0).' GB' }} RAM</li>
                        <li>{{ $plan->cpu == 0 ? '∞ Unlimited' : $plan->cpu.'%' }} CPU</li>
                        <li>{{ $plan->disk == 0 ? '∞ Unlimited' : number_format($plan->disk / 1024, 0).' GB' }} Disk</li>
                        <li>{{ $plan->databases }} Database(s)</li>
                        <li>{{ $plan->backups }} Backup(s)</li>
                        <li>Port allocations included</li>
                        @endif
                    </ul>
                </div>
                @endforeach
            </div>

            <!-- RIGHT: Checkout panel -->
            <div class="checkout-panel">
                <div class="sec-title" style="margin-bottom:16px;">Order Details</div>

                <div class="form-group" id="serverNameGroup" style="display:none;">
                    <label class="form-label">Server Name</label>
                    <input type="text" id="serverNameInput" class="form-input" placeholder="e.g. My Minecraft Server" maxlength="64">
                </div>

                <div class="form-group">
                    <label class="form-label">Duration</label>
                    <select id="quantitySelect" class="form-select" onchange="updateSummary()">
                        @for($i = 1; $i <= 6; $i++)
                            <option value="{{ $i }}">{{ $i }} Month{{ $i > 1 ? 's' : '' }}</option>
                        @endfor
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Your Country</label>
                    <select id="countrySelect" class="form-select" onchange="onCountryChange()">
                        <option value="KE">🇰🇪 Kenya</option>
                        <option value="TZ">🇹🇿 Tanzania</option>
                        <option value="UG">🇺🇬 Uganda</option>
                        <option value="RW">🇷🇼 Rwanda</option>
                        <option value="ET">🇪🇹 Ethiopia</option>
                        <option value="ZM">🇿🇲 Zambia</option>
                        <option value="NG">🇳🇬 Nigeria</option>
                        <option value="GH">🇬🇭 Ghana</option>
                        <option value="OTHER">🌍 Other</option>
                    </select>
                </div>

                <div class="form-label" style="margin-bottom:10px;">Payment Method</div>
                <div class="pay-methods" id="payMethodTiles">
                    <div class="pay-method active" id="pmCard" onclick="setPayMethod('card', this)">
                        <div class="pay-icon">💳</div>
                        <div class="pay-name">CARD</div>
                        <div class="pay-sub">Visa / MC</div>
                    </div>
                    <div class="pay-method" id="pmMpesa" onclick="setPayMethod('mpesa', this)">
                        <div class="pay-icon">📱</div>
                        <div class="pay-name">M-PESA</div>
                        <div class="pay-sub">KE STK Push</div>
                    </div>
                    <div class="pay-method" id="pmAirtel" onclick="setPayMethod('airtel', this)">
                        <div class="pay-icon">📲</div>
                        <div class="pay-name">AIRTEL</div>
                        <div class="pay-sub">Airtel Money</div>
                    </div>
                </div>
                <div id="payMethodNote" style="display:none;font-size:0.68rem;color:rgba(0,255,0,0.5);margin-bottom:12px;padding:8px 10px;border:1px solid rgba(0,255,0,0.15);border-radius:5px;background:rgba(0,255,0,0.03);"></div>

                <div class="phone-group hidden" id="phoneGroup">
                    <div class="form-group">
                        <label class="form-label">Phone (with country code)</label>
                        <input type="tel" id="phoneInput" class="form-input" placeholder="e.g. 254712345678">
                    </div>
                </div>

                <div class="order-summary">
                    <div class="sum-row"><span>Plan</span><span id="sumPlan">— select a plan</span></div>
                    <div class="sum-row"><span>Unit price</span><span id="sumUnit">$ —</span></div>
                    <div class="sum-row"><span>Months</span><span id="sumQty">1</span></div>
                    <div class="sum-row discount" id="sumDiscountRow" style="display:none;">
                        <span>Bulk discount</span><span id="sumDiscount">-$0</span>
                    </div>
                    <div class="sum-row total"><span>Total (KES)</span><span id="sumTotal">KES —</span></div>
                    <div class="sum-row" id="sumLocalRow" style="display:none;">
                        <span id="sumLocalLabel" style="color:rgba(0,255,0,0.5);font-size:0.72rem;">≈ Local</span>
                        <span id="sumLocal" style="color:rgba(0,255,0,0.7);font-size:0.72rem;">—</span>
                    </div>
                </div>

                <p id="selectHint" style="font-size:0.7rem;color:rgba(255,255,255,0.3);text-align:center;margin-bottom:12px;">← Select a plan on the left to continue</p>

                <div class="pay-error" id="payError"></div>
                <button id="payBtn" class="pay-btn" disabled onclick="handlePay()">⚡ PAY NOW</button>

                <div style="margin-top:10px;border-top:1px solid rgba(0,255,0,0.08);padding-top:12px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                        <span style="font-family:'JetBrains Mono',monospace;font-size:0.7rem;color:rgba(255,255,255,0.4);">💰 Wallet Balance</span>
                        <span id="walletBalanceDisplay" style="font-family:'Orbitron',monospace;font-size:0.78rem;color:#00ff00;font-weight:700;">KES {{ number_format($walletBalance, 2) }}</span>
                    </div>
                    <button id="walletPayBtn" class="pay-btn" disabled onclick="handleWalletPay()"
                        style="background:rgba(0,255,0,0.08);border-color:rgba(0,255,0,0.35);font-size:0.7rem;padding:11px;">
                        💰 PAY WITH WALLET
                    </button>
                    <p id="walletHint" style="font-size:0.65rem;color:rgba(255,255,255,0.3);text-align:center;margin-top:6px;display:none;"></p>
                </div>

                {{-- ── Wallet Top-Up ── --}}
                <div style="margin-top:10px;border-top:1px solid rgba(0,255,0,0.08);padding-top:14px;">
                    <div style="font-family:'JetBrains Mono',monospace;font-size:0.68rem;color:rgba(255,255,255,0.45);margin-bottom:10px;letter-spacing:.06em;">💳 TOP UP WALLET</div>

                    <input type="number" id="depositAmount" min="40" step="1" placeholder="Amount (KES, min 40)"
                        style="width:100%;background:rgba(0,255,0,0.04);border:1px solid rgba(0,255,0,0.2);color:#fff;font-family:'JetBrains Mono',monospace;font-size:0.75rem;padding:9px 12px;border-radius:6px;outline:none;box-sizing:border-box;margin-bottom:8px;"
                        oninput="updateDepositBtn()">

                    <div style="display:flex;gap:6px;margin-bottom:8px;">
                        <button class="dep-method-btn active" data-method="card"   onclick="selectDepMethod('card')"   style="flex:1;">💳 Card</button>
                        <button class="dep-method-btn"        data-method="mpesa"  onclick="selectDepMethod('mpesa')"  style="flex:1;">📱 M-Pesa</button>
                        <button class="dep-method-btn"        data-method="airtel" onclick="selectDepMethod('airtel')" style="flex:1;">📶 Airtel</button>
                    </div>

                    <input type="tel" id="depositPhone" placeholder="Phone e.g. 0712345678"
                        style="display:none;width:100%;background:rgba(0,255,0,0.04);border:1px solid rgba(0,255,0,0.2);color:#fff;font-family:'JetBrains Mono',monospace;font-size:0.75rem;padding:9px 12px;border-radius:6px;outline:none;box-sizing:border-box;margin-bottom:8px;">

                    <div id="depositError" style="display:none;background:rgba(255,50,50,0.12);border:1px solid rgba(255,100,100,0.35);border-radius:6px;padding:8px 12px;font-size:0.68rem;color:#ff6b6b;margin-bottom:8px;"></div>
                    <div id="depositSuccess" style="display:none;background:rgba(0,255,0,0.08);border:1px solid rgba(0,255,0,0.3);border-radius:6px;padding:8px 12px;font-size:0.68rem;color:#00ff00;margin-bottom:8px;"></div>

                    <button id="depositBtn" class="pay-btn" disabled onclick="handleDeposit()"
                        style="background:rgba(0,200,255,0.08);border-color:rgba(0,200,255,0.35);font-size:0.7rem;padding:11px;color:#00c8ff;">
                        ⬆ TOP UP
                    </button>
                </div>
            </div>

        </div><!-- /.billing-layout -->

        <!-- Payment History -->
        @if($payments->count() > 0)
        <div class="sec-title" style="margin-top:48px;">Payment History</div>
        <div class="history-table-wrap">
        <table class="history-table">
            <thead>
                <tr>
                    <th>Reference</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $p)
                @php $meta = json_decode($p->metadata ?? '{}', true); @endphp
                <tr>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:0.7rem;color:rgba(0,255,0,0.6)">{{ $p->reference }}</td>
                    <td>{{ strtoupper($p->currency ?? 'USD') }} {{ number_format($p->amount, 2) }}</td>
                    <td>{{ strtoupper($meta['payment_method'] ?? $p->gateway ?? '—') }}</td>
                    <td>{{ \Carbon\Carbon::parse($p->created_at)->format('d M Y, H:i') }}</td>
                    <td><span class="status-badge status-{{ $p->status }}">{{ strtoupper($p->status) }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>{{-- /.history-table-wrap --}}
        @endif

    </div><!-- /.wrap -->

    <!-- Paystack inline JS -->
    <script src="https://js.paystack.co/v1/inline.js"></script>

    <script>
        const CSRF           = document.querySelector('meta[name="csrf-token"]').content;
        const PUBLIC_KEY     = "{{ $paystackPublicKey }}";
        const DISCOUNT_RULES = @json($discountRules);
        const WALLET_BALANCE = {{ (float) $walletBalance }};

        let selectedPlan   = null;
        let paymentMethod  = 'card';
        let pollTimer      = null;
        let pollCount      = 0;
        const MAX_POLLS    = 40; // ~2 minutes at 3s interval

        // ── Plan selection ──
        function selectPlan(el) {
            document.querySelectorAll('.plan-card').forEach(c => c.classList.remove('selected'));
            el.classList.add('selected');
            selectedPlan = {
                id:      el.dataset.planId,
                name:    el.dataset.planName,
                price:   parseFloat(el.dataset.planPrice),
                type:    el.dataset.planType,
                isAdmin: el.dataset.planType === 'ADMIN PANEL',
            };
            document.getElementById('payBtn').disabled = false;
            document.getElementById('selectHint').style.display = 'none';

            // Show server name field only for non-admin plans
            const sng = document.getElementById('serverNameGroup');
            sng.style.display = selectedPlan.isAdmin ? 'none' : 'flex';

            updateSummary();
            updateWalletBtn();
        }

        // ── Payment method ──
        function setPayMethod(method, el) {
            paymentMethod = method;
            document.querySelectorAll('.pay-method').forEach(m => m.classList.remove('active'));
            el.classList.add('active');
            const pg = document.getElementById('phoneGroup');
            const pi = document.getElementById('phoneInput');
            if (method === 'mpesa' || method === 'airtel') {
                pg.classList.remove('hidden');
                pi.required = true;
            } else {
                pg.classList.add('hidden');
                pi.required = false;
            }
        }

        // ── Country / currency config ──
        const COUNTRY_CONFIG = {
            KE:    { name: 'Kenya',    currency: 'KES', symbol: 'KES',  rate: 130,   networks: 'M-Pesa, Airtel KE' },
            TZ:    { name: 'Tanzania', currency: 'KES', symbol: 'KES',  rate: 130,   networks: 'Visa / Mastercard' },
            UG:    { name: 'Uganda',   currency: 'KES', symbol: 'KES',  rate: 130,   networks: 'Visa / Mastercard' },
            RW:    { name: 'Rwanda',   currency: 'KES', symbol: 'KES',  rate: 130,   networks: 'Visa / Mastercard' },
            ET:    { name: 'Ethiopia', currency: 'KES', symbol: 'KES',  rate: 130,   networks: 'Visa / Mastercard' },
            ZM:    { name: 'Zambia',   currency: 'KES', symbol: 'KES',  rate: 130,   networks: 'Visa / Mastercard' },
            NG:    { name: 'Nigeria',  currency: 'KES', symbol: 'KES',  rate: 130,   networks: 'Visa / Mastercard' },
            GH:    { name: 'Ghana',    currency: 'KES', symbol: 'KES',  rate: 130,   networks: 'Visa / Mastercard' },
            OTHER: { name: 'Other',    currency: 'KES', symbol: 'KES',  rate: 130,   networks: 'Visa / Mastercard' },
        };

        function getCountry() {
            return document.getElementById('countrySelect').value || 'KE';
        }
        function getCountryCfg() {
            return COUNTRY_CONFIG[getCountry()] || COUNTRY_CONFIG['OTHER'];
        }

        // ── Country change → update payment methods + local prices ──
        function onCountryChange() {
            const country = getCountry();
            const cfg     = getCountryCfg();
            const isKenya = country === 'KE';

            // Show/hide payment tiles
            const pmMpesa   = document.getElementById('pmMpesa');
            const pmAirtel  = document.getElementById('pmAirtel');
            const pmCard    = document.getElementById('pmCard');
            const note      = document.getElementById('payMethodNote');

            // Kenya: all options. Non-Kenya: card only (Paystack inline, international cards)
            if (pmMpesa)   pmMpesa.style.display   = isKenya ? '' : 'none';
            if (pmAirtel)  pmAirtel.style.display   = isKenya ? '' : 'none';

            if (!isKenya) {
                // Auto-select card for non-Kenya, hide phone input
                document.querySelectorAll('.pay-method').forEach(m => m.classList.remove('active'));
                if (pmCard) { pmCard.classList.add('active'); paymentMethod = 'card'; }
                document.getElementById('phoneGroup').classList.add('hidden');
                note.style.display = 'block';
                note.innerHTML = '🌍 <strong>' + cfg.name + '</strong>: Pay with Visa / Mastercard — your card will be charged in KES via secure Paystack popup.';
            } else {
                note.style.display = 'none';
                // Reset to card for Kenya
                document.querySelectorAll('.pay-method').forEach(m => m.classList.remove('active'));
                if (pmCard) { pmCard.classList.add('active'); paymentMethod = 'card'; }
            }

            // All countries charge in KES — no local conversion needed
            document.querySelectorAll('.plan-local-price').forEach(el => {
                el.textContent = '';
            });

            updateSummary();
        }

        // ── Order summary ──
        function calcTotal() {
            if (!selectedPlan) return 0;
            const qty      = parseInt(document.getElementById('quantitySelect').value);
            const rule     = DISCOUNT_RULES[selectedPlan.type];
            const discount = (rule && qty >= rule.qty) ? rule.discount_each : 0;
            return Math.round(((selectedPlan.price - discount) * qty) * 100) / 100;
        }

        function updateSummary() {
            if (!selectedPlan) return;
            const qty      = parseInt(document.getElementById('quantitySelect').value);
            const rule     = DISCOUNT_RULES[selectedPlan.type];
            const discount = (rule && qty >= rule.qty) ? rule.discount_each : 0;
            const total    = (selectedPlan.price - discount) * qty;
            const cfg      = getCountryCfg();

            const country = getCountry();
            document.getElementById('sumPlan').textContent  = selectedPlan.name;
            document.getElementById('sumUnit').textContent  = 'KES ' + Math.round(selectedPlan.price).toLocaleString();
            document.getElementById('sumQty').textContent   = qty;
            document.getElementById('sumTotal').textContent = 'KES ' + Math.round(total).toLocaleString();

            const dr = document.getElementById('sumDiscountRow');
            if (discount > 0) {
                dr.style.display = 'flex';
                document.getElementById('sumDiscount').textContent = '-KES ' + Math.round(discount * qty).toLocaleString();
            } else {
                dr.style.display = 'none';
            }

            // All countries charge in KES — hide local conversion row
            document.getElementById('sumLocalRow').style.display = 'none';

            updateWalletBtn();
        }

        // ── Wallet button state ──
        // Both WALLET_BALANCE and plan totals are in KES — no conversion needed
        function updateWalletBtn() {
            const btn  = document.getElementById('walletPayBtn');
            const hint = document.getElementById('walletHint');
            if (!selectedPlan) { btn.disabled = true; hint.style.display = 'none'; return; }
            const totalKes = calcTotal(); // plan prices are KES
            if (WALLET_BALANCE >= totalKes) {
                btn.disabled = false;
                hint.style.display = 'block';
                hint.style.color   = 'rgba(0,255,0,0.5)';
                hint.textContent   = '✓ Sufficient balance (KES ' + WALLET_BALANCE.toFixed(2) + ')';
            } else {
                btn.disabled = true;
                hint.style.display = 'block';
                hint.style.color   = 'rgba(255,80,80,0.7)';
                const needed = totalKes - WALLET_BALANCE;
                hint.textContent   = '✗ Insufficient — top up KES ' + needed.toLocaleString() + ' more';
            }
        }

        // ── Wallet pay handler ──
        async function handleWalletPay() {
            if (!selectedPlan) return;
            clearPayError();
            const btn = document.getElementById('walletPayBtn');
            btn.disabled  = true;
            btn.textContent = '⏳ Processing...';
            try {
                const qty        = parseInt(document.getElementById('quantitySelect').value);
                const serverName = (document.getElementById('serverNameInput').value || '').trim();
                const body = new FormData();
                body.append('_token',      CSRF);
                body.append('plan_id',     selectedPlan.id);
                body.append('quantity',    qty);
                if (serverName) body.append('server_name', serverName);
                const res  = await fetch('/billing/wallet-pay', { method: 'POST', body, headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (!res.ok) throw new Error(data.error || 'Wallet payment failed.');
                if (data.status === 'success') {
                    btn.textContent = '✅ Success! Redirecting...';
                    setTimeout(() => { window.location.href = data.redirect || '/servers'; }, 1500);
                } else {
                    throw new Error(data.message || 'Payment failed.');
                }
            } catch (e) {
                showPayError(e.message);
                btn.disabled    = false;
                btn.textContent = '💰 PAY WITH WALLET';
            }
        }

        // ── Inline error helpers ──
        function showPayError(msg) {
            const el = document.getElementById('payError');
            el.textContent = '✗ ' + msg;
            el.classList.add('visible');
        }
        function clearPayError() {
            const el = document.getElementById('payError');
            el.textContent = '';
            el.classList.remove('visible');
        }

        // ── Main pay handler ──
        async function handlePay() {
            if (!selectedPlan) return;
            clearPayError();

            const btn = document.getElementById('payBtn');
            btn.disabled = true;

            // ── Paystack flow (card / mpesa / airtel — all countries) ──
            const phone = document.getElementById('phoneInput').value.trim();
            if ((paymentMethod === 'mpesa' || paymentMethod === 'airtel') && !phone) {
                showPayError('Please enter your phone number.');
                document.getElementById('phoneInput').focus();
                btn.disabled = false;
                return;
            }

            btn.textContent = '⏳ INITIATING...';

            try {
                const qty        = parseInt(document.getElementById('quantitySelect').value);
                const serverName = (document.getElementById('serverNameInput').value || '').trim();
                const body = new URLSearchParams({
                    _token:         CSRF,
                    plan_id:        selectedPlan.id,
                    quantity:       qty,
                    payment_method: paymentMethod,
                    phone:          phone,
                });
                if (serverName) body.append('server_name', serverName);

                const res  = await fetch('/billing/initiate', { method: 'POST', body, headers: { 'Accept': 'application/json' } });
                const data = await res.json();

                if (!res.ok || data.error) {
                    throw new Error(data.error || 'Failed to initiate payment.');
                }

                if (data.type === 'card') {
                    openPaystackInline(data);
                } else {
                    showStkOverlay(data);
                }
            } catch (e) {
                showPayError(e.message);
                btn.disabled = false;
                btn.textContent = '⚡ PAY NOW';
            }
        }

        // ── Paystack Inline popup (card) ──
        function openPaystackInline(data) {
            const handler = PaystackPop.setup({
                key:       data.public_key,
                email:     data.email,
                amount:    data.amount_kobo,
                currency:  data.currency,
                ref:       data.reference,
                label:     data.plan_name + ' Plan',
                onClose: function() {
                    const btn = document.getElementById('payBtn');
                    btn.disabled = false;
                    btn.textContent = '⚡ PAY NOW';
                },
                callback: function(response) {
                    // Payment popup closed successfully — verify on our backend
                    verifyPayment(response.reference, 'card');
                }
            });
            handler.openIframe();
        }

        // ── STK Push overlay helpers ──
        // primary: { label, action }  secondary: { label, action } | null (hidden)
        function setStkButtons(primary, secondary) {
            const pb = document.getElementById('stkPrimaryBtn');
            const sb = document.getElementById('stkSecondaryBtn');
            pb.textContent = primary.label;
            pb._action = primary.action;
            if (secondary) {
                sb.textContent = secondary.label;
                sb._action = secondary.action;
                sb.classList.remove('hidden');
            } else {
                sb.classList.add('hidden');
            }
        }
        function stkPrimaryAction() {
            const pb = document.getElementById('stkPrimaryBtn');
            if (pb._action) pb._action();
        }

        function showStkOverlay(data) {
            const overlay = document.getElementById('stkOverlay');
            const icon = document.getElementById('stkIcon');
            icon.textContent = data.provider === 'AIRTEL' ? '📲' : '📱';
            icon.classList.add('pulse');
            document.getElementById('stkTitle').textContent      = 'CHECK YOUR PHONE';
            document.getElementById('stkMsg').textContent        = '✅ STK push sent to ' + data.phone
                + '. Open the prompt on your phone and enter your ' + data.provider + ' PIN to complete payment.';
            document.getElementById('stkRef').textContent        = 'Ref: ' + data.reference;
            document.getElementById('stkSpinner').style.display  = 'block';
            document.getElementById('stkPollStatus').textContent = 'Waiting for confirmation...';
            setStkButtons({ label: 'CANCEL', action: closeOverlay }, null);
            overlay.classList.add('visible');

            pollCount = 0;
            startPolling(data.reference);
        }

        // ── Polling for mobile payment status ──
        function startPolling(reference) {
            pollTimer = setInterval(async () => {
                pollCount++;
                document.getElementById('stkPollStatus').textContent =
                    'Checking status... (' + pollCount + '/' + MAX_POLLS + ')';

                const result = await verifyPayment(reference, 'mobile');
                if (result === 'done') {
                    clearInterval(pollTimer);
                }

                if (pollCount >= MAX_POLLS) {
                    clearInterval(pollTimer);
                    document.getElementById('stkSpinner').style.display = 'none';
                    document.getElementById('stkPollStatus').innerHTML =
                        '<span class="stk-fail">Timed out. If you approved on your phone, refresh this page.</span>';
                    setStkButtons(
                        { label: 'REFRESH', action: () => window.location.reload() },
                        { label: 'CLOSE',   action: closeOverlay }
                    );
                }
            }, 3000);
        }

        // ── Verify payment (used by both card callback and mobile polling) ──
        // Returns 'done' if terminal state reached, undefined otherwise
        async function verifyPayment(reference, mode) {
            try {
                const res  = await fetch('/billing/verify', {
                    method: 'POST',
                    body:   new URLSearchParams({ _token: CSRF, reference }),
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();

                if (data.status === 'success') {
                    const dest = data.redirect || '/servers';
                    if (mode === 'card') {
                        window.location.href = dest;
                    } else {
                        // Mobile success — show confirmation then redirect
                        const icon = document.getElementById('stkIcon');
                        icon.textContent = '✅';
                        icon.classList.remove('pulse');
                        document.getElementById('stkTitle').textContent      = 'PAYMENT CONFIRMED';
                        document.getElementById('stkMsg').textContent        = data.message + ' Redirecting...';
                        document.getElementById('stkSpinner').style.display  = 'none';
                        document.getElementById('stkPollStatus').textContent = '';
                        setStkButtons(
                            { label: 'CONTINUE', action: () => window.location.href = dest },
                            null
                        );
                        setTimeout(() => { window.location.href = dest; }, 2500);
                    }
                    return 'done';
                }

                if (data.status === 'failed') {
                    if (mode === 'mobile') {
                        const icon = document.getElementById('stkIcon');
                        icon.textContent = '❌';
                        icon.classList.remove('pulse');
                        document.getElementById('stkSpinner').style.display  = 'none';
                        document.getElementById('stkPollStatus').innerHTML   = '';
                        document.getElementById('stkMsg').innerHTML =
                            '<span class="stk-fail">Payment failed or was cancelled. Please try again.</span>';
                        setStkButtons(
                            { label: 'TRY AGAIN', action: closeOverlay },
                            { label: 'CANCEL',    action: closeOverlay }
                        );
                    }
                    return 'done';
                }
            } catch (e) {
                // Network error — keep polling
            }
        }

        // ── Close / cancel overlay ──
        function closeOverlay() {
            clearInterval(pollTimer);
            document.getElementById('stkOverlay').classList.remove('visible');
            const btn = document.getElementById('payBtn');
            btn.disabled = false;
            btn.textContent = '⚡ PAY NOW';
            // Reset icon pulse class for next open
            document.getElementById('stkIcon').classList.add('pulse');
            setStkButtons({ label: 'CANCEL', action: closeOverlay }, null);
        }

        // ── Handle ?success=1 on page load (card payment callback) ──
        if (new URLSearchParams(window.location.search).get('success') === '1') {
            document.querySelector('.wrap').insertAdjacentHTML('afterbegin',
                '<div class="alert ok">✓ Payment confirmed! Your subscription is now active.</div>');
            history.replaceState({}, '', '/billing');
        }

        // ═══════════════════════════════════════
        //  WALLET DEPOSIT / TOP-UP FLOW
        // ═══════════════════════════════════════
        let DEP_METHOD = 'card';
        let WALLET_BAL = {{ (float) $walletBalance }};

        function selectDepMethod(m) {
            DEP_METHOD = m;
            document.querySelectorAll('.dep-method-btn').forEach(b => b.classList.toggle('active', b.dataset.method === m));
            const phoneInput = document.getElementById('depositPhone');
            phoneInput.style.display = (m === 'card') ? 'none' : 'block';
            updateDepositBtn();
        }

        function updateDepositBtn() {
            const amt = parseFloat(document.getElementById('depositAmount').value);
            const phone = document.getElementById('depositPhone').value.trim();
            const needsPhone = DEP_METHOD !== 'card';
            const valid = !isNaN(amt) && amt >= 40 && (!needsPhone || phone.length >= 9);
            document.getElementById('depositBtn').disabled = !valid;
        }

        function showDepError(msg) {
            const el = document.getElementById('depositError');
            el.textContent = msg;
            el.style.display = 'block';
            document.getElementById('depositSuccess').style.display = 'none';
        }

        function showDepSuccess(msg) {
            const el = document.getElementById('depositSuccess');
            el.textContent = msg;
            el.style.display = 'block';
            document.getElementById('depositError').style.display = 'none';
        }

        async function handleDeposit() {
            const btn    = document.getElementById('depositBtn');
            const amount = parseFloat(document.getElementById('depositAmount').value);
            const phone  = document.getElementById('depositPhone').value.trim();

            btn.disabled    = true;
            btn.textContent = 'Processing…';
            document.getElementById('depositError').style.display   = 'none';
            document.getElementById('depositSuccess').style.display = 'none';

            const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            const body = new FormData();
            body.append('_token',          csrf);
            body.append('amount',          amount);
            body.append('payment_method',  DEP_METHOD);
            if (DEP_METHOD !== 'card') body.append('phone', phone);

            try {
                const res  = await fetch('/billing/wallet/deposit/initiate', { method: 'POST', body, headers: { 'Accept': 'application/json' } });
                const data = await res.json();

                if (!res.ok) {
                    showDepError(data.error || 'Failed to initiate deposit.');
                    btn.disabled    = false;
                    btn.textContent = '⬆ TOP UP';
                    return;
                }

                if (data.type === 'card') {
                    const handler = PaystackPop.setup({
                        key:       data.public_key,
                        email:     data.email,
                        amount:    data.amount_kobo,
                        currency:  data.currency,
                        ref:       data.reference,
                        label:     data.label,
                        onClose:   function() {
                            btn.disabled    = false;
                            btn.textContent = '⬆ TOP UP';
                        },
                        callback:  async function(response) {
                            btn.textContent = 'Verifying…';
                            await verifyDeposit(response.reference, btn);
                        },
                    });
                    handler.openIframe();
                    return;
                }

                // Mobile money — poll
                btn.textContent = 'Awaiting approval…';
                showDepSuccess(data.message || 'STK push sent. Approve on your phone.');
                await pollDeposit(data.reference, btn);

            } catch(e) {
                showDepError('Network error: ' + e.message);
                btn.disabled    = false;
                btn.textContent = '⬆ TOP UP';
            }
        }

        async function verifyDeposit(reference, btn) {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            const body = new FormData();
            body.append('_token',    csrf);
            body.append('reference', reference);

            try {
                const res  = await fetch('/billing/wallet/deposit/verify', { method: 'POST', body, headers: { 'Accept': 'application/json' } });
                const data = await res.json();

                if (data.status === 'success') {
                    WALLET_BAL = data.new_balance ?? WALLET_BAL;
                    document.getElementById('walletBalanceDisplay').textContent = 'KES ' + WALLET_BAL.toFixed(2);
                    document.getElementById('depositAmount').value = '';
                    showDepSuccess('✓ ' + data.message + ' New balance: KES ' + WALLET_BAL.toFixed(2));
                    updateWalletBtn();
                } else if (data.status === 'pending') {
                    showDepError('Payment still pending. Check your payment history shortly.');
                } else {
                    showDepError(data.message || 'Deposit could not be confirmed.');
                }
            } catch(e) {
                showDepError('Verification error: ' + e.message);
            }

            btn.disabled    = false;
            btn.textContent = '⬆ TOP UP';
        }

        async function pollDeposit(reference, btn, attempts = 0) {
            if (attempts >= 20) {
                showDepError('Timed out waiting for payment confirmation. Check your transaction history.');
                btn.disabled    = false;
                btn.textContent = '⬆ TOP UP';
                return;
            }
            await new Promise(r => setTimeout(r, 6000));
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            const body = new FormData();
            body.append('_token',    csrf);
            body.append('reference', reference);

            try {
                const res  = await fetch('/billing/wallet/deposit/verify', { method: 'POST', body, headers: { 'Accept': 'application/json' } });
                const data = await res.json();

                if (data.status === 'success') {
                    WALLET_BAL = data.new_balance ?? WALLET_BAL;
                    document.getElementById('walletBalanceDisplay').textContent = 'KES ' + WALLET_BAL.toFixed(2);
                    document.getElementById('depositAmount').value = '';
                    showDepSuccess('✓ ' + data.message + ' New balance: KES ' + WALLET_BAL.toFixed(2));
                    updateWalletBtn();
                    btn.disabled    = false;
                    btn.textContent = '⬆ TOP UP';
                    return;
                } else if (data.status === 'failed') {
                    showDepError('Payment failed or was cancelled.');
                    btn.disabled    = false;
                    btn.textContent = '⬆ TOP UP';
                    return;
                }
                // still pending — keep polling
                await pollDeposit(reference, btn, attempts + 1);
            } catch(e) {
                showDepError('Poll error: ' + e.message);
                btn.disabled    = false;
                btn.textContent = '⬆ TOP UP';
            }
        }

        // ── Init on load ──
        onCountryChange();
    </script>
</body>
</html>
