@extends('layouts.admin')

@section('title')
    Super Admin Panel
@endsection

@section('content-header')
    <h1>
        Super Admin Panel
        <small style="color:var(--wxn-neon);font-family:'Orbitron',monospace;">Owner Control</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Super Admin</li>
    </ol>
@endsection

@section('content')

{{-- Status Bar --}}
<div class="row">
    <div class="col-xs-12">
        <div style="background:rgba(0,255,0,0.06);border:1px solid rgba(0,255,0,0.25);padding:12px 18px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
            <div style="font-family:'JetBrains Mono',monospace;font-size:0.8rem;color:var(--wxn-neon);">
                <i class="fa fa-shield"></i>&nbsp;
                <span style="color:rgba(0,255,0,0.6);">SUPER ADMIN SESSION ACTIVE</span>
                &nbsp;|&nbsp; Logged in as <strong>{{ Auth::check() ? Auth::user()->username : 'Owner' }}</strong>
                &nbsp;|&nbsp; <span style="color:rgba(0,255,0,0.5);font-size:0.75rem;">Since: {{ session('wxn_super_at') }}</span>
            </div>
            <form action="{{ route('admin.super.logout') }}" method="POST" style="margin:0;">
                @csrf
                <button type="submit" class="btn btn-sm btn-danger" style="font-family:'JetBrains Mono',monospace;font-size:0.75rem;letter-spacing:1px;">
                    <i class="fa fa-sign-out"></i> EXIT SUPER ADMIN
                </button>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     MAINTENANCE MODE TOGGLE
═══════════════════════════════════════════════════════════ --}}
<div class="row">
    <div class="col-xs-12">
        <div class="box wxn-box" style="border-color:{{ $maintenanceOn ? 'rgba(255,100,0,0.55)' : 'rgba(0,255,0,0.25)' }};">
            <div class="box-header wxn-box-header" style="background:{{ $maintenanceOn ? 'rgba(255,80,0,0.08)' : '' }};">
                <h3 class="box-title" style="color:{{ $maintenanceOn ? '#ff8800' : 'var(--wxn-neon)' }};">
                    <i class="fa fa-wrench"></i>
                    MAINTENANCE MODE
                    &nbsp;
                    <span style="font-size:0.7rem;padding:3px 10px;border-radius:3px;font-family:'JetBrains Mono',monospace;letter-spacing:1px;
                        background:{{ $maintenanceOn ? 'rgba(255,80,0,0.18)' : 'rgba(0,255,0,0.12)' }};
                        color:{{ $maintenanceOn ? '#ff8800' : '#00ff00' }};
                        border:1px solid {{ $maintenanceOn ? 'rgba(255,80,0,0.4)' : 'rgba(0,255,0,0.3)' }};">
                        {{ $maintenanceOn ? '● ACTIVE' : '○ INACTIVE' }}
                    </span>
                </h3>
            </div>
            <div class="box-body" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;padding:18px 20px;">
                <div style="font-family:'JetBrains Mono',monospace;font-size:0.82rem;color:rgba(255,255,255,0.6);line-height:1.6;">
                    @if($maintenanceOn)
                        <span style="color:#ff8800;font-weight:bold;">⚠ Site is currently in maintenance mode.</span><br>
                        All non-admin visitors see the maintenance page. You and other admins can still access everything normally.
                    @else
                        Site is <span style="color:#00ff00;">online</span> and accessible to all users.<br>
                        Enable maintenance mode to show visitors a "Under Maintenance" page while you work.
                    @endif
                </div>
                <form action="{{ route('admin.super.maintenance') }}" method="POST" style="margin:0;flex-shrink:0;">
                    @csrf
                    <button type="submit"
                        onclick="return confirm('{{ $maintenanceOn ? 'Bring the site back online?' : 'Put site into maintenance mode? Users will be blocked.' }}')"
                        style="font-family:'Orbitron',monospace;font-size:0.68rem;letter-spacing:2px;font-weight:700;
                            padding:10px 26px;border-radius:5px;border:none;cursor:pointer;transition:opacity .2s;
                            background:{{ $maintenanceOn ? '#00ff00' : '#ff6600' }};
                            color:{{ $maintenanceOn ? '#000' : '#fff' }};">
                        <i class="fa fa-{{ $maintenanceOn ? 'check-circle' : 'wrench' }}"></i>
                        {{ $maintenanceOn ? 'BRING ONLINE' : 'ENABLE MAINTENANCE' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     SITEWIDE ANNOUNCEMENT
═══════════════════════════════════════════════════════════ --}}
<div class="row">
    <div class="col-xs-12">
        <div class="box wxn-box">
            <div class="box-header wxn-box-header">
                <h3 class="box-title" style="color:var(--wxn-neon);">
                    <i class="fa fa-bullhorn"></i> Sitewide Announcement
                </h3>
            </div>
            <div class="box-body" style="padding:20px;">
                <p style="font-family:'JetBrains Mono',monospace;font-size:0.8rem;color:rgba(255,255,255,0.45);margin-bottom:18px;">
                    Show a banner message to all users across the entire site.
                </p>
                <form action="{{ route('admin.super.announcement') }}" method="POST">
                    @csrf

                    {{-- Active toggle + Type selector row --}}
                    <div style="display:flex;align-items:center;gap:22px;margin-bottom:16px;flex-wrap:wrap;">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;margin:0;">
                            <span class="wxn-label" style="margin:0;">Active</span>
                            <div class="wxn-toggle-wrap" style="position:relative;width:44px;height:24px;">
                                <input type="checkbox" name="announcement_active" id="ann-active-toggle"
                                    {{ $announcementActive === '1' ? 'checked' : '' }}
                                    style="opacity:0;position:absolute;width:100%;height:100%;margin:0;cursor:pointer;z-index:2;">
                                <div id="ann-toggle-track" style="
                                    position:absolute;inset:0;border-radius:12px;transition:background .2s;
                                    background:{{ $announcementActive === '1' ? '#00ff00' : 'rgba(255,255,255,0.1)' }};
                                    border:1px solid {{ $announcementActive === '1' ? '#00ff00' : 'rgba(255,255,255,0.2)' }};
                                ">
                                    <div id="ann-toggle-knob" style="
                                        position:absolute;top:2px;border-radius:50%;width:18px;height:18px;transition:left .2s,background .2s;
                                        left:{{ $announcementActive === '1' ? '22px' : '2px' }};
                                        background:{{ $announcementActive === '1' ? '#000' : 'rgba(255,255,255,0.5)' }};
                                    "></div>
                                </div>
                            </div>
                        </label>

                        <div style="display:flex;align-items:center;gap:8px;">
                            <select name="announcement_type" id="ann-type-select" style="
                                background:#0a150a;border:1px solid rgba(0,255,0,0.3);color:#00ff00;
                                font-family:'JetBrains Mono',monospace;font-size:0.8rem;padding:6px 10px;
                                border-radius:3px;cursor:pointer;outline:none;
                            ">
                                @foreach(['success'=>'Success (green)','info'=>'Info (blue)','warning'=>'Warning (orange)','danger'=>'Danger (red)'] as $val=>$label)
                                    <option value="{{ $val }}" {{ $announcementType === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Message textarea --}}
                    <textarea name="announcement_text" rows="3" placeholder="Enter announcement message..." style="
                        width:100%;background:#0a150a;border:1px solid rgba(0,255,0,0.3);color:#b0ffb0;
                        font-family:'JetBrains Mono',monospace;font-size:0.85rem;padding:12px;
                        border-radius:4px;outline:none;resize:vertical;min-height:80px;margin-bottom:14px;
                        display:block;box-sizing:border-box;
                    ">{{ $announcementText }}</textarea>

                    {{-- Save button --}}
                    <button type="submit" style="
                        background:rgba(0,255,0,0.12);border:1px solid rgba(0,255,0,0.5);color:#00ff00;
                        font-family:'JetBrains Mono',monospace;font-size:0.78rem;letter-spacing:1px;
                        padding:9px 20px;border-radius:4px;cursor:pointer;transition:all .18s;
                    " onmouseover="this.style.background='rgba(0,255,0,0.22)'" onmouseout="this.style.background='rgba(0,255,0,0.12)'">
                        <i class="fa fa-save"></i> Save Announcement
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     REPOSITORY CLONE FEATURE
═══════════════════════════════════════════════════════════ --}}
@php
    $repoCloneEnabled   = \wolfXcore\Models\Setting::where('key', \wolfXcore\Services\Files\RepoCloneService::SETTING_ENABLED)->value('value') ?? '1';
    $repoCloneAllowlist = \wolfXcore\Models\Setting::where('key', \wolfXcore\Services\Files\RepoCloneService::SETTING_ALLOWLIST)->value('value') ?? \wolfXcore\Services\Files\RepoCloneService::DEFAULT_ALLOWLIST;
@endphp
<div class="row">
    <div class="col-xs-12">
        <div class="box wxn-box">
            <div class="box-header wxn-box-header">
                <h3 class="box-title" style="color:var(--wxn-neon);">
                    <i class="fa fa-code-fork"></i> Clone From Repository
                </h3>
                <div class="box-tools" style="margin-top:2px;">
                    <span style="font-family:'JetBrains Mono',monospace;font-size:0.67rem;color:rgba(255,255,255,0.4);">
                        Lets users paste a public Git URL on their server's file manager to import code
                    </span>
                </div>
            </div>
            <div class="box-body" style="padding:20px;">
                <p style="font-family:'JetBrains Mono',monospace;font-size:0.8rem;color:rgba(255,255,255,0.45);margin-bottom:18px;">
                    When enabled, a "Clone from Repo" button appears on every server's file manager. Users paste a public repo URL (GitHub / GitLab / Bitbucket), the system fetches the archive of the chosen branch, and extracts it into the current directory. Disable this if it's being abused or causing load.
                </p>
                <form action="{{ route('admin.super.repo-clone.save') }}" method="POST">
                    @csrf

                    <div style="display:flex;align-items:center;gap:22px;margin-bottom:18px;flex-wrap:wrap;">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;margin:0;">
                            <span class="wxn-label" style="margin:0;">Allow users to clone public repos</span>
                            <div style="position:relative;width:44px;height:24px;">
                                <input type="checkbox" name="repo_clone_enabled" id="repo-clone-toggle"
                                    {{ $repoCloneEnabled === '1' ? 'checked' : '' }}
                                    style="opacity:0;position:absolute;width:100%;height:100%;margin:0;cursor:pointer;z-index:2;">
                                <div id="repo-clone-track" style="
                                    position:absolute;inset:0;border-radius:12px;transition:background .2s;
                                    background:{{ $repoCloneEnabled === '1' ? '#00ff00' : 'rgba(255,255,255,0.1)' }};
                                    border:1px solid {{ $repoCloneEnabled === '1' ? '#00ff00' : 'rgba(255,255,255,0.2)' }};
                                ">
                                    <div id="repo-clone-knob" style="
                                        position:absolute;top:2px;border-radius:50%;width:18px;height:18px;transition:left .2s,background .2s;
                                        left:{{ $repoCloneEnabled === '1' ? '22px' : '2px' }};
                                        background:{{ $repoCloneEnabled === '1' ? '#000' : 'rgba(255,255,255,0.5)' }};
                                    "></div>
                                </div>
                            </div>
                        </label>
                    </div>

                    <label class="wxn-label" style="display:block;margin-bottom:6px;">Allowed hosts (comma-separated)</label>
                    <input type="text" name="repo_clone_allowlist" value="{{ $repoCloneAllowlist }}"
                        placeholder="github.com, gitlab.com, bitbucket.org" style="
                        width:100%;background:#0a150a;border:1px solid rgba(0,255,0,0.3);color:#b0ffb0;
                        font-family:'JetBrains Mono',monospace;font-size:0.85rem;padding:10px 12px;
                        border-radius:4px;outline:none;box-sizing:border-box;margin-bottom:6px;">
                    <p style="font-family:'JetBrains Mono',monospace;font-size:0.7rem;color:rgba(255,255,255,0.4);margin-bottom:14px;">
                        Only these hosts can be cloned from. Currently supported with archive download: <code>github.com</code>, <code>gitlab.com</code>, <code>bitbucket.org</code>. Any host not in the supported list will be rejected even if you add it here.
                    </p>

                    <button type="submit" style="
                        background:rgba(0,255,0,0.12);border:1px solid rgba(0,255,0,0.5);color:#00ff00;
                        font-family:'JetBrains Mono',monospace;font-size:0.78rem;letter-spacing:1px;
                        padding:9px 20px;border-radius:4px;cursor:pointer;transition:all .18s;
                    " onmouseover="this.style.background='rgba(0,255,0,0.22)'" onmouseout="this.style.background='rgba(0,255,0,0.12)'">
                        <i class="fa fa-save"></i> Save Repo Clone Settings
                    </button>
                </form>

                <script>
                (function(){
                    var t = document.getElementById('repo-clone-toggle');
                    var trk = document.getElementById('repo-clone-track');
                    var knb = document.getElementById('repo-clone-knob');
                    if (!t || !trk || !knb) return;
                    t.addEventListener('change', function(){
                        if (t.checked) {
                            trk.style.background = '#00ff00';
                            trk.style.borderColor = '#00ff00';
                            knb.style.left = '22px';
                            knb.style.background = '#000';
                        } else {
                            trk.style.background = 'rgba(255,255,255,0.1)';
                            trk.style.borderColor = 'rgba(255,255,255,0.2)';
                            knb.style.left = '2px';
                            knb.style.background = 'rgba(255,255,255,0.5)';
                        }
                    });
                })();
                </script>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════ CHRISTMAS THEME ═══════════════════════════════
