<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>{{ config('app.name', 'wolfXcore') }} - @yield('title')</title>
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
        <meta name="_token" content="{{ csrf_token() }}">

        <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
        <link rel="icon" type="image/png" href="/favicons/favicon-32x32.png" sizes="32x32">
        <link rel="icon" type="image/png" href="/favicons/favicon-16x16.png" sizes="16x16">
        <link rel="manifest" href="/favicons/manifest.json">
        <link rel="mask-icon" href="/favicons/safari-pinned-tab.svg" color="#bc6e3c">
        <link rel="shortcut icon" href="/favicons/favicon.ico">
        <meta name="msapplication-config" content="/favicons/browserconfig.xml">
        <meta name="theme-color" content="#00ff00">

        @php
            $wxnLogo = \Pterodactyl\Http\Controllers\Admin\SuperAdminController::getSiteLogo();
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

        @include('layouts.scripts')

        @section('scripts')
            {!! Theme::css('vendor/select2/select2.min.css?t={cache-version}') !!}
            {!! Theme::css('vendor/bootstrap/bootstrap.min.css?t={cache-version}') !!}
            {!! Theme::css('vendor/adminlte/admin.min.css?t={cache-version}') !!}
            {!! Theme::css('vendor/adminlte/colors/skin-blue.min.css?t={cache-version}') !!}
            {!! Theme::css('vendor/sweetalert/sweetalert.min.css?t={cache-version}') !!}
            {!! Theme::css('vendor/animate/animate.min.css?t={cache-version}') !!}
            {!! Theme::css('css/wolfxcore.css?t={cache-version}') !!}
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=JetBrains+Mono:wght@300;400;600;700&display=swap">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ionicons/2.0.1/css/ionicons.min.css">
            <link rel="stylesheet" href="/css/wolfxnode.css">
            {!! Theme::css('css/wolfxnode-admin.css?t={cache-version}') !!}

            <!--[if lt IE 9]>
            <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
            <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
            <![endif]-->

            <style>
                /* Force dark Select2 dropdown — overrides select2.min.css white background */
                .select2-dropdown,
                .select2-dropdown.select2-dropdown--below,
                .select2-dropdown.select2-dropdown--above {
                    background-color: #040f04 !important;
                    background: #040f04 !important;
                    border: 1px solid rgba(0,255,0,0.25) !important;
                    border-radius: 3px !important;
                    box-shadow: 0 8px 32px rgba(0,0,0,0.8), 0 0 0 1px rgba(0,255,0,0.1) !important;
                }
                .select2-results,
                .select2-results__options {
                    background-color: #040f04 !important;
                    background: #040f04 !important;
                }
                .select2-results__option {
                    background-color: #040f04 !important;
                    background: #040f04 !important;
                    color: rgba(200,255,200,0.8) !important;
                    font-family: 'JetBrains Mono', monospace !important;
                    font-size: 0.82rem !important;
                    padding: 8px 12px !important;
                    border-bottom: 1px solid rgba(0,255,0,0.05) !important;
                    transition: background 0.1s !important;
                }
                .select2-results__option:last-child {
                    border-bottom: none !important;
                }
                .select2-results__option--highlighted,
                .select2-results__option--highlighted[aria-selected] {
                    background-color: rgba(0,255,0,0.12) !important;
                    background: rgba(0,255,0,0.12) !important;
                    color: #00ff00 !important;
                }
                .select2-results__option[aria-selected=true] {
                    background-color: rgba(0,255,0,0.08) !important;
                    background: rgba(0,255,0,0.08) !important;
                    color: #00ff00 !important;
                }
                .select2-search--dropdown {
                    background-color: #040f04 !important;
                    background: #040f04 !important;
                    padding: 6px !important;
                    border-bottom: 1px solid rgba(0,255,0,0.1) !important;
                }
                .select2-search--dropdown .select2-search__field {
                    background-color: rgba(0,30,0,0.8) !important;
                    background: rgba(0,30,0,0.8) !important;
                    border: 1px solid rgba(0,255,0,0.2) !important;
                    color: #00ff00 !important;
                    font-family: 'JetBrains Mono', monospace !important;
                    font-size: 0.82rem !important;
                    border-radius: 2px !important;
                    outline: none !important;
                }
                .select2-search--dropdown .select2-search__field:focus {
                    border-color: #00ff00 !important;
                    box-shadow: 0 0 0 2px rgba(0,255,0,0.1) !important;
                }
            </style>
        @show

        {{-- Select2 dark theme — outside @section so it ALWAYS renders --}}
        <style id="wxn-select2-dark">
            .select2-container .select2-dropdown,
            .select2-dropdown,
            .select2-dropdown--below,
            .select2-dropdown--above,
            .select2-container--default .select2-dropdown {
                background-color: #030e03 !important;
                background: #030e03 !important;
                border: 1px solid rgba(0,255,0,0.3) !important;
                border-radius: 4px !important;
                box-shadow: 0 6px 24px rgba(0,0,0,0.9), 0 0 0 1px rgba(0,255,0,0.08) !important;
            }
            .select2-results { background-color: #030e03 !important; background: #030e03 !important; }
            .select2-results__options { background-color: #030e03 !important; background: #030e03 !important; }
            .select2-results__option {
                background-color: #030e03 !important;
                background: #030e03 !important;
                color: rgba(180,255,180,0.85) !important;
                font-family: 'JetBrains Mono', monospace !important;
                font-size: 0.82rem !important;
                padding: 8px 12px !important;
                border-bottom: 1px solid rgba(0,255,0,0.05) !important;
            }
            .select2-results__option--highlighted[aria-selected],
            .select2-results__option--highlighted {
                background-color: rgba(0,255,0,0.15) !important;
                background: rgba(0,255,0,0.15) !important;
                color: #00ff00 !important;
            }
            .select2-results__option[aria-selected="true"] {
                background-color: rgba(0,255,0,0.1) !important;
                background: rgba(0,255,0,0.1) !important;
                color: #00ff00 !important;
            }
            .select2-search--dropdown {
                background-color: #030e03 !important;
                background: #030e03 !important;
                border-bottom: 1px solid rgba(0,255,0,0.12) !important;
                padding: 6px !important;
            }
            .select2-search--dropdown .select2-search__field {
                background-color: #071207 !important;
                background: #071207 !important;
                border: 1px solid rgba(0,255,0,0.25) !important;
                color: #00ff00 !important;
                font-family: 'JetBrains Mono', monospace !important;
                outline: none !important;
                border-radius: 2px !important;
            }
            .select2-search--dropdown .select2-search__field:focus {
                border-color: #00ff00 !important;
                box-shadow: 0 0 0 2px rgba(0,255,0,0.12) !important;
            }
        </style>
        @php
            $wxnThemeCss  = \Pterodactyl\Http\Controllers\Admin\SuperAdminController::getThemeCssBlock();
            $wxnCustomCss = \Pterodactyl\Http\Controllers\Admin\SuperAdminController::getCustomCss();
            $wxnThemeRaw  = \Pterodactyl\Http\Controllers\Admin\SuperAdminController::getAllThemeSettings();
        @endphp
        <style id="wxn-theme-vars">{!! $wxnThemeCss !!}</style>
        @if(!empty($wxnCustomCss))<style id="wxn-custom-css">{{ $wxnCustomCss }}</style>@endif
    </head>
    <body class="hold-transition skin-blue fixed sidebar-mini">
        <div class="wxn-bg-grid" @if(($wxnThemeRaw['grid_enable'] ?? '1') === '0') data-disabled="1" @endif></div>
        <div class="wxn-bg-scan" @if(($wxnThemeRaw['scan_enable'] ?? '1') === '0') data-disabled="1" @endif></div>
        <div class="wxn-corner wxn-corner-tl"></div>
        <div class="wxn-corner wxn-corner-tr"></div>
        <div class="wxn-corner wxn-corner-bl"></div>
        <div class="wxn-corner wxn-corner-br"></div>
        @php
            $wxnAnnouncement = \Pterodactyl\Http\Controllers\Admin\SuperAdminController::getAnnouncement();
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
                    var h = banner.offsetHeight;
                    var header  = document.querySelector('.main-header');
                    var sidebar = document.querySelector('.main-sidebar');
                    var content = document.querySelector('.content-wrapper');
                    var footer  = document.querySelector('.main-footer');
                    if (header)  header.style.top         = h + 'px';
                    if (sidebar) sidebar.style.paddingTop  = (50 + h) + 'px';
                    if (content) content.style.paddingTop  = h + 'px';
                    if (footer)  footer.style.marginLeft   = '0';
                }
                function wxnRemoveBannerOffset() {
                    var header  = document.querySelector('.main-header');
                    var sidebar = document.querySelector('.main-sidebar');
                    var content = document.querySelector('.content-wrapper');
                    if (header)  header.style.top        = '';
                    if (sidebar) sidebar.style.paddingTop = '';
                    if (content) content.style.paddingTop = '';
                }
                document.addEventListener('DOMContentLoaded', function() {
                    wxnApplyBannerOffset();
                    var dismiss = document.getElementById('wxn-banner-dismiss');
                    if (dismiss) {
                        dismiss.addEventListener('click', function() {
                            var banner = document.getElementById('wxn-sitewide-banner');
                            if (banner) banner.style.display = 'none';
                            wxnRemoveBannerOffset();
                        });
                    }
                });
            })();
            </script>
        @endif
        <div class="wrapper">
            <header class="main-header">
                <a href="{{ route('index') }}" class="logo">
                    <span style="font-family:'Orbitron',monospace;text-transform:uppercase;letter-spacing:0.06em;">
                        wolf<strong style="color:#00ff00;text-shadow:0 0 8px #00ff00;">X</strong>core
                    </span>
                </a>
                <nav class="navbar navbar-static-top">
                    <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </a>
                    <div class="navbar-custom-menu">
                        <ul class="nav navbar-nav">
                            <li class="user-menu">
                                <a href="{{ route('account') }}">
                                    <img src="https://www.gravatar.com/avatar/{{ Auth::check() ? md5(strtolower(Auth::user()->email)) : '0' }}?s=160" class="user-image" alt="User Image">
                                    <span class="hidden-xs">{{ Auth::check() ? Auth::user()->name_first . ' ' . Auth::user()->name_last : 'Super Admin' }}</span>
                                </a>
                            </li>
                            <li>
                                <li><a href="{{ route('index') }}" data-toggle="tooltip" data-placement="bottom" title="Exit Admin Control"><i class="fa fa-server"></i></a></li>
                            </li>
                            <li>
                                <li><a href="{{ route('auth.logout') }}" id="logoutButton" data-toggle="tooltip" data-placement="bottom" title="Logout"><i class="fa fa-sign-out"></i></a></li>
                            </li>
                        </ul>
                    </div>
                </nav>
            </header>
            <aside class="main-sidebar">
                <section class="sidebar">
                    <ul class="sidebar-menu">
                        <li class="header">BASIC ADMINISTRATION</li>
                        <li class="{{ Route::currentRouteName() !== 'admin.index' ?: 'active' }}">
                            <a href="{{ route('admin.index') }}">
                                <i class="fa fa-home"></i> <span>Overview</span>
                            </a>
                        </li>
                        <li class="{{ ! starts_with(Route::currentRouteName(), 'admin.settings') ?: 'active' }}">
                            <a href="{{ route('admin.settings')}}">
                                <i class="fa fa-wrench"></i> <span>Settings</span>
                            </a>
                        </li>
                        <li class="{{ ! starts_with(Route::currentRouteName(), 'admin.api') ?: 'active' }}">
                            <a href="{{ route('admin.api.index')}}">
                                <i class="fa fa-gamepad"></i> <span>Application API</span>
                            </a>
                        </li>
                        <li class="header">MANAGEMENT</li>
                        <li class="{{ ! starts_with(Route::currentRouteName(), 'admin.databases') ?: 'active' }}">
                            <a href="{{ route('admin.databases') }}">
                                <i class="fa fa-database"></i> <span>Databases</span>
                            </a>
                        </li>
                        <li class="{{ ! starts_with(Route::currentRouteName(), 'admin.locations') ?: 'active' }}">
                            <a href="{{ route('admin.locations') }}">
                                <i class="fa fa-globe"></i> <span>Locations</span>
                            </a>
                        </li>
                        <li class="{{ ! starts_with(Route::currentRouteName(), 'admin.nodes') ?: 'active' }}">
                            <a href="{{ route('admin.nodes') }}">
                                <i class="fa fa-sitemap"></i> <span>Nodes</span>
                            </a>
                        </li>
                        <li class="{{ ! starts_with(Route::currentRouteName(), 'admin.servers') ?: 'active' }}">
                            <a href="{{ route('admin.servers') }}">
                                <i class="fa fa-server"></i> <span>Servers</span>
                            </a>
                        </li>
                        <li class="{{ ! starts_with(Route::currentRouteName(), 'admin.users') ?: 'active' }}">
                            <a href="{{ route('admin.users') }}">
                                <i class="fa fa-users"></i> <span>Users</span>
                            </a>
                        </li>
                        @if(session('wxn_super'))
                        <li class="{{ ! starts_with(Route::currentRouteName(), 'admin.plans') ?: 'active' }}">
                            <a href="{{ route('admin.plans') }}">
                                <i class="fa fa-tag"></i> <span>Plans &amp; Pricing</span>
                            </a>
                        </li>
                        @if(session('wxn_super'))
                        <li class="{{ ! starts_with(Route::currentRouteName(), 'admin.bots') ?: 'active' }}">
                            <a href="{{ route('admin.bots') }}">
                                <i class="fa fa-robot"></i> <span>Bot Repos</span>
                            </a>
                        </li>
                        @endif
                        @endif
                        <li class="header">SERVICE MANAGEMENT</li>
                        <li class="{{ ! starts_with(Route::currentRouteName(), 'admin.mounts') ?: 'active' }}">
                            <a href="{{ route('admin.mounts') }}">
                                <i class="fa fa-magic"></i> <span>Mounts</span>
                            </a>
                        </li>
                        <li class="{{ ! starts_with(Route::currentRouteName(), 'admin.nests') ?: 'active' }}">
                            <a href="{{ route('admin.nests') }}">
                                <i class="fa fa-th-large"></i> <span>Nests</span>
                            </a>
                        </li>
                    </ul>
                </section>
            </aside>
            <div class="content-wrapper">
                <section class="content-header">
                    @yield('content-header')
                </section>
                <section class="content">
                    <div class="row">
                        <div class="col-xs-12">
                            @if (count($errors) > 0)
                                <div class="alert alert-danger">
                                    There was an error validating the data provided.<br><br>
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            @foreach (Alert::getMessages() as $type => $messages)
                                @foreach ($messages as $message)
                                    <div class="alert alert-{{ $type }} alert-dismissable" role="alert">
                                        {{ $message }}
                                    </div>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                    @yield('content')
                </section>
            </div>
            <footer class="main-footer">
                <div class="pull-right small" style="margin-right:10px;margin-top:-7px;color:#4d994d;">
                    <strong><i class="fa fa-fw {{ $appIsGit ? 'fa-git-square' : 'fa-code-fork' }}"></i></strong> {{ $appVersion }}<br />
                    <strong><i class="fa fa-fw fa-clock-o"></i></strong> {{ round(microtime(true) - LARAVEL_START, 3) }}s
                </div>
                &copy; 2025 – {{ date('Y') }} <strong style="color:#ffffff;">wolf<span style="color:#00e676;">X</span>core</strong> &nbsp;&middot;&nbsp; Powered by <span style="color:rgba(255,255,255,0.35);">WOLF TECH</span>
            </footer>
        </div>
        @section('footer-scripts')
            <script src="/js/keyboard.polyfill.js" type="application/javascript"></script>
            <script>keyboardeventKeyPolyfill.polyfill();</script>

            {!! Theme::js('vendor/jquery/jquery.min.js?t={cache-version}') !!}
            {!! Theme::js('vendor/sweetalert/sweetalert.min.js?t={cache-version}') !!}
            {!! Theme::js('vendor/bootstrap/bootstrap.min.js?t={cache-version}') !!}
            {!! Theme::js('vendor/slimscroll/jquery.slimscroll.min.js?t={cache-version}') !!}
            {!! Theme::js('vendor/adminlte/app.min.js?t={cache-version}') !!}
            {!! Theme::js('vendor/bootstrap-notify/bootstrap-notify.min.js?t={cache-version}') !!}
            {!! Theme::js('vendor/select2/select2.full.min.js?t={cache-version}') !!}
            {!! Theme::js('js/admin/functions.js?t={cache-version}') !!}
            <script src="/js/autocomplete.js" type="application/javascript"></script>

            @if(Auth::check() && Auth::user()->root_admin)
                <script>
                    $('#logoutButton').on('click', function (event) {
                        event.preventDefault();

                        var that = this;
                        swal({
                            title: 'Do you want to log out?',
                            type: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d9534f',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Log out'
                        }, function () {
                             $.ajax({
                                type: 'POST',
                                url: '{{ route('auth.logout') }}',
                                data: {
                                    _token: '{{ csrf_token() }}'
                                },complete: function () {
                                    window.location.href = '{{route('auth.login')}}';
                                }
                        });
                    });
                });
                </script>
            @endif

            <script>
                $(function () {
                    $('[data-toggle="tooltip"]').tooltip();

                    // Fix: move modals to <body> so AdminLTE transforms don't break position:fixed
                    $(document).on('show.bs.modal', '.modal', function () {
                        var $modal = $(this);
                        if ($modal.parent()[0] !== document.body) {
                            $modal.appendTo('body');
                        }
                        $('.main-sidebar, .left-side, .main-header').css('z-index', '1');
                    });
                    $(document).on('hidden.bs.modal', '.modal', function () {
                        $('.main-sidebar, .left-side').css('z-index', '');
                        $('.main-header').css('z-index', '');
                    });
                });
            </script>
        @show
    </body>
</html>
