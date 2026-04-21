<!DOCTYPE html>
<html>
    <head>
        <title>{{ config('app.name', 'wolfXcore') }}</title>

        @section('meta')
            <meta charset="utf-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta content="width=device-width, initial-scale=1" name="viewport">
            <meta name="csrf-token" content="{{ csrf_token() }}">
            <meta name="robots" content="noindex">
            <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
            <link rel="icon" type="image/png" href="/favicons/favicon-32x32.png" sizes="32x32">
            <link rel="icon" type="image/png" href="/favicons/favicon-16x16.png" sizes="16x16">
            <link rel="manifest" href="/favicons/manifest.json">
            <link rel="mask-icon" href="/favicons/safari-pinned-tab.svg" color="#00ff00">
            <link rel="shortcut icon" href="/favicons/favicon.ico">
            <meta name="msapplication-config" content="/favicons/browserconfig.xml">
            <meta name="theme-color" content="#000500">

            @php
                $wxnLogo = \wolfXcore\Http\Controllers\Admin\SuperAdminController::getSiteLogo();
                $wxnLogoAbsolute = \Illuminate\Support\Str::startsWith($wxnLogo, 'http') ? $wxnLogo : config('app.url') . $wxnLogo;
            @endphp
            <link rel="icon" type="image/jpeg" href="{{ $wxnLogo }}">
            <link rel="apple-touch-icon" href="{{ $wxnLogo }}">

            {{-- Open Graph / Social Media --}}
            <meta property="og:type"         content="website">
            <meta property="og:site_name"    content="{{ config('app.name', 'wolfXcore') }}">
            <meta property="og:title"        content="{{ config('app.name', 'wolfXcore') }} — Game Server Panel">
            <meta property="og:description"  content="High-performance game server management. Deploy, manage, and scale your game servers.">
            <meta property="og:image"        content="https://i.ibb.co/n8zGnhVj/Screenshot-2026-04-14-175412.png">
            <meta property="og:image:width"  content="1200">
            <meta property="og:image:height" content="630">
            <meta property="og:url"          content="{{ config('app.url') }}">
            <meta name="twitter:card"        content="summary_large_image">
            <meta name="twitter:title"       content="{{ config('app.name', 'wolfXcore') }} — Game Server Panel">
            <meta name="twitter:description" content="High-performance game server management. Deploy, manage, and scale your game servers.">
            <meta name="twitter:image"       content="https://i.ibb.co/n8zGnhVj/Screenshot-2026-04-14-175412.png">

            <link rel="preconnect" href="https://fonts.googleapis.com" />
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
            <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet" />
        @show

        @section('user-data')
            @if(!is_null(Auth::user()))
                <script>
                    window.wolfXcoreUser = {!! json_encode(Auth::user()->toVueObject()) !!};
                </script>
            @endif
            @if(!empty($siteConfiguration))
                <script>
                    window.SiteConfiguration = {!! json_encode($siteConfiguration) !!};
                </script>
            @endif
        @show

        @yield('assets')

        <link rel="stylesheet" href="/css/wolfxnode.css">
        @include('layouts.scripts')

        @php
            $wxnThemeCss   = \wolfXcore\Http\Controllers\Admin\SuperAdminController::getThemeCssBlock();
            $wxnCustomCss  = \wolfXcore\Http\Controllers\Admin\SuperAdminController::getCustomCss();
            $wxnThemeRaw   = \wolfXcore\Http\Controllers\Admin\SuperAdminController::getAllThemeSettings();
        @endphp
        <style id="wxn-theme-vars">{!! $wxnThemeCss !!}</style>
        @if(!empty($wxnCustomCss))
        <style id="wxn-custom-css">{{ $wxnCustomCss }}</style>
        @endif
    </head>
    <body class="{{ $css['body'] ?? '' }}">
        <div class="wxn-bg-grid" @if(($wxnThemeRaw['grid_enable'] ?? '1') === '0') data-disabled="1" @endif></div>
        <div class="wxn-bg-scan" @if(($wxnThemeRaw['scan_enable'] ?? '1') === '0') data-disabled="1" @endif></div>
        <div class="wxn-corner wxn-corner-tl"></div>
        <div class="wxn-corner wxn-corner-tr"></div>
        <div class="wxn-corner wxn-corner-bl"></div>
        <div class="wxn-corner wxn-corner-br"></div>
        @php
            $wxnAnnouncement = \wolfXcore\Http\Controllers\Admin\SuperAdminController::getAnnouncement();
            $wxnAnnColors = [
                'success' => ['bg'=>'rgba(0,200,80,0.12)',  'border'=>'rgba(0,200,80,0.45)',  'text'=>'#00e676', 'icon'=>'fa-check-circle'],
                'info'    => ['bg'=>'rgba(0,150,255,0.12)', 'border'=>'rgba(0,150,255,0.45)', 'text'=>'#4db8ff', 'icon'=>'fa-info-circle'],
                'warning' => ['bg'=>'rgba(255,160,0,0.12)', 'border'=>'rgba(255,160,0,0.45)', 'text'=>'#ffaa00', 'icon'=>'fa-exclamation-triangle'],
                'danger'  => ['bg'=>'rgba(255,50,50,0.12)', 'border'=>'rgba(255,50,50,0.45)', 'text'=>'#ff5555', 'icon'=>'fa-times-circle'],
            ];
        @endphp
        @if($wxnAnnouncement)
            @php $ac = $wxnAnnColors[$wxnAnnouncement['type']] ?? $wxnAnnColors['info']; @endphp
            <div id="wxn-sitewide-banner" style="
                position:fixed;top:0;left:0;right:0;z-index:99999;
                padding:10px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;
                background:{{ $ac['bg'] }};border-bottom:2px solid {{ $ac['border'] }};
                font-family:'JetBrains Mono',monospace;font-size:0.82rem;color:{{ $ac['text'] }};
                box-sizing:border-box;backdrop-filter:blur(4px);
            ">
                <span><i class="fa {{ $ac['icon'] }}" style="margin-right:8px;"></i>{{ $wxnAnnouncement['text'] }}</span>
                <button id="wxn-banner-dismiss" style="
                    background:none;border:none;color:{{ $ac['text'] }};cursor:pointer;font-size:1.1rem;opacity:0.6;
                    padding:0 4px;line-height:1;flex-shrink:0;
                " aria-label="Dismiss">&times;</button>
            </div>
            <script>
            (function() {
                function wxnApplyBannerOffset() {
                    var banner = document.getElementById('wxn-sitewide-banner');
                    if (!banner || banner.style.display === 'none') return;
                    document.body.style.paddingTop = banner.offsetHeight + 'px';
                }
                document.addEventListener('DOMContentLoaded', function() {
                    wxnApplyBannerOffset();
                    var dismiss = document.getElementById('wxn-banner-dismiss');
                    if (dismiss) {
                        dismiss.addEventListener('click', function() {
                            var banner = document.getElementById('wxn-sitewide-banner');
                            if (banner) banner.style.display = 'none';
                            document.body.style.paddingTop = '';
                        });
                    }
                });
            })();
            </script>
        @endif
        @section('content')
            @yield('above-container')
            @yield('container')
            @yield('below-container')
        @show
        @section('scripts')
            {!! $asset->js('main.js') !!}
        @show
    </body>
</html>