═══════════════════════════════════════════════════════════ --}}
<div class="row">
    <div class="col-xs-12">
        <div class="box wxn-box" style="border:1px solid rgba(220,20,20,0.3);background:linear-gradient(135deg,rgba(8,20,8,0.97),rgba(20,5,5,0.97));">
            <div class="box-header wxn-box-header" style="background:linear-gradient(90deg,rgba(180,0,0,0.12),rgba(0,120,0,0.12));">
                <h3 class="box-title" style="color:#ff4444;">
                    🎄 Christmas Theme
                </h3>
                <div class="box-tools" style="margin-top:2px;">
                    <span style="font-family:'JetBrains Mono',monospace;font-size:0.67rem;color:rgba(255,120,120,0.6);">
                        Auto-activates Nov 25 → Dec 31 · Snow · Reindeers · Jingle Bells
                    </span>
                </div>
            </div>
            <div class="box-body" style="padding:20px;">
                <p style="font-family:'JetBrains Mono',monospace;font-size:0.8rem;color:rgba(255,255,255,0.45);margin-bottom:18px;">
                    Controls the festive Christmas overlay: falling snow ❄, flying reindeers 🦌🛷🎅, string lights, jingle bells 🔔, and a <strong style="color:#ff8888;">Merry Christmas</strong> popup after every server deployment.
                </p>

                @php $xmasMode = \wolfXcore\Models\Setting::where('key','settings::christmas:mode')->value('value') ?? 'auto'; @endphp

                <form action="{{ route('admin.super.christmas.save') }}" method="POST">
                    @csrf
                    <div style="display:flex;align-items:center;gap:18px;flex-wrap:wrap;margin-bottom:20px;">

                        {{-- ON button --}}
                        <label style="cursor:pointer;display:flex;align-items:center;gap:8px;
                            padding:10px 20px;border-radius:6px;border:2px solid {{ $xmasMode==='on' ? '#ff4444' : 'rgba(255,68,68,0.25)' }};
                            background:{{ $xmasMode==='on' ? 'rgba(255,40,40,0.15)' : 'transparent' }};
                            transition:all .2s;">
                            <input type="radio" name="christmas_mode" value="on" {{ $xmasMode==='on' ? 'checked' : '' }} style="display:none;">
                            <span style="font-size:1.4rem;">🎄</span>
                            <div>
                                <div style="font-family:'Orbitron',monospace;font-size:0.72rem;color:{{ $xmasMode==='on' ? '#ff6666' : 'rgba(255,255,255,0.5)' }};font-weight:700;letter-spacing:1px;">ALWAYS ON</div>
                                <div style="font-family:'JetBrains Mono',monospace;font-size:0.62rem;color:rgba(255,255,255,0.3);">Force enable year-round</div>
                            </div>
                        </label>

                        {{-- AUTO button --}}
                        <label style="cursor:pointer;display:flex;align-items:center;gap:8px;
                            padding:10px 20px;border-radius:6px;border:2px solid {{ $xmasMode==='auto' ? '#00ff00' : 'rgba(0,255,0,0.2)' }};
                            background:{{ $xmasMode==='auto' ? 'rgba(0,255,0,0.1)' : 'transparent' }};
                            transition:all .2s;">
                            <input type="radio" name="christmas_mode" value="auto" {{ $xmasMode==='auto' ? 'checked' : '' }} style="display:none;">
                            <span style="font-size:1.4rem;">🗓️</span>
                            <div>
                                <div style="font-family:'Orbitron',monospace;font-size:0.72rem;color:{{ $xmasMode==='auto' ? '#00ff00' : 'rgba(255,255,255,0.5)' }};font-weight:700;letter-spacing:1px;">AUTO SEASON</div>
                                <div style="font-family:'JetBrains Mono',monospace;font-size:0.62rem;color:rgba(255,255,255,0.3);">Nov 25 – Dec 31 only</div>
                            </div>
                        </label>

                        {{-- OFF button --}}
                        <label style="cursor:pointer;display:flex;align-items:center;gap:8px;
                            padding:10px 20px;border-radius:6px;border:2px solid {{ $xmasMode==='off' ? 'rgba(120,120,120,0.8)' : 'rgba(120,120,120,0.2)' }};
                            background:{{ $xmasMode==='off' ? 'rgba(120,120,120,0.1)' : 'transparent' }};
                            transition:all .2s;">
                            <input type="radio" name="christmas_mode" value="off" {{ $xmasMode==='off' ? 'checked' : '' }} style="display:none;">
                            <span style="font-size:1.4rem;">🚫</span>
                            <div>
                                <div style="font-family:'Orbitron',monospace;font-size:0.72rem;color:{{ $xmasMode==='off' ? 'rgba(180,180,180,0.8)' : 'rgba(255,255,255,0.5)' }};font-weight:700;letter-spacing:1px;">ALWAYS OFF</div>
                                <div style="font-family:'JetBrains Mono',monospace;font-size:0.62rem;color:rgba(255,255,255,0.3);">Disable all Christmas effects</div>
                            </div>
                        </label>

                    </div>

                    {{-- Current status indicator --}}
                    <div style="font-family:'JetBrains Mono',monospace;font-size:0.72rem;color:rgba(255,255,255,0.35);margin-bottom:16px;">
                        @php
                            $now = new \DateTime();
                            $m = (int)$now->format('n');
                            $d = (int)$now->format('j');
                            $inSeason = ($m === 11 && $d >= 25) || $m === 12;
                        @endphp
                        Current date: <span style="color:rgba(0,255,0,0.6);">{{ $now->format('M d, Y') }}</span>
                        &nbsp;·&nbsp;
                        Season status: <span style="color:{{ $inSeason ? '#ff6666' : 'rgba(255,255,255,0.35)' }};">{{ $inSeason ? '🎄 IN SEASON' : '❄ Off-season' }}</span>
                        &nbsp;·&nbsp;
                        Active now: <span style="color:{{ ($xmasMode==='on' || ($xmasMode==='auto' && $inSeason)) ? '#ff6666' : 'rgba(100,100,100,0.8)' }};">
                            {{ ($xmasMode==='on' || ($xmasMode==='auto' && $inSeason)) ? 'YES 🎅' : 'NO' }}
                        </span>
                    </div>

                    <button type="submit" style="
                        background:linear-gradient(135deg,rgba(180,0,0,0.3),rgba(0,120,0,0.3));
                        border:1px solid rgba(180,0,0,0.4);color:#ff9999;
                        font-family:'JetBrains Mono',monospace;font-size:0.78rem;letter-spacing:1px;
                        padding:9px 20px;border-radius:4px;cursor:pointer;transition:all .18s;
                    " onmouseover="this.style.background='linear-gradient(135deg,rgba(180,0,0,0.5),rgba(0,120,0,0.5))'"
                       onmouseout="this.style.background='linear-gradient(135deg,rgba(180,0,0,0.3),rgba(0,120,0,0.3))'">
                        🎄 Save Christmas Settings
                    </button>
                </form>

                {{-- Live radio button highlight via JS --}}
                <script>
                (function(){
                    var radios = document.querySelectorAll('input[name="christmas_mode"]');
                    radios.forEach(function(r){
                        r.parentElement.addEventListener('click', function(){
                            radios.forEach(function(rb){
                                var lbl = rb.parentElement;
                                lbl.style.borderColor='rgba(120,120,120,0.2)';
                                lbl.style.background='transparent';
                            });
                            r.checked=true;
                            var lbl=r.parentElement;
                            if(r.value==='on'){lbl.style.borderColor='#ff4444';lbl.style.background='rgba(255,40,40,0.15)';}
                            else if(r.value==='auto'){lbl.style.borderColor='#00ff00';lbl.style.background='rgba(0,255,0,0.1)';}
                            else{lbl.style.borderColor='rgba(120,120,120,0.8)';lbl.style.background='rgba(120,120,120,0.1)';}
                        });
                    });
                })();
                </script>
            </div>
        </div>
    </div>
</div>

{{-- Stat Cards --}}
<div class="row">
    @foreach([['fa-users','Admins',$admins->count()],['fa-user','Total Users',\wolfXcore\Models\User::count()],['fa-server','Servers',\wolfXcore\Models\Server::count()],['fa-sitemap','Nodes',\wolfXcore\Models\Node::count()]] as $stat)
    <div class="col-xs-6 col-sm-3">
        <div class="wxn-stat-box box">
            <div style="font-size:1.8rem;color:var(--wxn-neon);font-family:'Orbitron',monospace;">{{ $stat[2] }}</div>
            <div style="font-size:0.65rem;color:rgba(0,255,0,0.5);letter-spacing:2px;text-transform:uppercase;margin-top:4px;font-family:'JetBrains Mono',monospace;"><i class="fa {{ $stat[0] }}"></i> {{ $stat[1] }}</div>
        </div>
    </div>
    @endforeach
</div>

