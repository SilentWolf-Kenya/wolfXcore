<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="noindex" />
  <title>wolfXcore — Game Server Panel</title>

  {{-- Favicon --}}
  <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
  <link rel="icon" type="image/png" href="/favicons/favicon-32x32.png" sizes="32x32">
  <link rel="icon" type="image/png" href="/favicons/favicon-16x16.png" sizes="16x16">
  <link rel="shortcut icon" href="/favicons/favicon.ico">
  <meta name="theme-color" content="#00ff00">

  @php
      $wxnLogo = \Pterodactyl\Http\Controllers\Admin\SuperAdminController::getSiteLogo();
      $wxnLogoAbsolute = \Illuminate\Support\Str::startsWith($wxnLogo, 'http') ? $wxnLogo : config('app.url') . $wxnLogo;
  @endphp
  <link rel="icon" type="image/jpeg" href="{{ $wxnLogo }}">
  <link rel="apple-touch-icon" href="{{ $wxnLogo }}">

  {{-- Open Graph / Social Media --}}
  <meta property="og:type"         content="website">
  <meta property="og:site_name"    content="wolfXcore">
  <meta property="og:title"        content="wolfXcore — Game Server Panel">
  <meta property="og:description"  content="High-performance game server management. Deploy, manage, and scale your game servers.">
  <meta property="og:image"        content="https://i.ibb.co/n8zGnhVj/Screenshot-2026-04-14-175412.png">
  <meta property="og:image:width"  content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:url"          content="{{ config('app.url') }}">
  <meta name="twitter:card"        content="summary_large_image">
  <meta name="twitter:title"       content="wolfXcore — Game Server Panel">
  <meta name="twitter:description" content="High-performance game server management. Deploy, manage, and scale your game servers.">
  <meta name="twitter:image"       content="https://i.ibb.co/n8zGnhVj/Screenshot-2026-04-14-175412.png">
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet" />
  <style>
    :root {
      --bg:          hsl(120,100%,2%);
      --fg:          hsl(120,100%,50%);
      --primary:     hsl(120,100%,50%);
      --primary-dim: hsl(120,50%,40%);
      --card-bg:     hsl(120,100%,4%);
      --muted:       hsl(120,30%,10%);
      --muted-fg:    hsl(120,50%,40%);
      --border:      hsl(120,100%,20%);
      --border-dim:  rgba(0,255,0,0.12);
      --neon-glow:   0 0 20px rgba(0,255,0,0.45);
      --neon-glow-sm:0 0 10px rgba(0,255,0,0.25);
      --font-display:'Orbitron', sans-serif;
      --font-mono:   'JetBrains Mono', monospace;
      --radius:      0.75rem;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; }

    body {
      background: var(--bg);
      color: var(--fg);
      font-family: var(--font-mono);
      min-height: 100vh;
      overflow-x: hidden;
    }

    h1,h2,h3,h4,h5,h6 { font-family: var(--font-display); font-weight: 700; }
    a { text-decoration: none; }

    /* ── Neon background ── */
    #neon-bg {
      position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden;
    }
    #neon-bg::before {
      content: '';
      position: absolute; inset: 0;
      background-image:
        linear-gradient(rgba(0,255,0,0.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,255,0,0.06) 1px, transparent 1px);
      background-size: 80px 80px;
    }
    #neon-bg::after {
      content: '';
      position: absolute; inset: 0;
      background: radial-gradient(ellipse 85% 75% at 50% 40%, transparent 35%, var(--bg) 100%);
    }
    .scan-lines {
      position: fixed; inset: 0; z-index: 0; pointer-events: none;
      background: repeating-linear-gradient(0deg, transparent, transparent 3px, rgba(0,255,0,0.012) 3px, rgba(0,255,0,0.012) 4px);
      animation: scanDrift 18s linear infinite;
    }
    @keyframes scanDrift { to { background-position-y: 80px; } }

    .grid-node {
      position: fixed;
      width: 3px; height: 3px; border-radius: 50%;
      background: var(--primary);
      box-shadow: 0 0 6px var(--primary), 0 0 14px rgba(0,255,0,0.4);
      transform: translate(-50%,-50%);
      pointer-events: none; z-index: 0;
      animation: nodePulse var(--dur,3s) ease-in-out infinite;
      animation-delay: var(--delay,0s);
    }
    @keyframes nodePulse {
      0%,100% { opacity: 0.15; transform: translate(-50%,-50%) scale(1); }
      50%      { opacity: 1;    transform: translate(-50%,-50%) scale(1.8); }
    }
    .energy-line {
      position: fixed; pointer-events: none; z-index: 0;
      background: linear-gradient(90deg, transparent, var(--primary), transparent);
      animation: energyFlow var(--dur,8s) linear infinite;
      opacity: 0.25;
    }
    @keyframes energyFlow {
      from { transform: translateX(-100vw); }
      to   { transform: translateX(200vw); }
    }

    /* ── Corner accents ── */
    .corner { position: fixed; width: 90px; height: 90px; z-index: 2; pointer-events: none; }
    .corner-tl { top:0; left:0;  border-top:1px solid var(--primary); border-left: 1px solid var(--primary); box-shadow: 2px 2px 20px rgba(0,255,0,0.2); }
    .corner-tr { top:0; right:0; border-top:1px solid var(--primary); border-right:1px solid var(--primary); box-shadow:-2px 2px 20px rgba(0,255,0,0.2); }
    .corner-bl { bottom:0; left:0;  border-bottom:1px solid var(--primary); border-left: 1px solid var(--primary); box-shadow: 2px -2px 20px rgba(0,255,0,0.2); }
    .corner-br { bottom:0; right:0; border-bottom:1px solid var(--primary); border-right:1px solid var(--primary); box-shadow:-2px -2px 20px rgba(0,255,0,0.2); }

    /* ── Utility ── */
    .container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
    .z1 { position: relative; z-index: 1; }

    .badge {
      display: inline-block;
      font-size: 0.6rem; letter-spacing: 0.14em; text-transform: uppercase;
      padding: 2px 8px; border-radius: 999px;
      background: rgba(0,255,0,0.15); border: 1px solid rgba(0,255,0,0.3);
      color: var(--primary); font-family: var(--font-mono);
    }
    .neon-btn {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 10px 22px; border-radius: var(--radius);
      font-family: var(--font-mono); font-size: 0.85rem;
      border: 1px solid rgba(0,255,0,0.3); color: var(--primary);
      background: rgba(0,255,0,0.08); cursor: pointer;
      transition: background 0.2s, box-shadow 0.2s, transform 0.1s;
    }
    .neon-btn:hover { background: rgba(0,255,0,0.15); box-shadow: var(--neon-glow-sm); }
    .neon-btn:active { transform: scale(0.97); }
    .neon-btn-outline {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 10px 22px; border-radius: var(--radius);
      font-family: var(--font-mono); font-size: 0.85rem;
      border: 1px solid rgba(255,255,255,0.15); color: #ccc;
      background: transparent; cursor: pointer;
      transition: background 0.2s, color 0.2s;
    }
    .neon-btn-outline:hover { background: rgba(255,255,255,0.06); color: #fff; }
    .check-icon { color: rgba(0,255,0,0.65); flex-shrink: 0; }
    .pulse-dot {
      display: inline-block; width: 6px; height: 6px; border-radius: 50%;
      background: var(--primary); box-shadow: 0 0 6px var(--primary);
      animation: pulseDot 2.2s ease-in-out infinite;
      vertical-align: middle; margin-right: 6px;
    }
    @keyframes pulseDot { 0%,100%{opacity:1;} 50%{opacity:0.2;} }

    /* ── Navbar ── */
    nav {
      position: fixed; top: 0; left: 0; right: 0; z-index: 50;
      background: rgba(0,5,0,0.95); backdrop-filter: blur(14px);
      border-bottom: 1px solid rgba(0,255,0,0.08);
    }
    .nav-inner {
      max-width: 1200px; margin: 0 auto; padding: 0 24px;
      height: 64px; display: flex; align-items: center; justify-content: space-between;
    }
    .brand { display: flex; align-items: center; gap: 12px; user-select: none; }
    .brand-icon {
      width: 40px; height: 40px; border-radius: 10px;
      background: rgba(0,255,0,0.04); border: 1px solid rgba(0,255,0,0.2);
      display: flex; align-items: center; justify-content: center;
      font-size: 18px; animation: iconGlow 3s ease-in-out infinite;
    }
    @keyframes iconGlow {
      0%,100% { box-shadow: 0 0 10px rgba(0,255,0,0.2); }
      50%      { box-shadow: 0 0 22px rgba(0,255,0,0.35); }
    }
    .brand-text { font-family: var(--font-display); font-size: 1.15rem; font-weight: 700; letter-spacing: 0.06em; }
    .brand-text .w { color: var(--primary); }
    .brand-text .x { color: #9ca3af; }
    .brand-text .c { color: #fff; }
    .brand-sub { font-size: 0.65rem; color: #4b5563; font-family: var(--font-mono); margin-top: 1px; }
    .nav-links { display: flex; gap: 8px; }

    /* ── Hero ── */
    .hero { padding-top: 128px; padding-bottom: 80px; text-align: center; }
    .hero h1 {
      font-size: clamp(2.2rem, 6vw, 4.5rem);
      line-height: 1.05; margin-bottom: 20px; letter-spacing: -0.01em;
    }
    .hero h1 .white { color: #fff; }
    .hero h1 .gradient {
      background: linear-gradient(90deg, rgba(0,255,0,0.85), #00ff00, rgba(0,255,0,0.65));
      -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
    }
    .hero-badge-wrap {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 5px 14px; border: 1px solid rgba(0,255,0,0.2);
      border-radius: 999px; font-size: 0.65rem; letter-spacing: 0.14em;
      text-transform: uppercase; color: var(--primary);
      margin-bottom: 28px; background: rgba(0,255,0,0.04);
    }
    .hero-desc { color: #6b7280; max-width: 560px; margin: 0 auto 36px; font-size: 0.95rem; line-height: 1.7; }
    .hero-cta { display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; margin-bottom: 48px; }

    /* ── Terminal preview ── */
    .terminal-preview {
      background: #010e01; border: 1px solid rgba(0,255,0,0.18);
      border-radius: var(--radius); padding: 20px;
      font-size: 0.78rem; line-height: 1.8; text-align: left;
      max-width: 680px; margin: 0 auto 56px;
      box-shadow: 0 0 40px rgba(0,255,0,0.06);
    }
    .term-bar { display: flex; gap: 6px; margin-bottom: 14px; }
    .term-dot { width: 10px; height: 10px; border-radius: 50%; }
    .dot-r { background: rgba(255,80,80,0.6); }
    .dot-y { background: rgba(255,200,0,0.6); }
    .dot-g { background: rgba(0,255,0,0.6); }
    .t-green  { color: var(--primary); }
    .t-yellow { color: #facc15; }
    .t-dim    { color: rgba(255,255,255,0.35); }
    .t-white  { color: #e0ffe0; }
    .t-bold   { font-weight: 700; }
    @keyframes blink { 0%,100%{opacity:1;} 50%{opacity:0;} }

    /* ── Region selector ── */
    .region-label { font-size: 0.65rem; letter-spacing: 0.14em; text-transform: uppercase; color: #4b5563; margin-bottom: 8px; }
    select.region-select {
      appearance: none; -webkit-appearance: none;
      background: rgba(0,0,0,0.6) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2300ff00' d='M6 8L1 3h10z'/%3E%3C/svg%3E") no-repeat right 10px center;
      border: 1px solid rgba(0,255,0,0.3); color: var(--primary);
      font-family: var(--font-mono); font-size: 0.82rem;
      border-radius: 8px; padding: 8px 32px 8px 12px; cursor: pointer;
      outline: none; margin-bottom: 24px;
    }
    select.region-select:focus { border-color: rgba(0,255,0,0.6); box-shadow: 0 0 0 2px rgba(0,255,0,0.1); }
    select.region-select option { background: #000800; }

    /* ── Pricing cards ── */
    .pricing-grid {
      display: grid; gap: 16px;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      max-width: 1000px; margin: 0 auto;
    }
    .plan-card {
      padding: 20px; border-radius: var(--radius);
      background: rgba(0,0,0,0.45); border: 1px solid rgba(0,255,0,0.15);
      backdrop-filter: blur(10px); text-align: left;
      transition: border-color 0.25s, transform 0.25s, box-shadow 0.25s;
      position: relative; overflow: hidden; display: flex; flex-direction: column;
    }
    .plan-card:hover { border-color: rgba(0,255,0,0.35); transform: translateY(-4px); box-shadow: 0 12px 40px rgba(0,0,0,0.6); }
    .plan-card.popular { border-color: rgba(0,255,0,0.45); box-shadow: 0 0 24px rgba(0,255,0,0.1); }
    .plan-top-bar {
      position: absolute; top: 0; left: 0; right: 0; height: 2px;
      background: linear-gradient(90deg, transparent, var(--primary), transparent);
    }
    .plan-badge { position: absolute; top: 10px; right: 10px; }
    .plan-icon {
      display: inline-flex; align-items: center; justify-content: center;
      width: 38px; height: 38px; border-radius: 8px;
      background: rgba(0,255,0,0.08); border: 1px solid rgba(0,255,0,0.1);
      margin-bottom: 10px; font-size: 16px;
    }
    .plan-name { font-family: var(--font-display); font-size: 1.1rem; font-weight: 700; color: #fff; }
    .plan-desc { font-size: 0.7rem; color: #6b7280; margin-top: 2px; }
    .plan-price { font-family: var(--font-display); font-size: 1.8rem; font-weight: 700; color: var(--primary); margin: 10px 0 2px; }
    .plan-price-unit { font-size: 0.72rem; color: #6b7280; margin-left: 4px; font-family: var(--font-mono); }
    .plan-specs { list-style: none; margin: 12px 0; flex: 1; }
    .plan-specs li { display: flex; align-items: center; gap: 8px; font-size: 0.78rem; color: #9ca3af; padding: 4px 0; }
    .plan-btn {
      display: flex; align-items: center; justify-content: center; gap: 6px;
      width: 100%; padding: 10px; border-radius: 8px; margin-top: 16px;
      font-family: var(--font-display); font-size: 0.75rem; font-weight: 700;
      border: 1px solid rgba(0,255,0,0.25); color: var(--primary);
      background: rgba(0,255,0,0.08); cursor: pointer; transition: background 0.2s;
    }
    .plan-btn:hover { background: rgba(0,255,0,0.18); }
    .plan-btn.popular-btn { border-color: rgba(0,255,0,0.4); background: rgba(0,255,0,0.14); }

    /* ── Stats grid ── */
    .stats-grid {
      display: grid; grid-template-columns: repeat(4,1fr); gap: 16px;
      max-width: 680px; margin: 48px auto 0; text-align: center;
    }
    .stat-card {
      padding: 16px; border-radius: var(--radius);
      border: 1px solid rgba(0,255,0,0.15); background: rgba(0,0,0,0.3);
      transition: border-color 0.2s;
    }
    .stat-card:hover { border-color: rgba(0,255,0,0.35); }
    .stat-value { font-size: 1.8rem; font-weight: 700; color: #fff; font-family: var(--font-display); }
    .stat-label { font-size: 0.7rem; color: #6b7280; margin-top: 4px; }

    /* ── Features section ── */
    .section { padding: 80px 0; position: relative; z-index: 1; }
    .section-title { font-size: clamp(1.6rem,3.5vw,2.4rem); color: #fff; text-align: center; margin-bottom: 12px; }
    .section-sub { color: #6b7280; text-align: center; max-width: 520px; margin: 0 auto 48px; font-size: 0.95rem; line-height: 1.7; }
    .features-grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(260px,1fr)); gap: 24px; }
    .feat-card {
      padding: 28px; border-radius: var(--radius);
      background: rgba(0,0,0,0.3); border: 1px solid rgba(0,255,0,0.12);
      backdrop-filter: blur(10px); transition: border-color 0.2s, transform 0.25s;
    }
    .feat-card:hover { border-color: rgba(0,255,0,0.35); transform: translateY(-5px); }
    .feat-icon {
      display: inline-flex; align-items: center; justify-content: center;
      width: 56px; height: 56px; border-radius: 10px;
      border: 1px solid rgba(0,255,0,0.1); margin-bottom: 20px;
      font-size: 24px; transition: transform 0.3s;
    }
    .feat-card:hover .feat-icon { transform: scale(1.1); }
    .feat-title { font-size: 1.1rem; color: #fff; margin-bottom: 10px; transition: color 0.25s; }
    .feat-card:hover .feat-title { color: rgba(0,255,0,0.9); }
    .feat-desc { color: #6b7280; font-size: 0.85rem; line-height: 1.7; }
    .feat-more { display: flex; align-items: center; gap: 4px; color: rgba(0,255,0,0.7); font-size: 0.78rem; margin-top: 14px; opacity: 0; transition: opacity 0.25s; }
    .feat-card:hover .feat-more { opacity: 1; }

    /* ── CTA section ── */
    .cta-wrap { max-width: 720px; margin: 0 auto; padding: 0 24px; }
    .cta-card {
      padding: 48px 40px; border-radius: var(--radius);
      background: rgba(0,0,0,0.4); border: 1px solid rgba(0,255,0,0.15);
      backdrop-filter: blur(14px); text-align: center;
    }
    .cta-title { font-size: clamp(1.4rem,3vw,2rem); color: #fff; margin-bottom: 14px; }
    .cta-desc { color: #6b7280; margin-bottom: 32px; font-size: 0.95rem; line-height: 1.7; }

    /* ── Footer ── */
    footer {
      background: rgba(0,0,0,0.45); border-top: 1px solid rgba(0,255,0,0.08);
      padding: 56px 0 28px; position: relative; z-index: 1;
    }
    .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 36px; margin-bottom: 40px; }
    .footer-col h4 { color: #fff; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.12em; margin-bottom: 16px; font-family: var(--font-mono); }
    .footer-col p { color: #6b7280; font-size: 0.8rem; line-height: 1.8; }
    .footer-col ul { list-style: none; }
    .footer-col ul li { margin-bottom: 10px; }
    .footer-col ul li a { color: #6b7280; font-size: 0.8rem; transition: color 0.2s; display: flex; align-items: center; gap: 8px; }
    .footer-col ul li a:hover { color: #fff; }
    .footer-bottom {
      padding-top: 24px; border-top: 1px solid rgba(0,255,0,0.06);
      display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;
    }
    .footer-copy { color: #4b5563; font-size: 0.72rem; }
    .footer-icons { display: flex; gap: 8px; }
    .footer-icon-btn {
      width: 34px; height: 34px; border-radius: 8px;
      background: rgba(0,255,0,0.04); border: 1px solid rgba(0,255,0,0.1);
      display: flex; align-items: center; justify-content: center;
      color: #6b7280; transition: color 0.2s, border-color 0.2s;
      font-size: 13px; cursor: pointer;
    }
    .footer-icon-btn:hover { color: #4ade80; border-color: rgba(74,222,128,0.35); }

    /* ── Responsive ── */
    @media (max-width: 768px) {
      .stats-grid { grid-template-columns: repeat(2,1fr); }
      .footer-grid { grid-template-columns: 1fr 1fr; }
      .nav-links .neon-btn-outline { display: none; }
    }
    @media (max-width: 480px) {
      .footer-grid { grid-template-columns: 1fr; }
      .stat-value { font-size: 1.4rem; }
    }
  </style>
</head>
<body>

<!-- ── Background ── -->
<div id="neon-bg"></div>
<div class="scan-lines"></div>
<div class="corner corner-tl"></div>
<div class="corner corner-tr"></div>
<div class="corner corner-bl"></div>
<div class="corner corner-br"></div>

<!-- ── Navbar ── -->
<nav>
  <div class="nav-inner">
    <a href="/" class="brand" style="text-decoration:none;">
      <div class="brand-icon">⚡</div>
      <div>
        <div class="brand-text"><span class="w">wolf</span><span class="x">X</span><span class="c">core</span></div>
        <div class="brand-sub">Game Server Panel</div>
      </div>
    </a>
    <div class="nav-links">
      <a href="/auth/login"    class="neon-btn-outline">Log In</a>
      <a href="/auth/register" class="neon-btn">Get Started</a>
    </div>
  </div>
</nav>

<!-- ── Hero ── -->
<div class="hero z1">
  <div class="container">

    <div class="hero-badge-wrap">
      <span class="pulse-dot"></span>
      System Online &nbsp;·&nbsp; wolfXcore v1.0
    </div>

    <h1>
      <span class="white">YOUR GAME SERVERS.</span><br />
      <span class="gradient">UNDER YOUR CONTROL.</span>
    </h1>

    <p class="hero-desc">
      wolfXcore is a high-performance game server management panel.
      Deploy, monitor, and control your servers from a single neon-lit command centre.
    </p>

    <div class="hero-cta">
      <a href="/auth/register" class="neon-btn" style="font-size:0.9rem;padding:12px 28px;">
        Get Started →
      </a>
      <a href="/auth/login" class="neon-btn-outline" style="padding:12px 28px;">
        Login to Panel
      </a>
    </div>

    <!-- Terminal preview -->
    <div class="terminal-preview">
      <div class="term-bar">
        <div class="term-dot dot-r"></div>
        <div class="term-dot dot-y"></div>
        <div class="term-dot dot-g"></div>
      </div>
      <div><span class="t-green t-bold">wolfXcore</span><span class="t-dim">~ </span><span class="t-white">Server marked as starting...</span></div>
      <div><span class="t-green t-bold">[wolfXcore]</span><span class="t-dim">: </span><span class="t-white">Updating process configuration files...</span></div>
      <div><span class="t-green t-bold">[wolfXcore]</span><span class="t-dim">: </span><span class="t-white">Pulling Docker container image...</span></div>
      <div><span class="t-white">Node.js Version: v20.11.0</span></div>
      <div><span class="t-green t-bold">wolfXcore</span><span class="t-dim">~ </span><span class="t-white">Server marked as </span><span class="t-green">running</span><span class="t-white">...</span></div>
      <div style="margin-top:8px;"><span class="t-dim">&gt; </span><span class="t-green" style="animation:blink 1.2s step-end infinite;">█</span></div>
    </div>

    <!-- Region selector -->
    <div style="margin-bottom:16px;">
      <p class="region-label">Select your region</p>
      <select class="region-select" id="regionSelect" onchange="updatePrices()">
        <option value="KES" data-rate="1"     data-sym="KSh">🇰🇪 Kenya — Kenyan Shilling (KSh)</option>
        <option value="USD" data-rate="0.0077" data-sym="$">🇺🇸 United States — US Dollar ($)</option>
        <option value="NGN" data-rate="11.5"  data-sym="₦">🇳🇬 Nigeria — Naira (₦)</option>
        <option value="GHS" data-rate="0.096" data-sym="GH₵">🇬🇭 Ghana — Cedi (GH₵)</option>
        <option value="TZS" data-rate="20.3"  data-sym="TSh">🇹🇿 Tanzania — Shilling (TSh)</option>
        <option value="UGX" data-rate="29.2"  data-sym="USh">🇺🇬 Uganda — Shilling (USh)</option>
        <option value="ZAR" data-rate="0.144" data-sym="R">🇿🇦 South Africa — Rand (R)</option>
      </select>
    </div>

    <!-- Pricing cards -->
    <div class="pricing-grid" id="pricingGrid" style="grid-template-columns:repeat(auto-fill,minmax(200px,1fr));">

      @php
          $planIcon = function ($name) {
              $n = strtolower($name);
              if (str_contains($n, 'admin'))     return '👑';
              if (str_contains($n, 'unlimited')) return '🔥';
              if (str_contains($n, 'pro'))       return '🛡️';
              if (str_contains($n, 'standard'))  return '🚀';
              if (str_contains($n, 'starter'))   return '⚡';
              if (str_contains($n, 'enterprise')) return '🏆';
              if (str_contains($n, 'business'))  return '💼';
              return '⭐';
          };
          $planUnit = function ($name) {
              return str_contains(strtolower($name), 'admin') ? ' once' : '/mo';
          };
          $planButton = function ($name) {
              return str_contains(strtolower($name), 'admin') ? 'Get Access →' : 'Get Started →';
          };
      @endphp

      @forelse ($plans as $plan)
        @php
            $kes = (int) $plan->price;
            $isPopular = (bool) $plan->is_featured;
        @endphp
        <div class="plan-card {{ $isPopular ? 'popular' : '' }}">
          @if ($isPopular)
            <div class="plan-top-bar"></div>
            <div class="plan-badge"><span class="badge">POPULAR</span></div>
          @endif
          <div class="plan-icon">{{ $planIcon($plan->name) }}</div>
          <div class="plan-name">{{ $plan->name }}</div>
          <div class="plan-desc">{{ $plan->description ?: 'Reliable hosting from wolfXcore' }}</div>
          <div class="plan-price" data-kes-price="{{ $kes }}" data-unit="{{ $planUnit($plan->name) }}">KSh {{ number_format($kes) }}<span class="plan-price-unit">{{ $planUnit($plan->name) }}</span></div>
          <ul class="plan-specs">
            <li><span class="check-icon">✓</span> {{ $plan->memory_formatted }} RAM</li>
            <li><span class="check-icon">✓</span> {{ $plan->cpu_formatted }} CPU</li>
            <li><span class="check-icon">✓</span> {{ $plan->disk_formatted }} Disk</li>
            <li><span class="check-icon">✓</span> {{ $plan->databases }} {{ $plan->databases == 1 ? 'Database' : 'Databases' }}</li>
            <li><span class="check-icon">✓</span> {{ $plan->backups }} {{ $plan->backups == 1 ? 'Backup' : 'Backups' }}</li>
          </ul>
          <a href="/auth/register" class="plan-btn {{ $isPopular ? 'popular-btn' : '' }}">{{ $planButton($plan->name) }}</a>
        </div>
      @empty
        <div style="grid-column:1/-1;text-align:center;padding:40px;color:#888;">
          <div style="font-size:2rem;margin-bottom:10px;">⚙️</div>
          <p>No plans available right now. Please check back soon.</p>
        </div>
      @endforelse

    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card"><div class="stat-value">99.9%</div><div class="stat-label">Uptime SLA</div></div>
      <div class="stat-card"><div class="stat-value">&lt;30ms</div><div class="stat-label">Panel Response</div></div>
      <div class="stat-card"><div class="stat-value">50+</div><div class="stat-label">Game Eggs</div></div>
      <div class="stat-card"><div class="stat-value">24/7</div><div class="stat-label">Monitoring</div></div>
    </div>

  </div>
</div>

<!-- ── Features ── -->
<div class="section" style="background: rgba(0,8,0,0.35);">
  <div class="container z1">
    <h2 class="section-title">Everything You Need to Run Game Servers</h2>
    <p class="section-sub">Built with cutting-edge technology for maximum performance and reliability</p>
    <div class="features-grid">

      <div class="feat-card">
        <div class="feat-icon" style="background:rgba(0,255,0,0.06);">⚡</div>
        <h3 class="feat-title">Instant Deployment</h3>
        <p class="feat-desc">Spin up game servers in seconds with pre-built Docker-based eggs for Minecraft, CS2, Rust, ARK, Valheim and dozens more.</p>
        <div class="feat-more">Learn more <span>›</span></div>
      </div>

      <div class="feat-card">
        <div class="feat-icon" style="background:rgba(96,165,250,0.08);">📊</div>
        <h3 class="feat-title">Real-Time Monitoring</h3>
        <p class="feat-desc">Live CPU, memory, disk, and network graphs update every second. Know exactly what every server is doing at all times.</p>
        <div class="feat-more">Learn more <span>›</span></div>
      </div>

      <div class="feat-card">
        <div class="feat-icon" style="background:rgba(167,139,250,0.08);">🖥️</div>
        <h3 class="feat-title">Web Console</h3>
        <p class="feat-desc">Full terminal access directly in your browser — send commands, view live logs, and control power state with one click.</p>
        <div class="feat-more">Learn more <span>›</span></div>
      </div>

      <div class="feat-card">
        <div class="feat-icon" style="background:rgba(251,191,36,0.08);">💾</div>
        <h3 class="feat-title">Automated Backups</h3>
        <p class="feat-desc">Schedule automatic backups and restore to any snapshot in seconds. Your data is always protected and recoverable.</p>
        <div class="feat-more">Learn more <span>›</span></div>
      </div>

      <div class="feat-card">
        <div class="feat-icon" style="background:rgba(52,211,153,0.08);">📁</div>
        <h3 class="feat-title">File Manager</h3>
        <p class="feat-desc">Browse, edit, upload, and download server files from the browser. Built-in code editor with syntax highlighting for configs.</p>
        <div class="feat-more">Learn more <span>›</span></div>
      </div>

      <div class="feat-card">
        <div class="feat-icon" style="background:rgba(248,113,113,0.08);">🔐</div>
        <h3 class="feat-title">Secure by Default</h3>
        <p class="feat-desc">Two-factor authentication, granular sub-user permissions, and full HTTPS encryption on every connection.</p>
        <div class="feat-more">Learn more <span>›</span></div>
      </div>

    </div>
  </div>
</div>

<!-- ── CTA ── -->
<div class="section" style="background: linear-gradient(to bottom, rgba(0,8,0,0.35), var(--bg));">
  <div class="cta-wrap z1">
    <div class="cta-card">
      <h2 class="cta-title">Ready to launch? wolfXcore is waiting.</h2>
      <p class="cta-desc">Create your account in seconds — no credit card required. Your servers, your rules.</p>
      <a href="/auth/register" class="neon-btn" style="font-size:0.9rem;padding:13px 32px;margin:0 auto;">
        Create Free Account →
      </a>
    </div>
  </div>
</div>

<!-- ── Footer ── -->
<footer>
  <div class="container">
    <div class="footer-grid">

      <div class="footer-col">
        <div class="brand" style="margin-bottom:14px;">
          <div class="brand-icon">⚡</div>
          <div>
            <div class="brand-text"><span class="w">wolf</span><span class="x">X</span><span class="c">core</span></div>
          </div>
        </div>
        <p>High-performance game server management panel. Deploy, monitor, and control your servers with ease.</p>
        <p style="margin-top:10px;">Powered by <span style="color:var(--primary);opacity:0.85;">WOLF TECH</span></p>
      </div>

      <div class="footer-col">
        <h4>Quick Links</h4>
        <ul>
          <li><a href="/auth/login">Login</a></li>
          <li><a href="/auth/register">Register</a></li>
          <li><a href="https://panel.xwolf.space" target="_blank">Control Panel</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4>Connect</h4>
        <ul>
          <li><a href="https://whatsapp.com/channel/0029Vb6dn9nEQIaqEMNclK3Y" target="_blank"><span style="color:#4ade80;">💬</span> WhatsApp Channel</a></li>
          <li><a href="https://chat.whatsapp.com/HjFc3pud3IA0R0WGr1V2Xu" target="_blank"><span style="color:#4ade80;">👥</span> WhatsApp Group</a></li>
          <li><a href="https://www.youtube.com/@Silentwolf906" target="_blank"><span style="color:#f87171;">▶</span> YouTube</a></li>
          <li><a href="https://wa.me/254713046497" target="_blank"><span style="color:#4ade80;">📞</span> +254 713 046 497</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4>Support</h4>
        <ul>
          <li><a href="https://wa.me/254713046497" target="_blank">Contact Support</a></li>
          <li><a href="#">Documentation</a></li>
          <li><a href="#">Status Page</a></li>
        </ul>
      </div>

    </div>

    <div class="footer-bottom">
      <p class="footer-copy" id="footerYear">© 2026 wolfXcore. All systems operational.</p>
      <div class="footer-icons">
        <a class="footer-icon-btn" href="https://whatsapp.com/channel/0029Vb6dn9nEQIaqEMNclK3Y" target="_blank" title="WhatsApp Channel">💬</a>
        <a class="footer-icon-btn" href="https://chat.whatsapp.com/HjFc3pud3IA0R0WGr1V2Xu" target="_blank" title="WhatsApp Group">👥</a>
        <a class="footer-icon-btn" href="https://www.youtube.com/@Silentwolf906" target="_blank" title="YouTube">▶</a>
      </div>
    </div>
  </div>
</footer>

<script>
  document.getElementById('footerYear').textContent =
    `© ${new Date().getFullYear()} wolfXcore. All systems operational.`;

  // Animated grid nodes
  (function spawnGridNodes() {
    const bg = document.getElementById('neon-bg');
    const cols = Math.ceil(window.innerWidth / 80) + 1;
    const rows = Math.ceil(window.innerHeight / 80) + 1;
    for (let c = 0; c < cols; c++) {
      for (let r = 0; r < rows; r++) {
        if (Math.random() > 0.35) continue;
        const dot = document.createElement('div');
        dot.className = 'grid-node';
        dot.style.left = `${(c / cols) * 100}%`;
        dot.style.top  = `${(r / rows) * 100}%`;
        dot.style.setProperty('--dur',   `${2 + Math.random() * 4}s`);
        dot.style.setProperty('--delay', `${Math.random() * 6}s`);
        bg.appendChild(dot);
      }
    }
  })();

  // Floating energy lines
  (function spawnEnergyLines() {
    const bg = document.getElementById('neon-bg');
    const lines = [
      { top: '22%', h: '1px', dur: '9s',  delay: '0s' },
      { top: '55%', h: '1px', dur: '13s', delay: '-4s' },
      { top: '80%', h: '1px', dur: '7s',  delay: '-2s' },
    ];
    lines.forEach(l => {
      const el = document.createElement('div');
      el.className = 'energy-line';
      el.style.top = l.top; el.style.height = l.h;
      el.style.width = `${120 + Math.random() * 120}px`;
      el.style.setProperty('--dur', l.dur);
      el.style.animationDelay = l.delay;
      bg.appendChild(el);
    });
  })();

  // ── Christmas Theme ──────────────────────────────────────────────────────
  (function() {
    @php
      $xmasMode = \Pterodactyl\Models\Setting::where('key','settings::christmas:mode')->value('value') ?? 'auto';
      $m = (int)date('n'); $d = (int)date('j');
      $inSeason = ($m === 11 && $d >= 25) || $m === 12;
      $xmasActive = $xmasMode === 'on' || ($xmasMode === 'auto' && $inSeason);
    @endphp
    var XMAS_ACTIVE = {{ $xmasActive ? 'true' : 'false' }};
    if (!XMAS_ACTIVE) return;

    // ── Inject CSS keyframes ──────────────────────────────────────────────
    var style = document.createElement('style');
    style.textContent = `
      @keyframes xLandFall {
        0%   { transform: translateY(-30px) translateX(0) rotate(0deg); opacity:0; }
        5%   { opacity:1; }
        93%  { opacity:0.8; }
        100% { transform: translateY(105vh) translateX(var(--sway,30px)) rotate(360deg); opacity:0; }
      }
      @keyframes xLandFly {
        0%   { transform: translateX(110vw) scaleX(-1); opacity:1; }
        100% { transform: translateX(-20vw) scaleX(-1); opacity:1; }
      }
      @keyframes xLandBlink {
        0%,100% { opacity:1; box-shadow: 0 0 7px 2px currentColor; }
        50%     { opacity:0.3; box-shadow:none; }
      }
      @keyframes xLandBell  { 0%,100%{transform:rotate(0)} 20%{transform:rotate(-18deg)} 40%{transform:rotate(18deg)} 60%{transform:rotate(-14deg)} 80%{transform:rotate(14deg)} }
      @keyframes xLandRib   { 0%,100%{opacity:0.6;transform:scale(1)} 50%{opacity:1;transform:scale(1.05)} }
    `;
    document.head.appendChild(style);

    // ── Lights strip ──────────────────────────────────────────────────────
    var LIGHT_COLS = ['#ff2222','#22ff22','#ffcc00','#2288ff','#ff66cc','#fff'];
    var wire = document.createElement('div');
    wire.style.cssText = 'position:fixed;top:0;left:0;right:0;height:24px;z-index:9998;pointer-events:none;background:linear-gradient(to bottom,rgba(0,0,0,0.5),transparent);';
    var wireBar = document.createElement('div');
    wireBar.style.cssText = 'position:absolute;top:9px;left:0;right:0;height:2px;background:rgba(70,35,0,0.9);';
    wire.appendChild(wireBar);
    var count = Math.ceil(window.innerWidth / 28);
    for (var i=0;i<count;i++) {
      var bulb = document.createElement('div');
      var col = LIGHT_COLS[i % LIGHT_COLS.length];
      bulb.style.cssText = 'width:10px;height:16px;border-radius:50% 50% 45% 45%;display:inline-block;margin-left:18px;position:relative;top:5px;';
      bulb.style.background = col; bulb.style.color = col;
      bulb.style.animation = 'xLandBlink ' + (0.8+(i%5)*0.3) + 's ease-in-out ' + ((i*0.07)%1.5) + 's infinite';
      wire.appendChild(bulb);
    }
    document.body.appendChild(wire);

    // ── Snowflakes ────────────────────────────────────────────────────────
    var flakeChars = ['❄','❅','❆','*'];
    var flakeCont = document.createElement('div');
    flakeCont.style.cssText = 'position:fixed;inset:0;z-index:9995;pointer-events:none;overflow:hidden;';
    for (var f=0; f<60; f++) {
      var flake = document.createElement('span');
      var sz = (Math.random()*14+7);
      var left = Math.random()*100;
      var dur  = Math.random()*9+7;
      var dly  = Math.random()*12;
      var sway = (Math.random()*40+20) * (f%2===0?1:-1);
      flake.textContent = flakeChars[Math.floor(Math.random()*flakeChars.length)];
      flake.style.cssText = 'position:absolute;top:-30px;color:rgba(255,255,255,0.85);user-select:none;';
      flake.style.left = left+'%';
      flake.style.fontSize = sz+'px';
      flake.style.setProperty('--sway', sway+'px');
      flake.style.animation = 'xLandFall '+dur+'s linear '+dly+'s infinite';
      flakeCont.appendChild(flake);
    }
    document.body.appendChild(flakeCont);

    // ── Reindeer fly-overs ────────────────────────────────────────────────
    var flightData = [{top:'8%',dur:'22s',dly:'0s',fs:'1.8rem'},{top:'15%',dur:'30s',dly:'11s',fs:'1.4rem'},{top:'6%',dur:'17s',dly:'6s',fs:'1.6rem'}];
    flightData.forEach(function(fd) {
      var deer = document.createElement('div');
      deer.textContent = '🦌🦌🦌 🛷🎅';
      deer.style.cssText = 'position:fixed;right:0;z-index:9997;pointer-events:none;white-space:nowrap;filter:drop-shadow(0 2px 8px rgba(0,0,0,0.6));';
      deer.style.top = fd.top;
      deer.style.fontSize = fd.fs;
      deer.style.animation = 'xLandFly '+fd.dur+' linear '+fd.dly+' infinite';
      document.body.appendChild(deer);
    });

    // ── Corner decorations ────────────────────────────────────────────────
    var corners = [
      {style:'position:fixed;top:26px;left:10px;z-index:9996;pointer-events:none;font-size:1.5rem;animation:xLandRib 2s ease-in-out infinite;',text:'🎋'},
      {style:'position:fixed;bottom:18px;left:10px;z-index:9996;pointer-events:none;font-size:1.4rem;animation:xLandRib 2.2s ease-in-out .3s infinite;',text:'🎁'},
      {style:'position:fixed;bottom:18px;right:12px;z-index:9996;pointer-events:none;font-size:1.4rem;animation:xLandRib 2s ease-in-out .7s infinite;',text:'⛄'},
      {style:'position:fixed;top:46%;right:10px;z-index:9996;pointer-events:none;font-size:1.3rem;animation:xLandBell 3s ease-in-out infinite;',text:'🔔'},
    ];
    corners.forEach(function(c) { var el=document.createElement('div'); el.style.cssText=c.style; el.textContent=c.text; document.body.appendChild(el); });

    // ── Jingle bell button ────────────────────────────────────────────────
    var bellBtn = document.createElement('div');
    bellBtn.textContent = '🔔';
    bellBtn.title = 'Jingle Bells!';
    bellBtn.style.cssText = 'position:fixed;bottom:62px;right:14px;z-index:9999;cursor:pointer;font-size:1.8rem;animation:xLandBell 2s ease-in-out infinite;filter:drop-shadow(0 2px 10px rgba(255,200,0,0.8));user-select:none;';
    bellBtn.onclick = function() {
      try {
        var ctx = new (window.AudioContext || window.webkitAudioContext)();
        var notes = [[659,.18],[659,.18],[659,.36],[659,.18],[659,.18],[659,.36],[659,.18],[784,.18],[523,.18],[587,.18],[659,.48],[698,.18],[698,.18],[698,.18],[698,.18],[698,.18],[659,.18],[659,.18],[659,.14],[659,.14],[587,.18],[587,.18],[659,.18],[587,.36],[784,.36]];
        var t = ctx.currentTime+.05;
        notes.forEach(function(n){var o=ctx.createOscillator();var g=ctx.createGain();o.connect(g);g.connect(ctx.destination);o.type='triangle';o.frequency.value=n[0];g.gain.setValueAtTime(.18,t);g.gain.exponentialRampToValueAtTime(.001,t+n[1]*.9);o.start(t);o.stop(t+n[1]);t+=n[1]+.03;});
      } catch(e){}
    };
    document.body.appendChild(bellBtn);
  })();
  // ── End Christmas Theme ───────────────────────────────────────────────────

  // Pricing currency conversion — KES base prices read from data-kes-price attributes on each card
  function formatPrice(kes, rate, sym) {
    const val = Math.round(kes * rate);
    if (val >= 1000) return `${sym}${(val / 1000).toFixed(val % 1000 === 0 ? 0 : 1)}k`;
    return `${sym}${val}`;
  }
  function updatePrices() {
    const sel = document.getElementById('regionSelect');
    if (!sel) return;
    const opt = sel.options[sel.selectedIndex];
    const rate = parseFloat(opt.dataset.rate);
    const sym  = opt.dataset.sym;
    document.querySelectorAll('.plan-price[data-kes-price]').forEach((el) => {
      const kes = parseFloat(el.dataset.kesPrice) || 0;
      const unit = el.dataset.unit || '/mo';
      el.innerHTML = `${formatPrice(kes, rate, sym)}<span class="plan-price-unit">${unit}</span>`;
    });
  }
</script>

</body>
</html>
