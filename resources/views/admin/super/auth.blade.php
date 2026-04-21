<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin — wolfXcore</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700;900&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: #030a03;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'JetBrains Mono', monospace;
            position: relative;
            overflow: hidden;
        }

        /* Subtle grid overlay */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(0,255,0,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,255,0,0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
        }

        /* Glow blobs */
        body::after {
            content: '';
            position: fixed;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(0,255,0,0.06) 0%, transparent 70%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            pointer-events: none;
        }

        .card {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 420px;
            background: rgba(6,13,6,0.92);
            border: 1px solid rgba(0,255,0,0.25);
            border-radius: 6px;
            box-shadow: 0 0 60px rgba(0,255,0,0.12), 0 0 120px rgba(0,255,0,0.04);
            padding: 40px 36px 36px;
            margin: 20px;
        }

        .logo {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.6rem;
            font-weight: 900;
            color: #00ff00;
            letter-spacing: 6px;
            text-transform: uppercase;
            text-shadow: 0 0 20px rgba(0,255,0,0.5);
        }

        .logo-sub {
            margin-top: 6px;
            font-size: 0.68rem;
            color: rgba(0,255,0,0.4);
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        .divider {
            border: none;
            border-top: 1px solid rgba(0,255,0,0.12);
            margin: 0 0 28px;
        }

        .error-bar {
            background: rgba(255,50,50,0.08);
            border: 1px solid rgba(255,50,50,0.35);
            color: #ff6666;
            padding: 11px 14px;
            margin-bottom: 22px;
            font-size: 0.78rem;
            border-radius: 3px;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .error-bar svg { flex-shrink: 0; }

        label {
            display: block;
            font-size: 0.7rem;
            color: rgba(0,255,0,0.6);
            letter-spacing: 2.5px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        input[type="password"] {
            width: 100%;
            background: rgba(0,10,0,0.7);
            border: 1px solid rgba(0,255,0,0.25);
            border-radius: 3px;
            color: #00ff00;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.95rem;
            padding: 12px 14px;
            letter-spacing: 3px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input[type="password"]:focus {
            border-color: rgba(0,255,0,0.6);
            box-shadow: 0 0 0 3px rgba(0,255,0,0.08);
        }

        input[type="password"]::placeholder {
            color: rgba(0,255,0,0.2);
            letter-spacing: 1px;
        }

        .btn {
            width: 100%;
            margin-top: 22px;
            padding: 13px;
            background: #00ff00;
            color: #000;
            border: none;
            border-radius: 3px;
            font-family: 'Orbitron', sans-serif;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
            cursor: pointer;
            transition: background 0.2s, box-shadow 0.2s;
        }

        .btn:hover {
            background: #33ff33;
            box-shadow: 0 0 20px rgba(0,255,0,0.4);
        }

        .btn:active { background: #00cc00; }

        .footer-note {
            margin-top: 24px;
            text-align: center;
            font-size: 0.65rem;
            color: rgba(0,255,0,0.2);
            letter-spacing: 1.5px;
        }

        @keyframes scanline {
            0%   { transform: translateY(-100%); }
            100% { transform: translateY(100vh); }
        }

        .scanline {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(transparent, rgba(0,255,0,0.06), transparent);
            pointer-events: none;
            animation: scanline 8s linear infinite;
        }
    </style>
</head>
<body>
    <div class="scanline"></div>

    <div class="card">
        <div class="logo">
            <div class="logo-title">⚡ wolfXcore</div>
            <div class="logo-sub">Super Admin — Owner Access Only</div>
        </div>
        <hr class="divider">

        @if(session('super_error'))
        <div class="error-bar">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            {{ session('super_error') }}
        </div>
        @endif

        <form action="{{ route('admin.super.authenticate') }}" method="POST">
            @csrf
            <label for="key">Secret Key</label>
            <input
                type="password"
                id="key"
                name="key"
                autofocus
                autocomplete="off"
                placeholder="••••••••••••••••"
            >
            <button type="submit" class="btn">Authenticate</button>
        </form>

        <div class="footer-note">All access attempts are logged.</div>
    </div>
</body>
</html>