{{-- ═══════════════════════════════════════════════════════════
     MASTER THEME DESIGNER
═══════════════════════════════════════════════════════════ --}}
<div class="row">
    <div class="col-xs-12">
        <div class="box wxn-box">
            <div class="box-header wxn-box-header">
                <h3 class="box-title"><i class="fa fa-paint-brush"></i> THEME DESIGNER</h3>
                <div class="box-tools">
                    <span class="wxn-live-badge"><i class="fa fa-bolt"></i> LIVE — No rebuild needed</span>
                </div>
            </div>
            <div class="box-body" style="padding:20px;">
                <form action="{{ route('admin.super.theme') }}" method="POST" id="wxn-theme-form">
                    @csrf

                    {{-- Tab nav --}}
                    <div class="wxn-tab-nav" id="wxn-theme-tabs">
                        <button type="button" class="wxn-tnav active" data-target="t-page">
                            <i class="fa fa-desktop"></i> Page
                        </button>
                        <button type="button" class="wxn-tnav" data-target="t-nav">
                            <i class="fa fa-bars"></i> Nav Bar
                        </button>
                        <button type="button" class="wxn-tnav" data-target="t-sidebar">
                            <i class="fa fa-columns"></i> Sidebar
                        </button>
                        <button type="button" class="wxn-tnav" data-target="t-console">
                            <i class="fa fa-terminal"></i> Console
                        </button>
                        <button type="button" class="wxn-tnav" data-target="t-buttons">
                            <i class="fa fa-power-off"></i> Buttons
                        </button>
                        <button type="button" class="wxn-tnav" data-target="t-cards">
                            <i class="fa fa-th-large"></i> Cards
                        </button>
                        <button type="button" class="wxn-tnav" data-target="t-fonts">
                            <i class="fa fa-font"></i> Fonts
                        </button>
                        <button type="button" class="wxn-tnav" data-target="t-effects">
                            <i class="fa fa-magic"></i> Effects
                        </button>
                        <button type="button" class="wxn-tnav" data-target="t-css">
                            <i class="fa fa-code"></i> Custom CSS
                        </button>
                    </div>

                    {{-- ─── PAGE & BACKGROUND ─────────────────────────────── --}}
                    <div class="wxn-tpane active" id="t-page">
                        <div class="wxn-section-title"><i class="fa fa-desktop"></i> Page & Background</div>
                        <div class="wxn-grid-3">
                            <div>
                                <label class="wxn-label">ACCENT / NEON COLOR</label>
                                <div class="wxn-color-row">
                                    <input type="color" name="accent_color" value="{{ $theme['accent_color'] }}" class="wxn-color-pick" onchange="syncHex(this,'hex_accent')">
                                    <input type="text" id="hex_accent" value="{{ $theme['accent_color'] }}" placeholder="#00ff00" class="wxn-input wxn-color-hex" onchange="syncPicker(this,'accent_color')">
                                </div>
                                <div class="wxn-presets-row">
                                    @foreach([['#00ff00','Neon Green'],['#00ffff','Cyan'],['#ff00ff','Magenta'],['#ff6600','Orange'],['#ffff00','Yellow'],['#0099ff','Blue'],['#ff0055','Red'],['#cc44ff','Purple'],['#00ff88','Mint']] as $p)
                                    <button type="button" class="wxn-preset" data-color="{{ $p[0] }}" data-name="accent_color" data-hexid="hex_accent" title="{{ $p[1] }}" style="background:{{ $p[0] }};{{ $theme['accent_color']===$p[0] ? 'outline:2px solid #fff;' : '' }}"></button>
                                    @endforeach
                                </div>
                                <div class="wxn-hint">Applied to all neon glows, active states, links & accents sitewide.</div>
                            </div>
                            <div>
                                <label class="wxn-label">PAGE BACKGROUND</label>
                                <div class="wxn-color-row">
                                    <input type="color" name="page_bg" value="{{ substr($theme['page_bg'],0,7) }}" class="wxn-color-pick" onchange="syncHex(this,'hex_page_bg')">
                                    <input type="text" id="hex_page_bg" value="{{ $theme['page_bg'] }}" placeholder="#030a03" class="wxn-input wxn-color-hex" onchange="syncPicker(this,'page_bg')">
                                </div>
                                <div class="wxn-presets-row">
                                    @foreach([['#030a03','Dark Green'],['#030305','Dark Blue'],['#0a0305','Dark Red'],['#050505','Black'],['#0d0d0d','Dark'],['#0a0a14','Navy']] as $p)
                                    <button type="button" class="wxn-preset" data-color="{{ $p[0] }}" data-name="page_bg" data-hexid="hex_page_bg" title="{{ $p[1] }}" style="background:{{ $p[0] }};border:1px solid rgba(255,255,255,0.1);{{ $theme['page_bg']===$p[0] ? 'outline:2px solid #fff;' : '' }}"></button>
                                    @endforeach
                                </div>
                                <div class="wxn-hint">Main page background color.</div>
                            </div>
                        </div>
                    </div>

                    {{-- ─── NAVIGATION BAR ─────────────────────────────────── --}}
                    <div class="wxn-tpane" id="t-nav">
                        <div class="wxn-section-title"><i class="fa fa-bars"></i> Navigation Bar (Top Bar / Admin Header)</div>
                        <div class="wxn-grid-3">
                            <div>
                                <label class="wxn-label">NAV BACKGROUND</label>
                                <div class="wxn-color-row">
                                    <input type="color" name="nav_bg" value="{{ substr($theme['nav_bg'],0,7) }}" class="wxn-color-pick" onchange="syncHex(this,'hex_nav_bg')">
                                    <input type="text" id="hex_nav_bg" value="{{ $theme['nav_bg'] }}" placeholder="#000800" class="wxn-input wxn-color-hex" onchange="syncPicker(this,'nav_bg')">
                                </div>
                                <div class="wxn-presets-row">
                                    @foreach([['#000800','Dark Green'],['#000308','Dark Blue'],['#080000','Dark Red'],['#050505','Black'],['#0d0d0d','Charcoal']] as $p)
                                    <button type="button" class="wxn-preset" data-color="{{ $p[0] }}" data-name="nav_bg" data-hexid="hex_nav_bg" title="{{ $p[1] }}" style="background:{{ $p[0] }};border:1px solid rgba(255,255,255,0.1);{{ $theme['nav_bg']===$p[0] ? 'outline:2px solid #fff;' : '' }}"></button>
                                    @endforeach
                                </div>
                            </div>
                            <div>
                                <label class="wxn-label">NAV TEXT COLOR</label>
                                <div class="wxn-color-row">
                                    <input type="color" name="nav_text" value="{{ $theme['nav_text'] }}" class="wxn-color-pick" onchange="syncHex(this,'hex_nav_text')">
                                    <input type="text" id="hex_nav_text" value="{{ $theme['nav_text'] }}" placeholder="#ffffff" class="wxn-input wxn-color-hex" onchange="syncPicker(this,'nav_text')">
                                </div>
                                <div class="wxn-presets-row">
                                    @foreach([['#ffffff','White'],['#b0ffb0','Light Green'],['#b0e0ff','Light Blue'],['#ffd0b0','Light Orange']] as $p)
                                    <button type="button" class="wxn-preset" data-color="{{ $p[0] }}" data-name="nav_text" data-hexid="hex_nav_text" title="{{ $p[1] }}" style="background:{{ $p[0] }};{{ $theme['nav_text']===$p[0] ? 'outline:2px solid #fff;' : '' }}"></button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ─── SIDEBAR ─────────────────────────────────────────── --}}
                    <div class="wxn-tpane" id="t-sidebar">
                        <div class="wxn-section-title"><i class="fa fa-columns"></i> Sidebar</div>
                        <div class="wxn-grid-3">
                            <div>
                                <label class="wxn-label">SIDEBAR BACKGROUND</label>
                                <div class="wxn-color-row">
                                    <input type="color" name="sidebar_bg" value="{{ substr($theme['sidebar_bg'],0,7) }}" class="wxn-color-pick" onchange="syncHex(this,'hex_sidebar_bg')">
                                    <input type="text" id="hex_sidebar_bg" value="{{ $theme['sidebar_bg'] }}" placeholder="#020802" class="wxn-input wxn-color-hex" onchange="syncPicker(this,'sidebar_bg')">
                                </div>
                                <div class="wxn-presets-row">
                                    @foreach([['#020802','Dark Green'],['#020208','Dark Blue'],['#080202','Dark Red'],['#050505','Black'],['#0d0d0d','Charcoal']] as $p)
                                    <button type="button" class="wxn-preset" data-color="{{ $p[0] }}" data-name="sidebar_bg" data-hexid="hex_sidebar_bg" title="{{ $p[1] }}" style="background:{{ $p[0] }};border:1px solid rgba(255,255,255,0.1);{{ $theme['sidebar_bg']===$p[0] ? 'outline:2px solid #fff;' : '' }}"></button>
                                    @endforeach
                                </div>
                            </div>
                            <div>
                                <label class="wxn-label">SIDEBAR TEXT COLOR</label>
                                <div class="wxn-color-row">
                                    <input type="color" name="sidebar_text" value="{{ $theme['sidebar_text'] }}" class="wxn-color-pick" onchange="syncHex(this,'hex_sidebar_text')">
                                    <input type="text" id="hex_sidebar_text" value="{{ $theme['sidebar_text'] }}" placeholder="#b0ffb0" class="wxn-input wxn-color-hex" onchange="syncPicker(this,'sidebar_text')">
                                </div>
                                <div class="wxn-presets-row">
                                    @foreach([['#b0ffb0','Light Green'],['#ffffff','White'],['#aaaaaa','Grey'],['#b0c8ff','Light Blue']] as $p)
                                    <button type="button" class="wxn-preset" data-color="{{ $p[0] }}" data-name="sidebar_text" data-hexid="hex_sidebar_text" title="{{ $p[1] }}" style="background:{{ $p[0] }};{{ $theme['sidebar_text']===$p[0] ? 'outline:2px solid #888;' : '' }}"></button>
                                    @endforeach
                                </div>
                            </div>
                            <div>
                                <label class="wxn-label">ACTIVE ITEM BACKGROUND</label>
                                <input type="text" name="sidebar_active_bg" value="{{ $theme['sidebar_active_bg'] }}" placeholder="rgba(0,255,0,0.12)" class="wxn-input">
                                <div class="wxn-hint">Background of the active/hovered sidebar menu item. Accepts any CSS color, including rgba().</div>
                            </div>
                        </div>
                    </div>

                    {{-- ─── CONSOLE ─────────────────────────────────────────── --}}
                    <div class="wxn-tpane" id="t-console">
                        <div class="wxn-section-title"><i class="fa fa-terminal"></i> Console (Terminal)</div>
                        <p class="wxn-hint" style="margin-bottom:16px;">These colors apply to the server console terminal on the server console page.</p>
                        <div class="wxn-grid-3">
                            <div>
                                <label class="wxn-label">CONSOLE BACKGROUND</label>
                                <div class="wxn-color-row">
                                    <input type="color" name="console_bg" value="{{ substr($theme['console_bg'],0,7) }}" class="wxn-color-pick" onchange="syncHex(this,'hex_console_bg')">
                                    <input type="text" id="hex_console_bg" value="{{ $theme['console_bg'] }}" placeholder="#020702" class="wxn-input wxn-color-hex" onchange="syncPicker(this,'console_bg')">
                                </div>
                                <div class="wxn-hint">Terminal window background.</div>
                            </div>
                            <div>
                                <label class="wxn-label">CURSOR / ACCENT COLOR</label>
                                <div class="wxn-color-row">
                                    <input type="color" name="console_cursor" value="{{ $theme['console_cursor'] }}" class="wxn-color-pick" onchange="syncHex(this,'hex_console_cursor')">
                                    <input type="text" id="hex_console_cursor" value="{{ $theme['console_cursor'] }}" placeholder="#00e676" class="wxn-input wxn-color-hex" onchange="syncPicker(this,'console_cursor')">
                                </div>
                                <div class="wxn-hint">Cursor and selection highlight color.</div>
                            </div>
                            <div>
                                <label class="wxn-label">DEFAULT TEXT COLOR</label>
                                <div class="wxn-color-row">
                                    <input type="color" name="console_white" value="{{ $theme['console_white'] }}" class="wxn-color-pick" onchange="syncHex(this,'hex_console_white')">
                                    <input type="text" id="hex_console_white" value="{{ $theme['console_white'] }}" placeholder="#d0d0d0" class="wxn-input wxn-color-hex" onchange="syncPicker(this,'console_white')">
                                </div>
                                <div class="wxn-hint">Normal log output text color.</div>
                            </div>
                        </div>
                        <div class="wxn-grid-3" style="margin-top:14px;">
                            <div>
                                <label class="wxn-label" style="color:rgba(0,230,118,0.9);">&#9632; GREEN — Normal Logs</label>
                                <div class="wxn-color-row">
                                    <input type="color" name="console_green" value="{{ $theme['console_green'] }}" class="wxn-color-pick" onchange="syncHex(this,'hex_console_green')">
                                    <input type="text" id="hex_console_green" value="{{ $theme['console_green'] }}" placeholder="#00e676" class="wxn-input wxn-color-hex" onchange="syncPicker(this,'console_green')">
                                </div>
                            </div>
                            <div>
                                <label class="wxn-label" style="color:rgba(255,80,80,0.9);">&#9632; RED — Error Logs</label>
                                <div class="wxn-color-row">
                                    <input type="color" name="console_red" value="{{ $theme['console_red'] }}" class="wxn-color-pick" onchange="syncHex(this,'hex_console_red')">
                                    <input type="text" id="hex_console_red" value="{{ $theme['console_red'] }}" placeholder="#ff5370" class="wxn-input wxn-color-hex" onchange="syncPicker(this,'console_red')">
                                </div>
                            </div>
                            <div>
                                <label class="wxn-label" style="color:rgba(250,204,21,0.9);">&#9632; YELLOW — Warnings / Power Events</label>
                                <div class="wxn-color-row">
                                    <input type="color" name="console_yellow" value="{{ $theme['console_yellow'] }}" class="wxn-color-pick" onchange="syncHex(this,'hex_console_yellow')">
                                    <input type="text" id="hex_console_yellow" value="{{ $theme['console_yellow'] }}" placeholder="#facc15" class="wxn-input wxn-color-hex" onchange="syncPicker(this,'console_yellow')">
                                </div>
                            </div>
                            <div>
                                <label class="wxn-label" style="color:rgba(137,221,255,0.9);">&#9632; CYAN — Info</label>
                                <div class="wxn-color-row">
                                    <input type="color" name="console_cyan" value="{{ $theme['console_cyan'] }}" class="wxn-color-pick" onchange="syncHex(this,'hex_console_cyan')">
                                    <input type="text" id="hex_console_cyan" value="{{ $theme['console_cyan'] }}" placeholder="#89ddff" class="wxn-input wxn-color-hex" onchange="syncPicker(this,'console_cyan')">
                                </div>
                            </div>
                        </div>
                        <div style="margin-top:14px;" class="wxn-hint">
                            <i class="fa fa-info-circle"></i>
                            Note: Console colors apply after a page reload because xterm.js reads them at terminal initialization.
                        </div>
                    </div>

                    {{-- ─── SERVER CONTROL BUTTONS ──────────────────────────── --}}
                    <div class="wxn-tpane" id="t-buttons">
                        <div class="wxn-section-title"><i class="fa fa-power-off"></i> Server Control Buttons</div>
                        <p class="wxn-hint" style="margin-bottom:16px;">Colors for the Start, Stop/Kill, and Restart buttons on the server console page. Changes apply immediately — no rebuild needed.</p>

                        {{-- Live preview --}}
                        <div style="display:flex;gap:10px;margin-bottom:18px;align-items:center;flex-wrap:wrap;">
                            <span class="wxn-label" style="display:inline;">PREVIEW:</span>
                            <button type="button" id="prev-start" style="padding:7px 18px;border-radius:6px;border:1px solid;font-family:'JetBrains Mono',monospace;font-size:0.75rem;font-weight:700;letter-spacing:1px;cursor:default;background:var(--wxn-btn-start-bg);color:var(--wxn-btn-start-text);border-color:var(--wxn-btn-start-border);">START</button>
                            <button type="button" id="prev-restart" style="padding:7px 18px;border-radius:6px;border:1px solid;font-family:'JetBrains Mono',monospace;font-size:0.75rem;font-weight:700;letter-spacing:1px;cursor:default;background:var(--wxn-btn-restart-bg);color:var(--wxn-btn-restart-text);border-color:var(--wxn-btn-restart-border);">RESTART</button>
                            <button type="button" id="prev-stop" style="padding:7px 18px;border-radius:6px;border:1px solid;font-family:'JetBrains Mono',monospace;font-size:0.75rem;font-weight:700;letter-spacing:1px;cursor:default;background:var(--wxn-btn-stop-bg);color:var(--wxn-btn-stop-text);border-color:var(--wxn-btn-stop-border);">STOP</button>
                        </div>

                        <div class="wxn-grid-3">
                            {{-- START --}}
                            <div class="wxn-btn-section" style="border:1px solid rgba(0,230,118,0.2);border-radius:6px;padding:14px;">
                                <div style="font-family:'Orbitron',monospace;font-size:0.7rem;color:#00e676;letter-spacing:2px;margin-bottom:10px;text-transform:uppercase;">▶ Start Button</div>
                                <label class="wxn-label">BACKGROUND</label>
                                <div class="wxn-color-row">
                                    <input type="color" name="btn_start_bg" value="{{ substr($theme['btn_start_bg'],0,7) }}" class="wxn-color-pick wxn-btn-preview" data-preview="prev-start" data-prop="background" onchange="syncHex(this,'hex_btn_start_bg');updateBtnPreview()">
                                    <input type="text" id="hex_btn_start_bg" value="{{ $theme['btn_start_bg'] }}" placeholder="rgba(0,230,118,0.15)" class="wxn-input wxn-color-hex" onchange="syncPicker(this,'btn_start_bg');updateBtnPreview()" data-var="--wxn-btn-start-bg">
                                </div>
                                <label class="wxn-label" style="margin-top:8px;">TEXT COLOR</label>
                                <div class="wxn-color-row">
                                    <input type="color" name="btn_start_text" value="{{ $theme['btn_start_text'] }}" class="wxn-color-pick" onchange="syncHex(this,'hex_btn_start_text');updateBtnPreview()">
                                    <input type="text" id="hex_btn_start_text" value="{{ $theme['btn_start_text'] }}" placeholder="#00e676" class="wxn-input wxn-color-hex" onchange="syncPicker(this,'btn_start_text');updateBtnPreview()" data-var="--wxn-btn-start-text">
                                </div>
                                <label class="wxn-label" style="margin-top:8px;">BORDER COLOR</label>
                                <input type="text" name="btn_start_border" id="btn_start_border" value="{{ $theme['btn_start_border'] }}" placeholder="rgba(0,230,118,0.5)" class="wxn-input" style="margin-top:4px;" oninput="updateBtnPreview()">
                            </div>

                            {{-- RESTART --}}
                            <div class="wxn-btn-section" style="border:1px solid rgba(200,200,200,0.15);border-radius:6px;padding:14px;">
                                <div style="font-family:'Orbitron',monospace;font-size:0.7rem;color:rgba(255,255,255,0.7);letter-spacing:2px;margin-bottom:10px;text-transform:uppercase;">↻ Restart Button</div>
                                <label class="wxn-label">BACKGROUND</label>
                                <div class="wxn-color-row">
                                    <input type="color" name="btn_restart_bg" value="{{ substr($theme['btn_restart_bg'],0,7) }}" class="wxn-color-pick" onchange="syncHex(this,'hex_btn_restart_bg');updateBtnPreview()">
                                    <input type="text" id="hex_btn_restart_bg" value="{{ $theme['btn_restart_bg'] }}" placeholder="rgba(3,15,3,0.8)" class="wxn-input wxn-color-hex" onchange="syncPicker(this,'btn_restart_bg');updateBtnPreview()" data-var="--wxn-btn-restart-bg">
                                </div>
                                <label class="wxn-label" style="margin-top:8px;">TEXT COLOR</label>
                                <div class="wxn-color-row">
                                    <input type="color" name="btn_restart_text" value="{{ substr($theme['btn_restart_text'],0,7) }}" class="wxn-color-pick" onchange="syncHex(this,'hex_btn_restart_text');updateBtnPreview()">
                                    <input type="text" id="hex_btn_restart_text" value="{{ $theme['btn_restart_text'] }}" placeholder="rgba(255,255,255,0.75)" class="wxn-input wxn-color-hex" onchange="syncPicker(this,'btn_restart_text');updateBtnPreview()" data-var="--wxn-btn-restart-text">
                                </div>
                                <label class="wxn-label" style="margin-top:8px;">BORDER COLOR</label>
                                <input type="text" name="btn_restart_border" id="btn_restart_border" value="{{ $theme['btn_restart_border'] }}" placeholder="rgba(0,230,118,0.2)" class="wxn-input" style="margin-top:4px;" oninput="updateBtnPreview()">
                            </div>

                            {{-- STOP --}}
                            <div class="wxn-btn-section" style="border:1px solid rgba(220,38,38,0.2);border-radius:6px;padding:14px;">
                                <div style="font-family:'Orbitron',monospace;font-size:0.7rem;color:#ff6b6b;letter-spacing:2px;margin-bottom:10px;text-transform:uppercase;">■ Stop / Kill Button</div>
                                <label class="wxn-label">BACKGROUND</label>
                                <div class="wxn-color-row">
                                    <input type="color" name="btn_stop_bg" value="{{ substr($theme['btn_stop_bg'],0,7) }}" class="wxn-color-pick" onchange="syncHex(this,'hex_btn_stop_bg');updateBtnPreview()">
                                    <input type="text" id="hex_btn_stop_bg" value="{{ $theme['btn_stop_bg'] }}" placeholder="rgba(180,20,20,0.2)" class="wxn-input wxn-color-hex" onchange="syncPicker(this,'btn_stop_bg');updateBtnPreview()" data-var="--wxn-btn-stop-bg">
                                </div>
                                <label class="wxn-label" style="margin-top:8px;">TEXT COLOR</label>
                                <div class="wxn-color-row">
                                    <input type="color" name="btn_stop_text" value="{{ $theme['btn_stop_text'] }}" class="wxn-color-pick" onchange="syncHex(this,'hex_btn_stop_text');updateBtnPreview()">
                                    <input type="text" id="hex_btn_stop_text" value="{{ $theme['btn_stop_text'] }}" placeholder="#ff6b6b" class="wxn-input wxn-color-hex" onchange="syncPicker(this,'btn_stop_text');updateBtnPreview()" data-var="--wxn-btn-stop-text">
                                </div>
                                <label class="wxn-label" style="margin-top:8px;">BORDER COLOR</label>
                                <input type="text" name="btn_stop_border" id="btn_stop_border" value="{{ $theme['btn_stop_border'] }}" placeholder="rgba(220,38,38,0.45)" class="wxn-input" style="margin-top:4px;" oninput="updateBtnPreview()">
                            </div>
                        </div>
                    </div>

                    {{-- ─── CARDS & BORDERS ─────────────────────────────────── --}}
                    <div class="wxn-tpane" id="t-cards">
                        <div class="wxn-section-title"><i class="fa fa-th-large"></i> Cards & Borders</div>
                        <div class="wxn-grid-3">
                            <div>
                                <label class="wxn-label">CARD BACKGROUND</label>
                                <input type="text" name="card_bg" value="{{ $theme['card_bg'] }}" placeholder="rgba(0,0,0,0.45)" class="wxn-input">
                                <div class="wxn-hint">Background for cards, panels, stat blocks. Accepts any CSS color.</div>
                            </div>
                            <div>
                                <label class="wxn-label">CARD / BORDER COLOR</label>
                                <div class="wxn-color-row">
                                    <input type="color" name="card_border" value="{{ substr($theme['card_border'],0,7) }}" class="wxn-color-pick" onchange="syncHex(this,'hex_card_border')">
                                    <input type="text" id="hex_card_border" value="{{ $theme['card_border'] }}" placeholder="rgba(0,255,0,0.18)" class="wxn-input wxn-color-hex" onchange="syncPicker(this,'card_border')">
                                </div>
                                <div class="wxn-hint">Border color for cards and containers. Accepts rgba().</div>
                            </div>
                        </div>
                    </div>

                    {{-- ─── FONTS ───────────────────────────────────────────── --}}
                    <div class="wxn-tpane" id="t-fonts">
                        <div class="wxn-section-title"><i class="fa fa-font"></i> Fonts</div>
                        <div class="wxn-grid-3">
                            <div>
                                <label class="wxn-label">BODY FONT</label>
                                <input type="text" name="font_body" value="{{ $theme['font_body'] }}" placeholder="JetBrains Mono" class="wxn-input">
                                <div class="wxn-presets-row" style="flex-wrap:wrap;gap:6px;margin-top:8px;">
                                    @foreach(['JetBrains Mono','Courier New','Fira Code','Source Code Pro','Roboto Mono','Ubuntu Mono'] as $f)
                                    <button type="button" class="wxn-font-preset" data-font="{{ $f }}" data-target="font_body"
                                            style="padding:4px 8px;font-family:'{{ $f }}',monospace;font-size:0.72rem;background:rgba(0,255,0,0.06);border:1px solid rgba(0,255,0,{{ $theme['font_body']===$f ? '0.5' : '0.15' }});color:rgba(200,255,200,0.8);border-radius:3px;cursor:pointer;">{{ $f }}</button>
                                    @endforeach
                                </div>
                                <div class="wxn-hint">Used for body text, inputs, labels. Include fallback (e.g. "Fira Code, monospace").</div>
                            </div>
                            <div>
                                <label class="wxn-label">DISPLAY / HEADING FONT</label>
                                <input type="text" name="font_heading" value="{{ $theme['font_heading'] }}" placeholder="Orbitron" class="wxn-input">
                                <div class="wxn-presets-row" style="flex-wrap:wrap;gap:6px;margin-top:8px;">
                                    @foreach(['Orbitron','Exo 2','Rajdhani','Share Tech Mono','Press Start 2P','Russo One'] as $f)
                                    <button type="button" class="wxn-font-preset" data-font="{{ $f }}" data-target="font_heading"
                                            style="padding:4px 8px;font-family:'{{ $f }}',monospace;font-size:0.72rem;background:rgba(0,255,0,0.06);border:1px solid rgba(0,255,0,{{ $theme['font_heading']===$f ? '0.5' : '0.15' }});color:rgba(200,255,200,0.8);border-radius:3px;cursor:pointer;">{{ $f }}</button>
                                    @endforeach
                                </div>
                                <div class="wxn-hint">Used for page headings, logo, navigation titles.</div>
                            </div>
                        </div>
                        {{-- ── Base Font Size Slider ──────────────────────────── --}}
                        @php $fSize = (int)($theme['font_size_base'] ?? 14); @endphp
                        <div style="margin-top:20px;border-top:1px solid rgba(0,255,0,0.1);padding-top:16px;">
                            <div style="display:flex;align-items:center;gap:14px;margin-bottom:10px;">
                                <label class="wxn-label" style="margin:0;">BASE FONT SIZE</label>
                                <span id="wxn-fsize-display"
                                      style="font-family:'JetBrains Mono',monospace;font-size:1.1rem;color:var(--wxn-neon);font-weight:700;min-width:42px;text-align:center;background:rgba(0,255,0,0.07);border:1px solid rgba(0,255,0,0.2);padding:2px 8px;border-radius:4px;">{{ $fSize }}px</span>
                            </div>
                            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                                <span class="wxn-hint" style="min-width:30px;">10px</span>
                                <input type="range" name="font_size_base" id="wxn-fsize-slider"
                                       min="10" max="24" step="1" value="{{ $fSize }}"
                                       style="flex:1;min-width:180px;accent-color:var(--wxn-neon);cursor:pointer;"
                                       oninput="document.getElementById('wxn-fsize-display').textContent=this.value+'px';document.documentElement.style.setProperty('--wxn-font-size-base',this.value+'px');">
                                <span class="wxn-hint" style="min-width:30px;">24px</span>
                            </div>
                            <div class="wxn-presets-row" style="margin-top:10px;">
                                @foreach([10=>'XS',12=>'SM',14=>'MD',16=>'LG',18=>'XL',20=>'2XL'] as $sz => $lbl)
                                <button type="button" onclick="setFontSize({{ $sz }})"
                                        style="padding:4px 10px;font-family:'JetBrains Mono',monospace;font-size:0.7rem;background:rgba(0,255,0,0.06);border:1px solid rgba(0,255,0,{{ $fSize===$sz ? '0.5' : '0.15' }});color:rgba(200,255,200,0.8);border-radius:3px;cursor:pointer;"
                                        id="wxn-fsize-preset-{{ $sz }}">{{ $sz }}px {{ $lbl }}</button>
                                @endforeach
                            </div>
                            <div class="wxn-hint" style="margin-top:8px;"><i class="fa fa-info-circle"></i> Applies to the admin panel and user panel immediately. Does not require a rebuild.</div>
                        </div>

                        <div class="wxn-hint" style="margin-top:12px;"><i class="fa fa-info-circle"></i>
                            Use any Google Font name. Font must be loaded via the Google Fonts URL already in the template, or via Custom CSS. A React rebuild is needed for the font to apply to the console.</div>
                    </div>

                    {{-- ─── EFFECTS ──────────────────────────────────────────── --}}
                    <div class="wxn-tpane" id="t-effects">
                        <div class="wxn-section-title"><i class="fa fa-magic"></i> Background Effects</div>
                        <div style="display:flex;flex-wrap:wrap;gap:20px;align-items:flex-start;">
                            <div>
                                <label class="wxn-label" style="margin-bottom:10px;">NEON GRID OVERLAY</label>
                                <label class="wxn-toggle" style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                                    <input type="hidden" name="grid_enable" value="0">
                                    <input type="checkbox" name="grid_enable" value="1" id="chk-grid" {{ $theme['grid_enable']==='1' ? 'checked' : '' }}
                                           style="width:20px;height:20px;accent-color:var(--wxn-neon);cursor:pointer;">
                                    <span style="font-family:'JetBrains Mono',monospace;font-size:0.8rem;color:rgba(200,255,200,0.8);">Show neon grid lines on page background</span>
                                </label>
                                <div class="wxn-hint">The cyberpunk grid pattern in the page background.</div>
                            </div>
                            <div>
                                <label class="wxn-label" style="margin-bottom:10px;">SCAN LINE ANIMATION</label>
                                <label class="wxn-toggle" style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                                    <input type="hidden" name="scan_enable" value="0">
                                    <input type="checkbox" name="scan_enable" value="1" id="chk-scan" {{ $theme['scan_enable']==='1' ? 'checked' : '' }}
                                           style="width:20px;height:20px;accent-color:var(--wxn-neon);cursor:pointer;">
                                    <span style="font-family:'JetBrains Mono',monospace;font-size:0.8rem;color:rgba(200,255,200,0.8);">Show animated scan line overlay</span>
                                </label>
                                <div class="wxn-hint">Subtle moving scan lines over the background.</div>
                            </div>
                        </div>
                    </div>

                    {{-- ─── CUSTOM CSS ───────────────────────────────────────── --}}
                    <div class="wxn-tpane" id="t-css">
                        <div class="wxn-section-title"><i class="fa fa-code"></i> Custom CSS Injection</div>
                        <textarea name="custom_css" rows="14"
                                  placeholder="/* Injected into every page — admin + user panel */&#10;&#10;/* Override any element: */&#10;/* .main-header .logo { font-size: 1.3rem !important; } */&#10;/* .sidebar-menu > li > a { font-size: 1rem !important; } */"
                                  class="wxn-input wxn-textarea" style="font-size:0.75rem;line-height:1.7;">{{ $theme['custom_css'] }}</textarea>
                        <div class="wxn-hint"><i class="fa fa-warning"></i> This CSS is injected on every page, both admin and user panel. Use with care — invalid CSS can break the layout.</div>
                    </div>

                    {{-- Save Button --}}
                    <div style="margin-top:20px;padding-top:16px;border-top:1px solid rgba(0,255,0,0.15);display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                        <button type="submit" class="btn btn-success wxn-btn-submit" style="padding:10px 28px;font-size:0.85rem;letter-spacing:2px;">
                            <i class="fa fa-save"></i> SAVE ALL THEME SETTINGS
                        </button>
                        <span class="wxn-hint"><i class="fa fa-bolt"></i> Changes are live on all pages immediately — no server rebuild needed.</span>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ─── NOTIFICATIONS ───────────────────────────────────────────────── --}}
