<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Under Maintenance — wolfXcore</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=JetBrains+Mono:wght@400;700&display=swap">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: #030a03;
            color: #e8ffe8;
            font-family: 'JetBrains Mono', monospace;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* Animated grid background */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(0,255,0,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,255,0,0.04) 1px, transparent 1px);
            background-size: 40px 40px;
            animation: gridMove 20s linear infinite;
            pointer-events: none;
        }
        @keyframes gridMove {
            0%   { background-position: 0 0; }
            100% { background-position: 40px 40px; }
        }

        .card {
            position: relative;
            background: rgba(10,26,10,0.95);
            border: 1.5px solid rgba(0,255,0,0.35);
            border-radius: 16px;
            padding: 56px 52px 48px;
            max-width: 520px;
            width: 92%;
            text-align: center;
            box-shadow: 0 0 80px rgba(0,255,0,0.10), 0 24px 64px rgba(0,0,0,0.7);
            animation: fadeUp 0.5s ease;
        }
        @keyframes fadeUp {
            from { transform: translateY(24px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }

        .icon {
            font-size: 3.5rem;
            margin-bottom: 18px;
            display: block;
            animation: wrenchSpin 3s ease-in-out infinite;
        }
        @keyframes wrenchSpin {
            0%,100% { transform: rotate(-12deg); }
            50%      { transform: rotate(12deg);  }
        }

        .logo {
            font-family: 'Orbitron', monospace;
            font-size: 1.1rem;
            color: #00ff00;
            letter-spacing: 4px;
            margin-bottom: 6px;
            text-shadow: 0 0 18px rgba(0,255,0,0.5);
        }

        .subtitle {
            font-size: 0.65rem;
            color: rgba(0,255,0,0.4);
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 32px;
        }

        h1 {
            font-family: 'Orbitron', monospace;
            font-size: 1.05rem;
            color: #00ff00;
            letter-spacing: 3px;
            margin-bottom: 14px;
            text-shadow: 0 0 20px rgba(0,255,0,0.4);
        }

        p {
            font-size: 0.82rem;
            color: rgba(255,255,255,0.55);
            line-height: 1.75;
            margin-bottom: 32px;
        }

        .status-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 0.72rem;
            color: rgba(0,255,0,0.6);
            background: rgba(0,255,0,0.05);
            border: 1px solid rgba(0,255,0,0.15);
            border-radius: 6px;
            padding: 10px 18px;
            margin-bottom: 28px;
        }
        .dot {
            width: 8px; height: 8px;
            background: #00ff00;
            border-radius: 50%;
            box-shadow: 0 0 8px #00ff00;
            animation: blink 1.2s ease-in-out infinite;
        }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.2} }

        .login-link {
            font-family: 'Orbitron', monospace;
            font-size: 0.62rem;
            letter-spacing: 1.5px;
            color: rgba(255,255,255,0.2);
            text-decoration: none;
            border: 1px solid rgba(255,255,255,0.1);
            padding: 8px 18px;
            border-radius: 4px;
            transition: all 0.2s;
        }
        .login-link:hover {
            color: rgba(0,255,0,0.6);
            border-color: rgba(0,255,0,0.25);
        }

        .neon-line {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(0,255,0,0.3), transparent);
            margin: 28px 0;
        }
    </style>
</head>
<body>
    <div class="card">
        <span class="icon">🔧</span>
        <div class="logo">WOLFXCORE</div>
        <div class="subtitle">Game Server Panel</div>

        <h1>SYSTEM MAINTENANCE</h1>
        <p>
            We're performing scheduled maintenance to improve your experience.<br>
            The panel will be back online shortly.
        </p>

        <div class="status-bar">
            <div class="dot"></div>
            Maintenance in progress — please check back soon
        </div>

        <div class="neon-line"></div>

        <a href="/auth/login" class="login-link">ADMIN LOGIN</a>
    </div>
</body>
</html>