<div class="row">
    <div class="col-xs-12 col-md-5">
        <div class="box wxn-box">
            <div class="box-header wxn-box-header">
                <h3 class="box-title"><i class="fa fa-bell"></i> SEND NOTIFICATION</h3>
                <div class="box-tools"><span class="wxn-hint">Broadcasts to all users immediately</span></div>
            </div>
            <div class="box-body" style="padding:18px;">
                <form action="{{ route('admin.super.notifications.create') }}" method="POST">
                    @csrf
                    <label class="wxn-label">TITLE</label>
                    <input type="text" name="title" class="wxn-input" placeholder="Short notification title..." maxlength="120" required>

                    <label class="wxn-label" style="margin-top:12px;">MESSAGE</label>
                    <textarea name="body" class="wxn-input wxn-textarea" rows="4" placeholder="Notification body text..." maxlength="2000" required style="min-height:80px;"></textarea>

                    <label class="wxn-label" style="margin-top:12px;">TYPE</label>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:6px;">
                        @foreach(['info'=>['color'=>'#4da6ff','label'=>'Info'],'success'=>['color'=>'#00ff64','label'=>'Success'],'warning'=>['color'=>'#ffc800','label'=>'Warning'],'danger'=>['color'=>'#ff4444','label'=>'Danger']] as $tk => $tv)
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;padding:6px 12px;border:1px solid rgba(255,255,255,0.12);border-radius:4px;font-family:'JetBrains Mono',monospace;font-size:0.72rem;color:{{ $tv['color'] }};">
                            <input type="radio" name="type" value="{{ $tk }}" {{ $tk==='info' ? 'checked' : '' }} style="accent-color:{{ $tv['color'] }};"> {{ $tv['label'] }}
                        </label>
                        @endforeach
                    </div>

                    <button type="submit" class="btn btn-sm btn-success wxn-btn-submit" style="margin-top:14px;">
                        <i class="fa fa-paper-plane"></i> SEND NOTIFICATION
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xs-12 col-md-7">
        <div class="box wxn-box">
            <div class="box-header wxn-box-header with-border">
                <h3 class="box-title"><i class="fa fa-list"></i> ACTIVE NOTIFICATIONS <span class="wxn-badge" style="margin-left:6px;">{{ $notifications->count() }}</span></h3>
            </div>
            <div class="box-body table-responsive no-padding">
                @if($notifications->isEmpty())
                    <div style="padding:24px;text-align:center;color:rgba(255,255,255,0.3);font-family:'JetBrains Mono',monospace;font-size:0.78rem;">No notifications yet.</div>
                @else
                <table class="table table-hover">
                    <tbody>
                        <tr>
                            <th class="wxn-th">TITLE</th>
                            <th class="wxn-th">TYPE</th>
                            <th class="wxn-th">STATUS</th>
                            <th class="wxn-th">SENT</th>
                            <th class="wxn-th">ACTIONS</th>
                        </tr>
                        @foreach($notifications as $notif)
                        @php
                            $typeColors = ['info'=>'#4da6ff','success'=>'#00ff64','warning'=>'#ffc800','danger'=>'#ff4444'];
                            $tc = $typeColors[$notif->type] ?? '#4da6ff';
                        @endphp
                        <tr>
                            <td>
                                <strong style="color:#fff;font-size:0.82rem;">{{ $notif->title }}</strong>
                                <div class="wxn-mono-xs" style="margin-top:2px;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $notif->body }}</div>
                            </td>
                            <td><span style="color:{{ $tc }};font-family:'JetBrains Mono',monospace;font-size:0.7rem;text-transform:uppercase;">{{ $notif->type }}</span></td>
                            <td>
                                @if($notif->is_active)
                                    <span class="wxn-badge-green" style="font-size:0.65rem;">LIVE</span>
                                @else
                                    <span style="background:rgba(255,255,255,0.05);color:rgba(255,255,255,0.35);font-family:'JetBrains Mono',monospace;font-size:0.65rem;padding:2px 6px;border-radius:2px;">HIDDEN</span>
                                @endif
                            </td>
                            <td class="wxn-mono-xs">{{ $notif->created_at->diffForHumans() }}</td>
                            <td style="white-space:nowrap;">
                                <form action="{{ route('admin.super.notifications.toggle', $notif->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-xs" style="font-family:'JetBrains Mono',monospace;font-size:0.68rem;background:rgba(0,255,0,0.08);border:1px solid rgba(0,255,0,0.2);color:var(--wxn-neon);">
                                        <i class="fa fa-{{ $notif->is_active ? 'eye-slash' : 'eye' }}"></i> {{ $notif->is_active ? 'Hide' : 'Show' }}
                                    </button>
                                </form>
                                <form action="{{ route('admin.super.notifications.delete', $notif->id) }}" method="POST" style="display:inline;margin-left:4px;"
                                      class="wxn-confirm-form" data-confirm="Delete this notification?" data-confirm-detail="This cannot be undone." data-confirm-type="danger">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-danger" style="font-family:'JetBrains Mono',monospace;font-size:0.68rem;">
                                        <i class="fa fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     PAYMENT GATEWAYS
═══════════════════════════════════════════════════════════ --}}
<div class="row">
    <div class="col-xs-12">
        <div class="box wxn-box">
            <div class="box-header wxn-box-header">
                <h3 class="box-title"><i class="fa fa-credit-card"></i> PAYMENT GATEWAYS</h3>
                <div class="box-tools"><span class="wxn-hint">Configure payment providers for the billing page</span></div>
            </div>
            <div class="box-body" style="padding:20px;">

                {{-- Gateway status badges --}}
                <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:20px;">
                    @php
                        $psKey  = \wolfXcore\Models\Setting::where('key','settings::payment:paystack_secret')->value('value');
                        $allGateways = [
                            ['id'=>'paystack','label'=>'Paystack','icon'=>'fa-bolt','configured'=>!empty($psKey)],
                            ['id'=>'paypal','label'=>'PayPal','icon'=>'fa-paypal','configured'=>false],
                            ['id'=>'crypto','label'=>'Crypto','icon'=>'fa-bitcoin','configured'=>false],
                        ];
                    @endphp
                    @foreach($allGateways as $gw)
                    <div style="display:flex;align-items:center;gap:8px;padding:8px 14px;border:1px solid {{ $gw['configured'] ? 'rgba(0,255,0,0.4)' : 'rgba(255,255,255,0.1)' }};border-radius:5px;background:{{ $gw['configured'] ? 'rgba(0,255,0,0.06)' : 'rgba(255,255,255,0.02)' }};">
                        <i class="fa {{ $gw['icon'] }}" style="color:{{ $gw['configured'] ? 'var(--wxn-neon)' : 'rgba(255,255,255,0.3)' }};"></i>
                        <span style="font-family:'JetBrains Mono',monospace;font-size:0.8rem;color:{{ $gw['configured'] ? '#fff' : 'rgba(255,255,255,0.35)' }};">{{ $gw['label'] }}</span>
                        <span style="font-size:0.65rem;letter-spacing:1px;color:{{ $gw['configured'] ? 'var(--wxn-neon)' : 'rgba(255,255,255,0.25)' }};">{{ $gw['configured'] ? '● ACTIVE' : '○ NOT SET' }}</span>
                    </div>
                    @endforeach
                </div>

                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:20px;">

                    {{-- Paystack --}}
                    <div style="border:1px solid rgba(0,255,0,0.2);border-radius:8px;padding:18px;background:rgba(0,255,0,0.02);">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
                            <i class="fa fa-bolt" style="color:var(--wxn-neon);font-size:1.2rem;"></i>
                            <span style="font-family:'Orbitron',monospace;font-size:0.85rem;letter-spacing:1px;color:var(--wxn-neon);">PAYSTACK</span>
                            <span style="font-size:0.65rem;margin-left:auto;padding:2px 8px;border-radius:3px;background:{{ !empty($psKey) ? 'rgba(0,255,0,0.15)' : 'rgba(255,60,60,0.12)' }};color:{{ !empty($psKey) ? 'var(--wxn-neon)' : '#ff6060' }};font-family:'JetBrains Mono',monospace;">{{ !empty($psKey) ? '✓ CONFIGURED' : '✗ NOT SET' }}</span>
                        </div>
                        <form action="{{ route('admin.super.payment.save') }}" method="POST">
                            @csrf
                            <input type="hidden" name="gateway" value="paystack">
                            <label class="wxn-label">PUBLIC KEY</label>
                            <input type="text" name="paystack_public" class="wxn-input"
                                   value="{{ \wolfXcore\Models\Setting::where('key','settings::payment:paystack_public')->value('value') }}"
                                   placeholder="pk_live_..." autocomplete="off" style="margin-bottom:10px;">
                            <label class="wxn-label">SECRET KEY</label>
                            <input type="text" name="paystack_secret" class="wxn-input"
                                   value="{{ !empty($psKey) ? '••••••••••••' . substr($psKey,-4) : '' }}"
                                   placeholder="sk_live_..." autocomplete="off" style="margin-bottom:10px;"
                                   onfocus="if(this.value.startsWith('•'))this.value=''"
                                   data-actual="{{ !empty($psKey) ? '1' : '0' }}">
                            <label class="wxn-label">CURRENCY</label>
                            <select name="currency" class="wxn-input" style="margin-bottom:14px;">
                                @foreach(['KES'=>'KES — Kenyan Shilling','NGN'=>'NGN — Nigerian Naira','GHS'=>'GHS — Ghanaian Cedi','ZAR'=>'ZAR — South African Rand','USD'=>'USD — US Dollar'] as $code=>$label)
                                <option value="{{ $code }}" {{ (\wolfXcore\Models\Setting::where('key','settings::payment:currency')->value('value') ?? 'KES')===$code ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm btn-success wxn-btn-submit" style="width:100%;">
                                <i class="fa fa-save"></i> SAVE PAYSTACK SETTINGS
                            </button>
                        </form>
                    </div>

                    {{-- PayPal (coming soon) --}}
                    <div style="border:1px solid rgba(255,255,255,0.08);border-radius:8px;padding:18px;background:rgba(255,255,255,0.01);opacity:0.55;">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
                            <i class="fa fa-paypal" style="color:#003087;font-size:1.2rem;"></i>
                            <span style="font-family:'Orbitron',monospace;font-size:0.85rem;letter-spacing:1px;color:#009cde;">PAYPAL</span>
                            <span style="font-size:0.65rem;margin-left:auto;padding:2px 8px;border-radius:3px;background:rgba(255,255,255,0.06);color:rgba(255,255,255,0.4);font-family:'JetBrains Mono',monospace;">COMING SOON</span>
                        </div>
                        <div class="wxn-hint">PayPal support will be added in a future update.</div>
                    </div>

                    {{-- Crypto (coming soon) --}}
                    <div style="border:1px solid rgba(255,255,255,0.08);border-radius:8px;padding:18px;background:rgba(255,255,255,0.01);opacity:0.55;">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
                            <i class="fa fa-bitcoin" style="color:#f7931a;font-size:1.2rem;"></i>
                            <span style="font-family:'Orbitron',monospace;font-size:0.85rem;letter-spacing:1px;color:#f7931a;">CRYPTO</span>
                            <span style="font-size:0.65rem;margin-left:auto;padding:2px 8px;border-radius:3px;background:rgba(255,255,255,0.06);color:rgba(255,255,255,0.4);font-family:'JetBrains Mono',monospace;">COMING SOON</span>
                        </div>
                        <div class="wxn-hint">Crypto payment support (BTC, ETH, USDT) coming in a future update.</div>
                    </div>
                </div>

                {{-- Webhook info --}}
                <div style="margin-top:18px;padding:12px 16px;background:rgba(0,0,0,0.25);border:1px solid rgba(0,255,0,0.1);border-radius:6px;display:grid;gap:12px;">
                    <div>
                        <div style="font-family:'JetBrains Mono',monospace;font-size:0.73rem;color:rgba(0,255,0,0.6);margin-bottom:4px;letter-spacing:1px;">PAYSTACK CALLBACK URL</div>
                        <div style="font-family:'JetBrains Mono',monospace;font-size:0.78rem;color:#fff;word-break:break-all;">{{ url('/billing/callback') }}</div>
                        <div class="wxn-hint" style="margin-top:4px;">Set this in Paystack dashboard → Settings → API Keys &amp; Webhooks.</div>
                    </div>
                    <div>
                        <a href="{{ route('admin.super.paystack-transactions') }}"
                           class="btn btn-sm btn-success" style="font-family:'JetBrains Mono',monospace;letter-spacing:1px;">
                            <i class="fa fa-list"></i> VIEW PAYSTACK TRANSACTIONS
                        </a>
                        <div class="wxn-hint" style="margin-top:6px;">Live records pulled from your Paystack account, cross-referenced with the local database.</div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- ─── SITE BRANDING ─────────────────────────────────────────────────── --}}
<div class="row">
    <div class="col-xs-12 col-md-4">
        <div class="box wxn-box">
            <div class="box-header wxn-box-header">
                <h3 class="box-title"><i class="fa fa-image"></i> SITE BRANDING</h3>
            </div>
            <div class="box-body" style="padding:18px;">
                <div style="display:flex;align-items:center;gap:14px;margin-bottom:14px;">
                    <img id="wxn-logo-preview" src="{{ $logoUrl }}" alt="Logo"
                         style="width:64px;height:64px;object-fit:cover;border:2px solid rgba(0,255,0,0.3);border-radius:6px;background:#000;">
                    <div class="wxn-hint" style="word-break:break-all;">{{ $logoUrl }}</div>
                </div>
                <form action="{{ route('admin.super.branding') }}" method="POST">
                    @csrf
                    <label class="wxn-label">LOGO URL</label>
                    <input type="text" name="logo_url" id="wxn-logo-url" value="{{ $logoUrl }}"
                           placeholder="https://... or /wolf-logo.jpg" class="wxn-input"
                           oninput="document.getElementById('wxn-logo-preview').src=this.value||'{{ $logoUrl }}'">
                    <div class="wxn-hint">External URL or local path. Used as favicon + OG image.</div>
                    <button type="submit" class="btn btn-sm btn-success wxn-btn-submit" style="margin-top:12px;">
                        <i class="fa fa-save"></i> SAVE BRANDING
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ─── TAB VISIBILITY ─────────────────────────────────────────── --}}
    <div class="col-xs-12 col-md-8">
        <div class="box wxn-box">
            <div class="box-header wxn-box-header">
                <h3 class="box-title"><i class="fa fa-toggle-on"></i> SERVER TAB VISIBILITY</h3>
                <div class="box-tools"><span class="wxn-hint">Disable tabs globally for all users</span></div>
            </div>
            <div class="box-body" style="padding:18px;">
                <form action="{{ route('admin.super.tabs') }}" method="POST" id="wxn-tabs-form">
                    @csrf
                    <div class="wxn-hint" style="margin-bottom:12px;"><i class="fa fa-info-circle"></i> Console tab is always visible and cannot be disabled.</div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(155px,1fr));gap:10px;">
                        @php $tabLabels=['files'=>['icon'=>'fa-folder','label'=>'Files'],'databases'=>['icon'=>'fa-database','label'=>'Databases'],'schedules'=>['icon'=>'fa-clock-o','label'=>'Schedules'],'users'=>['icon'=>'fa-users','label'=>'Users'],'backups'=>['icon'=>'fa-download','label'=>'Backups'],'network'=>['icon'=>'fa-exchange','label'=>'Network'],'startup'=>['icon'=>'fa-play','label'=>'Startup'],'settings'=>['icon'=>'fa-cog','label'=>'Settings'],'activity'=>['icon'=>'fa-list','label'=>'Activity']]; @endphp
                        @foreach($allTabs as $tab)
                        @php $isDisabled=in_array($tab,$disabledTabs); @endphp
                        <label class="wxn-tab-toggle {{ $isDisabled ? 'wxn-tab-disabled' : 'wxn-tab-enabled' }}"
                               style="display:flex;align-items:center;gap:10px;padding:10px 14px;border:1px solid {{ $isDisabled ? 'rgba(255,60,60,0.3)' : 'rgba(0,255,0,0.2)' }};border-radius:5px;background:{{ $isDisabled ? 'rgba(255,0,0,0.05)' : 'rgba(0,255,0,0.03)' }};cursor:pointer;transition:all .2s;">
                            <input type="checkbox" name="disabled_tabs[]" value="{{ $tab }}"
                                   {{ $isDisabled ? 'checked' : '' }} style="display:none;" class="wxn-tab-checkbox" onchange="updateTabCard(this)">
                            <span style="font-size:1rem;color:{{ $isDisabled ? 'rgba(255,100,100,0.7)' : 'var(--wxn-neon)' }};"><i class="fa {{ $tabLabels[$tab]['icon'] }}"></i></span>
                            <span style="font-family:'JetBrains Mono',monospace;font-size:0.78rem;color:{{ $isDisabled ? 'rgba(255,150,150,0.7)' : 'rgba(180,255,180,0.8)' }};">{{ $tabLabels[$tab]['label'] }}</span>
                            <span class="wxn-tab-status" style="margin-left:auto;font-family:'JetBrains Mono',monospace;font-size:0.62rem;padding:2px 6px;border-radius:3px;background:{{ $isDisabled ? 'rgba(255,0,0,0.15)' : 'rgba(0,255,0,0.1)' }};color:{{ $isDisabled ? '#ff6666' : 'var(--wxn-neon)' }};">{{ $isDisabled ? 'HIDDEN' : 'VISIBLE' }}</span>
                        </label>
                        @endforeach
                    </div>
                    {{-- ── Power Button Order ────────────────────────────────── --}}
                    @php
                        $savedOrder = json_decode($theme['btn_order'] ?? '["start","restart","stop"]', true) ?: ['start','restart','stop'];
                        $btnMeta = [
                            'start'   => ['icon'=>'fa-play',         'label'=>'START',   'color'=>'rgba(0,255,0,0.8)',   'bg'=>'rgba(0,255,0,0.08)',   'border'=>'rgba(0,255,0,0.3)'],
                            'restart' => ['icon'=>'fa-refresh',      'label'=>'RESTART', 'color'=>'rgba(255,255,255,0.7)','bg'=>'rgba(255,255,255,0.05)','border'=>'rgba(255,255,255,0.15)'],
                            'stop'    => ['icon'=>'fa-stop',         'label'=>'STOP',    'color'=>'rgba(255,107,107,0.9)','bg'=>'rgba(255,0,0,0.07)',   'border'=>'rgba(255,60,60,0.3)'],
                        ];
                    @endphp
                    <div style="margin-top:18px;border-top:1px solid rgba(0,255,0,0.1);padding-top:16px;">
                        <div class="wxn-label" style="margin-bottom:6px;"><i class="fa fa-sort"></i> POWER BUTTON ORDER</div>
                        <div class="wxn-hint" style="margin-bottom:12px;">Drag to reorder. Changes apply after the next page load.</div>
                        <input type="hidden" name="btn_order" id="wxn-btn-order-val" value="{{ json_encode($savedOrder) }}">
                        <div id="wxn-btn-sort" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                            @foreach($savedOrder as $bk)
                            @php $m = $btnMeta[$bk] ?? $btnMeta['start']; @endphp
                            <div class="wxn-sortable-btn" data-key="{{ $bk }}" draggable="true"
                                 style="display:flex;align-items:center;gap:8px;padding:10px 16px;border:1px solid {{ $m['border'] }};border-radius:5px;background:{{ $m['bg'] }};cursor:grab;user-select:none;transition:opacity .15s,box-shadow .15s;min-width:110px;">
                                <i class="fa fa-bars" style="color:rgba(255,255,255,0.2);font-size:0.8rem;"></i>
                                <i class="fa {{ $m['icon'] }}" style="color:{{ $m['color'] }};font-size:0.9rem;"></i>
                                <span style="font-family:'JetBrains Mono',monospace;font-size:0.78rem;color:{{ $m['color'] }};letter-spacing:1px;">{{ $m['label'] }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- ── Power Button Position ────────────────────────────────── --}}
                    @php $savedPos = $theme['btn_position'] ?? 'right'; @endphp
                    <div style="margin-top:18px;border-top:1px solid rgba(0,255,0,0.1);padding-top:16px;">
                        <div class="wxn-label" style="margin-bottom:6px;"><i class="fa fa-arrows"></i> POWER BUTTON POSITION</div>
                        <div class="wxn-hint" style="margin-bottom:12px;">Choose where buttons appear on the server console page.</div>
                        <input type="hidden" name="btn_position" id="wxn-btn-pos-val" value="{{ $savedPos }}">
                        <div style="display:flex;gap:10px;flex-wrap:wrap;">
                            @foreach([
                                'top'    => ['label'=>'Top',    'icon'=>'fa-arrow-up',    'desc'=>'Above console'],
                                'left'   => ['label'=>'Left',   'icon'=>'fa-arrow-left',  'desc'=>'Before server name'],
                                'right'  => ['label'=>'Right',  'icon'=>'fa-arrow-right', 'desc'=>'After server name'],
                                'bottom' => ['label'=>'Bottom', 'icon'=>'fa-arrow-down',  'desc'=>'Below stats'],
                            ] as $posKey => $posMeta)
                            <div class="wxn-pos-card {{ $savedPos === $posKey ? 'wxn-pos-active' : '' }}"
                                 onclick="selectBtnPos('{{ $posKey }}')"
                                 style="cursor:pointer;padding:10px 14px;border:1px solid {{ $savedPos === $posKey ? 'rgba(0,255,0,0.7)' : 'rgba(0,255,0,0.15)' }};border-radius:5px;background:{{ $savedPos === $posKey ? 'rgba(0,255,0,0.1)' : 'rgba(0,0,0,0.2)' }};min-width:100px;text-align:center;transition:all .2s;">
                                {{-- Mini layout preview --}}
                                <div style="width:60px;height:42px;border:1px solid rgba(255,255,255,0.1);border-radius:3px;margin:0 auto 8px;position:relative;background:#0a100a;overflow:hidden;">
                                    @if($posKey === 'top')
                                        <div style="position:absolute;top:3px;left:3px;right:3px;height:7px;background:rgba(0,255,0,0.5);border-radius:1px;"></div>
                                        <div style="position:absolute;top:14px;left:3px;right:3px;bottom:3px;background:rgba(0,255,0,0.08);border-radius:1px;"></div>
                                    @elseif($posKey === 'left')
                                        <div style="position:absolute;top:3px;left:3px;width:14px;bottom:3px;background:rgba(0,255,0,0.5);border-radius:1px;"></div>
                                        <div style="position:absolute;top:3px;left:21px;right:3px;bottom:3px;background:rgba(0,255,0,0.08);border-radius:1px;"></div>
                                    @elseif($posKey === 'right')
                                        <div style="position:absolute;top:3px;right:3px;width:14px;bottom:3px;background:rgba(0,255,0,0.5);border-radius:1px;"></div>
                                        <div style="position:absolute;top:3px;left:3px;right:21px;bottom:3px;background:rgba(0,255,0,0.08);border-radius:1px;"></div>
                                    @elseif($posKey === 'bottom')
                                        <div style="position:absolute;top:3px;left:3px;right:3px;bottom:14px;background:rgba(0,255,0,0.08);border-radius:1px;"></div>
                                        <div style="position:absolute;bottom:3px;left:3px;right:3px;height:7px;background:rgba(0,255,0,0.5);border-radius:1px;"></div>
                                    @endif
                                </div>
                                <div style="font-family:'Orbitron',monospace;font-size:0.68rem;color:{{ $savedPos === $posKey ? 'var(--wxn-neon)' : 'rgba(200,255,200,0.6)' }};letter-spacing:1px;">{{ $posMeta['label'] }}</div>
                                <div style="font-family:'JetBrains Mono',monospace;font-size:0.6rem;color:rgba(255,255,255,0.3);margin-top:2px;">{{ $posMeta['desc'] }}</div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div style="margin-top:16px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        <button type="submit" class="btn btn-sm btn-success wxn-btn-submit"><i class="fa fa-save"></i> SAVE</button>
                        <button type="button" class="btn btn-sm" onclick="setAllTabs(false)" style="font-family:'JetBrains Mono',monospace;font-size:0.72rem;background:rgba(0,255,0,0.08);border:1px solid rgba(0,255,0,0.2);color:var(--wxn-neon);"><i class="fa fa-check-square-o"></i> SHOW ALL</button>
                        <button type="button" class="btn btn-sm" onclick="setAllTabs(true)" style="font-family:'JetBrains Mono',monospace;font-size:0.72rem;background:rgba(255,0,0,0.06);border:1px solid rgba(255,0,0,0.2);color:#ff6666;"><i class="fa fa-times-circle-o"></i> HIDE ALL</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     SERVER AUTO-PROVISIONING
═══════════════════════════════════════════════════════════ --}}
<div class="row">
    <div class="col-xs-12">
        <div class="box wxn-box">
            <div class="box-header wxn-box-header">
                <h3 class="box-title"><i class="fa fa-server"></i> SERVER AUTO-PROVISIONING</h3>
                <div class="box-tools"><span class="wxn-hint">Servers are auto-created here after a user pays for a plan</span></div>
            </div>
            <div class="box-body" style="padding:20px;">
                <form action="{{ route('admin.super.provisioning.save') }}" method="POST">
                    @csrf
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;margin-bottom:18px;">
                        <div>
                            <label class="wxn-label">NODE</label>
                            <select name="node_id" class="wxn-input">
                                @foreach($nodes as $node)
                                <option value="{{ $node->id }}" {{ ($serverConfig && $serverConfig->node_id == $node->id) ? 'selected' : '' }}>
                                    #{{ $node->id }} — {{ $node->name }} ({{ $node->fqdn }})
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="wxn-label">NEST</label>
                            <select name="nest_id" class="wxn-input" onchange="filterEggs(this.value)">
                                @foreach($nests as $nest)
                                <option value="{{ $nest->id }}" {{ ($serverConfig && $serverConfig->nest_id == $nest->id) ? 'selected' : '' }}>
                                    #{{ $nest->id }} — {{ $nest->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="wxn-label">EGG (Application Type)</label>
                            <select name="egg_id" class="wxn-input" id="eggSelect">
                                @foreach($eggs as $egg)
                                <option value="{{ $egg->id }}"
                                    data-nest="{{ $egg->nest_id }}"
                                    {{ ($serverConfig && $serverConfig->egg_id == $egg->id) ? 'selected' : '' }}>
                                    #{{ $egg->id }} — {{ $egg->name }} (Nest {{ $egg->nest_id }})
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;margin-bottom:18px;">
                        <div>
                            <label class="wxn-label">STARTUP COMMAND OVERRIDE <span style="color:rgba(255,255,255,0.3);font-size:0.7rem;">(leave blank to use egg default)</span></label>
                            <input type="text" name="startup_override" class="wxn-input"
                                value="{{ $serverConfig->startup_override ?? '' }}"
                                placeholder="e.g. node index.js" autocomplete="off">
                        </div>
                        <div>
                            <label class="wxn-label">DOCKER IMAGE OVERRIDE <span style="color:rgba(255,255,255,0.3);font-size:0.7rem;">(leave blank to use egg default)</span></label>
                            <input type="text" name="docker_image_override" class="wxn-input"
                                value="{{ $serverConfig->docker_image_override ?? '' }}"
                                placeholder="e.g. ghcr.io/wolfxcore/yolks:nodejs_18" autocomplete="off">
                        </div>
                    </div>
                    <div style="background:rgba(0,255,0,0.04);border:1px solid rgba(0,255,0,0.12);border-radius:6px;padding:12px 16px;margin-bottom:16px;font-family:'JetBrains Mono',monospace;font-size:0.75rem;color:rgba(255,255,255,0.5);line-height:1.7;">
                        <strong style="color:var(--wxn-neon);">How it works:</strong> When a user pays for a plan (via Paystack or Wallet), a server is automatically created using the settings above.
                        RAM / CPU / Disk are taken from the plan. A free port allocation on the selected node is assigned automatically.
                        If no free allocations are available, provisioning is skipped silently and you can create the server manually.
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                        <button type="submit" class="btn btn-sm btn-success wxn-btn-submit">
                            <i class="fa fa-save"></i> SAVE PROVISIONING SETTINGS
                        </button>
                        @if($serverConfig)
                        <span style="font-family:'JetBrains Mono',monospace;font-size:0.72rem;color:rgba(0,255,0,0.5);">
                            ✓ Current: Node #{{ $serverConfig->node_id }} / Nest #{{ $serverConfig->nest_id }} / Egg #{{ $serverConfig->egg_id }}
                        </span>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function filterEggs(nestId) {
    const sel = document.getElementById('eggSelect');
    Array.from(sel.options).forEach(opt => {
        opt.hidden = opt.dataset.nest !== String(nestId);
    });
    const first = Array.from(sel.options).find(o => !o.hidden);
    if (first) sel.value = first.value;
}
// Auto-filter eggs on page load to match selected nest
(function() {
    const nestSel = document.querySelector('select[name="nest_id"]');
    if (nestSel) filterEggs(nestSel.value);
})();
</script>

{{-- Admin Users + All Users --}}
<div class="row">
    <div class="col-xs-12">
        <div class="box wxn-box">
            <div class="box-header wxn-box-header with-border">
                <h3 class="box-title"><i class="fa fa-users"></i> ADMIN USERS</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tbody>
                        <tr><th class="wxn-th">USER</th><th class="wxn-th">EMAIL</th><th class="wxn-th">JOINED</th><th class="wxn-th">ROLE</th><th class="wxn-th">ACTION</th></tr>
                        @foreach($admins as $user)
                        <tr style="{{ $user->id===Auth::id() ? 'background:rgba(0,255,0,0.04);' : '' }}">
                            <td><strong style="color:#fff;">{{ $user->username }}</strong><br><small style="color:rgba(255,255,255,0.4);">{{ $user->name_first }} {{ $user->name_last }}</small>@if($user->id===Auth::id()) <span class="wxn-badge">YOU</span>@endif</td>
                            <td class="wxn-mono-sm">{{ $user->email }}</td>
                            <td class="wxn-mono-xs">{{ $user->created_at->format('Y-m-d') }}</td>
                            <td><span class="wxn-badge-green"><i class="fa fa-shield"></i> ADMIN</span></td>
                            <td>
                                @if($user->id !== Auth::id())
                                    <form action="{{ route('admin.super.toggle', $user->id) }}" method="POST"
                                          style="display:inline;" class="wxn-confirm-form"
                                          data-confirm="Revoke admin from {{ $user->username }}?"
                                          data-confirm-detail="They will immediately lose all admin access."
                                          data-confirm-type="danger">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-danger" style="font-family:'JetBrains Mono',monospace;font-size:0.72rem;">
                                            <i class="fa fa-times"></i> REVOKE
                                        </button>
                                    </form>
                                @else
                                    <span style="color:rgba(0,255,0,0.3);font-family:'JetBrains Mono',monospace;font-size:0.72rem;">Protected</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-xs-12">
        <div class="box wxn-box">
            <div class="box-header wxn-box-header with-border">
                <h3 class="box-title"><i class="fa fa-list"></i> ALL USERS</h3>
                <div class="box-tools"><span class="wxn-hint">Promote users to admin</span></div>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tbody>
                        <tr><th class="wxn-th">USER</th><th class="wxn-th">EMAIL</th><th class="wxn-th">SERVERS</th><th class="wxn-th">STATUS</th><th class="wxn-th">ACTION</th></tr>
                        @foreach($allUsers as $user)
                        <tr>
                            <td><strong style="color:#fff;">{{ $user->username }}</strong><br><small style="color:rgba(255,255,255,0.4);">{{ $user->name_first }} {{ $user->name_last }}</small></td>
                            <td class="wxn-mono-sm">{{ $user->email }}</td>
                            <td class="wxn-mono-sm">{{ $user->servers()->count() }}</td>
                            <td>
                                @if($user->root_admin)
                                    <span class="wxn-badge-green">ADMIN</span>
                                @else
                                    <span style="background:rgba(255,255,255,0.05);color:rgba(255,255,255,0.35);font-size:0.68rem;font-family:'JetBrains Mono',monospace;padding:2px 6px;border-radius:2px;">USER</span>
                                @endif
                            </td>
                            <td>
                                @if($user->id !== Auth::id())
                                    <form action="{{ route('admin.super.toggle', $user->id) }}" method="POST"
                                          style="display:inline;" class="wxn-confirm-form"
                                          data-confirm="{{ $user->root_admin ? 'Revoke admin from' : 'Grant admin to' }} {{ $user->username }}?"
                                          data-confirm-detail="{{ $user->root_admin ? 'They will immediately lose all admin access.' : 'They will gain full admin access to the panel.' }}"
                                          data-confirm-type="{{ $user->root_admin ? 'danger' : 'success' }}">
                                        @csrf
                                        @if($user->root_admin)
                                            <button type="submit" class="btn btn-xs btn-danger" style="font-family:'JetBrains Mono',monospace;font-size:0.7rem;">
                                                <i class="fa fa-times"></i> Revoke
                                            </button>
                                        @else
                                            <button type="submit" class="btn btn-xs btn-success" style="font-family:'JetBrains Mono',monospace;font-size:0.7rem;">
                                                <i class="fa fa-arrow-up"></i> Make Admin
                                            </button>
                                        @endif
                                    </form>
                                @else
                                    <span style="color:rgba(0,255,0,0.3);font-family:'JetBrains Mono',monospace;font-size:0.7rem;">You</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($allUsers->hasPages())
                <div class="box-footer" style="background:rgba(0,0,0,0.3);border-top:1px solid rgba(0,255,0,0.1);">{!! $allUsers->links() !!}</div>
            @endif
        </div>
    </div>
</div>

@endsection

@section('footer-scripts')
@parent
<style>
/* ── Super Admin Styles ── */
.wxn-stat-box.box { border:1px solid rgba(0,255,0,0.2);background:#060d06;text-align:center;padding:18px; }
.wxn-box.box { border:1px solid rgba(0,255,0,0.3);background:#060d06; }
.wxn-box-header { border-bottom:1px solid rgba(0,255,0,0.15);padding:12px 18px; }
.wxn-box-header .box-title { font-family:'Orbitron',monospace;color:var(--wxn-neon);font-size:0.85rem;letter-spacing:2px; }
.wxn-live-badge { font-family:'JetBrains Mono',monospace;font-size:0.72rem;color:rgba(0,255,0,0.5);background:rgba(0,255,0,0.06);padding:3px 8px;border-radius:3px;border:1px solid rgba(0,255,0,0.2); }
.wxn-label { font-family:'JetBrains Mono',monospace;font-size:0.72rem;color:rgba(0,255,0,0.6);letter-spacing:1px;display:block;margin-bottom:4px;text-transform:uppercase; }
.wxn-hint { font-family:'JetBrains Mono',monospace;font-size:0.65rem;color:rgba(0,255,0,0.3);margin-top:5px;line-height:1.5; }
.wxn-input { width:100%;background:#0a150a;border:1px solid rgba(0,255,0,0.3);color:#00ff00;font-family:'JetBrains Mono',monospace;font-size:0.8rem;padding:8px 10px;border-radius:3px;outline:none;margin-top:4px;transition:border-color .2s;display:block; }
.wxn-input:focus { border-color:rgba(0,255,0,0.7);box-shadow:0 0 0 2px rgba(0,255,0,0.08); }
.wxn-textarea { resize:vertical;min-height:120px; }
.wxn-btn-submit { font-family:'JetBrains Mono',monospace;font-size:0.75rem;letter-spacing:1px; }
.wxn-section-title { font-family:'Orbitron',monospace;color:var(--wxn-neon);font-size:0.82rem;letter-spacing:2px;text-transform:uppercase;margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid rgba(0,255,0,0.12); }
.wxn-grid-3 { display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px; }
.wxn-color-row { display:flex;gap:8px;align-items:center;margin-top:4px; }
.wxn-color-pick { width:44px;height:36px;border:1px solid rgba(0,255,0,0.3);background:#0a150a;border-radius:4px;cursor:pointer;padding:2px;flex-shrink:0; }
.wxn-color-hex { flex:1;margin-top:0; }
.wxn-presets-row { display:flex;flex-wrap:wrap;gap:5px;margin-top:8px; }
.wxn-preset { width:22px;height:22px;border-radius:4px;border:2px solid transparent;cursor:pointer;transition:transform .1s,outline .1s;padding:0; }
.wxn-preset:hover { transform:scale(1.15); }
/* Theme tab nav */
.wxn-tab-nav { display:flex;flex-wrap:wrap;gap:6px;margin-bottom:18px;border-bottom:1px solid rgba(0,255,0,0.12);padding-bottom:14px; }
.wxn-tnav { background:rgba(0,255,0,0.04);border:1px solid rgba(0,255,0,0.18);color:rgba(200,255,200,0.7);font-family:'JetBrains Mono',monospace;font-size:0.73rem;padding:6px 12px;border-radius:4px;cursor:pointer;transition:all .15s;letter-spacing:0.5px; }
.wxn-tnav:hover { background:rgba(0,255,0,0.1);color:#fff; }
.wxn-tnav.active { background:rgba(0,255,0,0.14);border-color:var(--wxn-neon);color:var(--wxn-neon);font-weight:700; }
.wxn-tpane { display:none; }
.wxn-tpane.active { display:block; }
/* Table headers */
.wxn-th { font-family:'Orbitron',monospace;font-size:0.7rem;color:rgba(0,255,0,0.6);letter-spacing:1px;padding:10px 14px; }
.wxn-mono-sm { font-family:'JetBrains Mono',monospace;font-size:0.78rem;color:rgba(255,255,255,0.6); }
.wxn-mono-xs { font-family:'JetBrains Mono',monospace;font-size:0.72rem;color:rgba(255,255,255,0.4); }
.wxn-badge { background:rgba(0,255,0,0.12);color:#00ff00;font-family:'JetBrains Mono',monospace;font-size:0.65rem;padding:2px 6px;border-radius:3px;border:1px solid rgba(0,255,0,0.3); }
.wxn-badge-green { background:rgba(0,255,0,0.12);color:#00ff00;font-family:'JetBrains Mono',monospace;font-size:0.65rem;padding:2px 6px;border-radius:3px;border:1px solid rgba(0,255,0,0.3); }
table.table-hover > tbody > tr:hover > td { background:rgba(0,255,0,0.04) !important; }
table.table > tbody > tr > td { border-color:rgba(0,255,0,0.05) !important;vertical-align:middle; }
</style>

<script>
// ── Color picker ↔ hex text sync ──
function syncHex(picker, hexId) {
    var el = document.getElementById(hexId);
    if (el) el.value = picker.value;
    // also update the hidden input if name differs from id
    var name = picker.getAttribute('name');
    if (name && picker.form) {
        var hidden = picker.form.querySelector('[name="'+name+'"]');
        if (hidden && hidden !== picker) hidden.value = picker.value;
    }
}
function syncPicker(hexInput, pickerName) {
    var form = hexInput.closest('form');
    if (!form) return;
    var picker = form.querySelector('input[type="color"][name="'+pickerName+'"]');
    if (picker) { try { picker.value = hexInput.value.substring(0,7); } catch(e){} }
    // update var
    var varName = hexInput.dataset.var;
    if (varName) document.documentElement.style.setProperty(varName, hexInput.value);
}

// ── Theme tab navigation ──
document.querySelectorAll('.wxn-tnav').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.wxn-tnav').forEach(function(b){ b.classList.remove('active'); });
        document.querySelectorAll('.wxn-tpane').forEach(function(p){ p.classList.remove('active'); });
        btn.classList.add('active');
        var target = document.getElementById(btn.dataset.target);
        if (target) target.classList.add('active');
    });
});

// ── Color preset buttons ──
document.querySelectorAll('.wxn-preset').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var color = btn.dataset.color;
        var fieldName = btn.dataset.name;
        var hexId = btn.dataset.hexid;
        // Update the picker
        var form = btn.closest('form');
        if (form) {
            var picker = form.querySelector('input[type="color"][name="'+fieldName+'"]');
            if (picker) { try { picker.value = color.substring(0,7); } catch(e){} }
        }
        if (hexId) {
            var hexEl = document.getElementById(hexId);
            if (hexEl) hexEl.value = color;
        }
        // Update the form input
        if (form) {
            var textInput = form.querySelector('input[type="text"][name="'+fieldName+'"]');
            if (textInput && !textInput.classList.contains('wxn-color-hex')) textInput.value = color;
        }
        // Live preview for page background
        if (fieldName === 'page_bg') document.documentElement.style.setProperty('--wxn-bg', color);
        if (fieldName === 'accent_color') {
            document.documentElement.style.setProperty('--wxn-neon', color);
        }
        // Highlight active preset
        var parentPresets = btn.parentElement.querySelectorAll('.wxn-preset');
        parentPresets.forEach(function(p){ p.style.outline='none'; });
        btn.style.outline = '2px solid #fff';
    });
});

// ── Font preset buttons ──
document.querySelectorAll('.wxn-font-preset').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var font = btn.dataset.font;
        var targetName = btn.dataset.target;
        var form = btn.closest('form');
        if (form) {
            var input = form.querySelector('input[name="'+targetName+'"]');
            if (input) input.value = font;
        }
        // Highlight
        btn.parentElement.querySelectorAll('.wxn-font-preset').forEach(function(b){ b.style.borderColor='rgba(0,255,0,0.15)'; b.style.fontWeight=''; });
        btn.style.borderColor = 'rgba(0,255,0,0.5)';
        btn.style.fontWeight = '700';
    });
});

// ── Button preview live update ──
function updateBtnPreview() {
    var fields = {
        start:   ['hex_btn_start_bg','hex_btn_start_text','btn_start_border','prev-start','--wxn-btn-start-bg','--wxn-btn-start-text','--wxn-btn-start-border'],
        restart: ['hex_btn_restart_bg','hex_btn_restart_text','btn_restart_border','prev-restart','--wxn-btn-restart-bg','--wxn-btn-restart-text','--wxn-btn-restart-border'],
        stop:    ['hex_btn_stop_bg','hex_btn_stop_text','btn_stop_border','prev-stop','--wxn-btn-stop-bg','--wxn-btn-stop-text','--wxn-btn-stop-border'],
    };
    Object.values(fields).forEach(function(f) {
        var bg   = (document.getElementById(f[0])||{}).value||'';
        var text = (document.getElementById(f[1])||{}).value||'';
        var brd  = (document.getElementById(f[2])||{}).value||'';
        var btn  = document.getElementById(f[3]);
        if (btn) {
            if (bg)   btn.style.background   = bg;
            if (text) btn.style.color        = text;
            if (brd)  btn.style.borderColor  = brd;
        }
        // update CSS vars for real page preview
        if (bg)   document.documentElement.style.setProperty(f[4], bg);
        if (text) document.documentElement.style.setProperty(f[5], text);
        if (brd)  document.documentElement.style.setProperty(f[6], brd);
    });
}

// ── Tab visibility toggles ──
function updateTabCard(checkbox) {
    var label = checkbox.closest('label');
    if (!label) return;
    var on = checkbox.checked;
    label.style.border        = on ? '1px solid rgba(255,60,60,0.3)' : '1px solid rgba(0,255,0,0.2)';
    label.style.background    = on ? 'rgba(255,0,0,0.05)' : 'rgba(0,255,0,0.03)';
    var iconEl   = label.querySelector('.wxn-tab-icon');
    var statusEl = label.querySelector('.wxn-tab-status');
    var textEl   = label.querySelectorAll('span')[1];
    if (iconEl)   iconEl.style.color   = on ? 'rgba(255,100,100,0.7)' : 'var(--wxn-neon)';
    if (textEl)   textEl.style.color   = on ? 'rgba(255,150,150,0.7)' : 'rgba(180,255,180,0.8)';
    if (statusEl) {
        statusEl.textContent   = on ? 'HIDDEN' : 'VISIBLE';
        statusEl.style.background = on ? 'rgba(255,0,0,0.15)' : 'rgba(0,255,0,0.1)';
        statusEl.style.color      = on ? '#ff6666' : 'var(--wxn-neon)';
    }
}
function setAllTabs(disabled) {
    document.querySelectorAll('.wxn-tab-checkbox').forEach(function(cb) {
        cb.checked = disabled;
        updateTabCard(cb);
    });
}

// ── Font size preset buttons ──
function setFontSize(sz) {
    var slider  = document.getElementById('wxn-fsize-slider');
    var display = document.getElementById('wxn-fsize-display');
    if (slider)  slider.value = sz;
    if (display) display.textContent = sz + 'px';
    document.documentElement.style.setProperty('--wxn-font-size-base', sz + 'px');
    [10,12,14,16,18,20].forEach(function(s) {
        var btn = document.getElementById('wxn-fsize-preset-' + s);
        if (!btn) return;
        btn.style.borderColor = (s === sz) ? 'rgba(0,255,0,0.5)' : 'rgba(0,255,0,0.15)';
    });
}

// ── Button position picker ──
function selectBtnPos(pos) {
    document.getElementById('wxn-btn-pos-val').value = pos;
    document.querySelectorAll('.wxn-pos-card').forEach(function(card) {
        var isActive = card.getAttribute('onclick') === "selectBtnPos('" + pos + "')";
        card.style.borderColor = isActive ? 'rgba(0,255,0,0.7)'  : 'rgba(0,255,0,0.15)';
        card.style.background  = isActive ? 'rgba(0,255,0,0.1)'  : 'rgba(0,0,0,0.2)';
        var label = card.querySelector('div:nth-child(2)');
        if (label) label.style.color = isActive ? 'var(--wxn-neon)' : 'rgba(200,255,200,0.6)';
        if (isActive) card.classList.add('wxn-pos-active');
        else          card.classList.remove('wxn-pos-active');
    });
}

// ── Button order drag-and-drop ──
(function() {
    var container = document.getElementById('wxn-btn-sort');
    if (!container) return;
    var dragged = null;

    function updateOrderInput() {
        var order = [];
        container.querySelectorAll('.wxn-sortable-btn').forEach(function(el) {
            order.push(el.getAttribute('data-key'));
        });
        var input = document.getElementById('wxn-btn-order-val');
        if (input) input.value = JSON.stringify(order);
    }

    container.addEventListener('dragstart', function(e) {
        dragged = e.target.closest('.wxn-sortable-btn');
        if (!dragged) return;
        dragged.style.opacity = '0.4';
        dragged.style.boxShadow = '0 0 12px rgba(0,255,0,0.4)';
        e.dataTransfer.effectAllowed = 'move';
    });
    container.addEventListener('dragend', function(e) {
        if (dragged) {
            dragged.style.opacity = '';
            dragged.style.boxShadow = '';
            dragged = null;
        }
        updateOrderInput();
    });
    container.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        var target = e.target.closest('.wxn-sortable-btn');
        if (!target || target === dragged) return;
        var rect = target.getBoundingClientRect();
        var mid  = rect.left + rect.width / 2;
        if (e.clientX < mid) {
            container.insertBefore(dragged, target);
        } else {
            container.insertBefore(dragged, target.nextSibling);
        }
    });
})();

// ── On submit: ensure text inputs (which support rgba) override color pickers ──
var themeForm = document.getElementById('wxn-theme-form');
if (themeForm) {
    themeForm.addEventListener('submit', function() {
        var textOverrides = [
            ['hex_accent',          'accent_color'],
            ['hex_page_bg',         'page_bg'],
            ['hex_nav_bg',          'nav_bg'],
            ['hex_nav_text',        'nav_text'],
            ['hex_sidebar_bg',      'sidebar_bg'],
            ['hex_sidebar_text',    'sidebar_text'],
            ['hex_card_border',     'card_border'],
            ['hex_console_bg',      'console_bg'],
            ['hex_console_cursor',  'console_cursor'],
            ['hex_console_white',   'console_white'],
            ['hex_console_green',   'console_green'],
            ['hex_console_red',     'console_red'],
            ['hex_console_yellow',  'console_yellow'],
            ['hex_console_cyan',    'console_cyan'],
            ['hex_btn_start_bg',    'btn_start_bg'],
            ['hex_btn_start_text',  'btn_start_text'],
            ['hex_btn_restart_bg',  'btn_restart_bg'],
            ['hex_btn_restart_text','btn_restart_text'],
            ['hex_btn_stop_bg',     'btn_stop_bg'],
            ['hex_btn_stop_text',   'btn_stop_text'],
        ];
        textOverrides.forEach(function(pair) {
            var el = document.getElementById(pair[0]);
            if (!el || !el.value) return;
            var hidden = document.createElement('input');
            hidden.type  = 'hidden';
            hidden.name  = pair[1];
            hidden.value = el.value;
            themeForm.appendChild(hidden);
        });
    });
}

// ── Announcement toggle ──
(function() {
    var cb     = document.getElementById('ann-active-toggle');
    var track  = document.getElementById('ann-toggle-track');
    var knob   = document.getElementById('ann-toggle-knob');
    if (!cb) return;
    function applyState(on) {
        track.style.background    = on ? '#00ff00' : 'rgba(255,255,255,0.1)';
        track.style.borderColor   = on ? '#00ff00' : 'rgba(255,255,255,0.2)';
        knob.style.left           = on ? '22px' : '2px';
        knob.style.background     = on ? '#000' : 'rgba(255,255,255,0.5)';
    }
    cb.addEventListener('change', function() { applyState(this.checked); });
})();

// ── Confirm dialogs for admin toggle ──
document.querySelectorAll('.wxn-confirm-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        var msg    = form.dataset.confirm || 'Are you sure?';
        var detail = form.dataset.confirmDetail || '';
        var type   = form.dataset.confirmType || 'warning';
        var fullMsg = detail ? msg + '\n\n' + detail : msg;
        if (!confirm(fullMsg)) e.preventDefault();
    });
});
</script>
@endsection
