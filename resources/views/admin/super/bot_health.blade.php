@extends('layouts.admin')

@section('title', 'Bot Health')

@section('content-header')
    <h1>Bot Health <small style="color:var(--wxn-neon);font-family:'Orbitron',monospace;">Stability Monitor</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.super.index') }}">Super Admin</a></li>
        <li class="active">Bot Health</li>
    </ol>
@endsection

@section('content')
@php
    $pct = $effectiveCap > 0 ? min(100, round($committed / $effectiveCap * 100)) : 0;
    $pctColor = $pct >= 100 ? '#ff4444' : ($pct >= 80 ? '#ff9900' : '#00ff00');

    // Summary tiles. The 24h numbers come from the append-only wxn_bot_crashes event log
    // (NOT from wxn_bot_health.crash_count_24h which is a 10-min token-bucket approximation
    // tuned for the breaker — totally unsuitable for reporting).
    $totalBots          = \Pterodactyl\Models\Server::count();
    $pausedNow          = $paused->count();
    $since24h           = now()->subHours(24);
    // Crashes / OOM / restores all come from the append-only wxn_bot_crashes event log
    // so each tile is a true 24h count (one row per event, never decays).
    $crashes24h         = \Illuminate\Support\Facades\DB::table('wxn_bot_crashes')
                            ->where('occurred_at', '>=', $since24h)
                            ->whereIn('event', ['server:power.crashed', 'server:power.oom_killed', 'server:installer.crashed'])
                            ->count();
    $oomEvents24h       = \Illuminate\Support\Facades\DB::table('wxn_bot_crashes')
                            ->where('occurred_at', '>=', $since24h)
                            ->where('event', 'server:power.oom_killed')->count();
    $restores24h        = \Illuminate\Support\Facades\DB::table('wxn_bot_crashes')
                            ->where('occurred_at', '>=', $since24h)
                            ->where('event', 'panel:session.restored')->count();
@endphp

<div class="row" style="margin-bottom:16px;">
    @php
        $tiles = [
            ['label' => 'BOTS DEPLOYED',      'value' => number_format($totalBots),                'color' => 'var(--wxn-neon)'],
            ['label' => 'PAUSED RIGHT NOW',   'value' => number_format($pausedNow),                'color' => $pausedNow > 0 ? '#ff6666' : 'var(--wxn-neon)'],
            ['label' => 'CRASHES (24H)',      'value' => number_format((int) $crashes24h),         'color' => $crashes24h > 20 ? '#ff9900' : 'rgba(255,255,255,0.7)'],
            ['label' => 'OOM KILLS (24H)',    'value' => number_format((int) $oomEvents24h),       'color' => $oomEvents24h > 0 ? '#ff4444' : 'rgba(255,255,255,0.7)'],
            ['label' => 'SESSION RESTORES',   'value' => number_format((int) $restores24h),        'color' => 'rgba(255,255,255,0.7)'],
        ];
    @endphp
    @foreach($tiles as $t)
        <div class="col-xs-6 col-md-2-4" style="width:20%;float:left;padding:0 8px;">
            <div style="padding:18px 14px;border:1px solid rgba(0,255,0,0.18);background:rgba(0,15,0,0.55);border-radius:6px;">
                <div style="font-family:'JetBrains Mono',monospace;font-size:0.65rem;letter-spacing:2px;color:rgba(0,255,0,0.6);">{{ $t['label'] }}</div>
                <div style="font-family:'Orbitron',monospace;font-size:1.8rem;color:{{ $t['color'] }};margin-top:4px;line-height:1;">{{ $t['value'] }}</div>
            </div>
        </div>
    @endforeach
    <div style="clear:both;"></div>
</div>

<div class="row">
    <div class="col-xs-12">
        <div class="box wxn-box">
            <div class="box-header wxn-box-header">
                <h3 class="box-title" style="color:var(--wxn-neon);"><i class="fa fa-heartbeat"></i> NODE CAPACITY</h3>
            </div>
            <div class="box-body" style="padding:20px;">
                <div style="font-family:'JetBrains Mono',monospace;font-size:0.85rem;color:rgba(255,255,255,0.7);margin-bottom:10px;">
                    Node: <strong style="color:var(--wxn-neon);">{{ $node->name ?? '—' }}</strong>
                    &nbsp;|&nbsp; Physical RAM cap: <strong>{{ number_format($nodeMem) }} MB</strong>
                    &nbsp;|&nbsp; Overallocate: <strong>{{ $overallocate }}%</strong>
                    &nbsp;|&nbsp; Effective ceiling: <strong>{{ number_format($effectiveCap) }} MB</strong>
                </div>
                <div style="background:rgba(0,0,0,0.4);height:30px;border-radius:4px;overflow:hidden;border:1px solid rgba(0,255,0,0.2);">
                    <div style="height:100%;width:{{ $pct }}%;background:{{ $pctColor }};transition:width .5s;"></div>
                </div>
                <div style="margin-top:8px;font-family:'Orbitron',monospace;font-size:0.95rem;color:{{ $pctColor }};">
                    {{ number_format($committed) }} MB committed / {{ number_format($effectiveCap) }} MB cap ({{ $pct }}%)
                    @if($pct >= 100)
                        <span style="color:#ff4444;">— ⚠ OVER-COMMITTED. New deployments are refused.</span>
                    @elseif($pct >= 80)
                        <span style="color:#ff9900;">— ⚠ Near limit. Add capacity or migrate before deploying more.</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xs-12">
        <div class="box wxn-box">
            <div class="box-header wxn-box-header">
                <h3 class="box-title" style="color:var(--wxn-neon);"><i class="fa fa-pause-circle"></i> AUTO-PAUSED BOTS ({{ $paused->count() }})</h3>
            </div>
            <div class="box-body" style="padding:0;">
                @if($paused->isEmpty())
                    <div style="padding:20px;color:rgba(255,255,255,0.4);font-family:'JetBrains Mono',monospace;font-size:0.85rem;">
                        No bots are currently paused. The circuit breaker pauses bots after 5 crashes in 10 minutes.
                    </div>
                @else
                <table class="table" style="margin:0;">
                    <thead>
                        <tr style="font-family:'JetBrains Mono',monospace;font-size:0.7rem;color:rgba(0,255,0,0.6);letter-spacing:1px;">
                            <th>SERVER</th><th>CAP</th><th>LIVE RSS</th><th>CRASHES</th><th>LAST REASON</th><th>RESUMES AT</th><th>ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paused as $p)
                            @php
                                $srv = \Pterodactyl\Models\Server::find($p->server_id);
                                $cap = (int) ($srv?->memory ?? 0);
                                $rss = $liveRss[$p->server_id] ?? null;
                                $rssPct = ($cap > 0 && $rss !== null) ? round($rss / $cap * 100) : null;
                                $rssColor = $rssPct === null ? 'rgba(255,255,255,0.4)' : ($rssPct >= 90 ? '#ff4444' : ($rssPct >= 70 ? '#ff9900' : '#00ff88'));
                            @endphp
                            <tr>
                                <td><a href="{{ route('admin.servers.view', $p->server_id) }}" style="color:var(--wxn-neon);">{{ $srv?->name ?? '#'.$p->server_id }}</a></td>
                                <td style="font-family:'JetBrains Mono',monospace;font-size:0.78rem;">{{ $cap }}M</td>
                                <td style="font-family:'JetBrains Mono',monospace;font-size:0.78rem;color:{{ $rssColor }};">
                                    @if($rss === null)—@else{{ $rss }}M @if($rssPct !== null)<small>({{ $rssPct }}%)</small>@endif @endif
                                </td>
                                <td><span style="color:#ff6666;font-family:'Orbitron',monospace;">{{ $p->crash_count_24h }}</span></td>
                                <td style="font-size:0.75rem;color:rgba(255,255,255,0.55);max-width:300px;word-break:break-all;">{{ \Illuminate\Support\Str::limit($p->last_crash_reason, 100) }}</td>
                                <td style="font-family:'JetBrains Mono',monospace;font-size:0.78rem;color:#ff9900;">AWAITING RESET</td>
                                <td>
                                    <form action="{{ route('admin.super.bot-health.reset', $p->server_id) }}" method="POST" style="margin:0;">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-success">RESET</button>
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

<div class="row">
    <div class="col-xs-12">
        <div class="box wxn-box">
            <div class="box-header wxn-box-header">
                <h3 class="box-title" style="color:var(--wxn-neon);"><i class="fa fa-server"></i> TOP 15 RAM-COMMITTED SERVERS</h3>
            </div>
            <div class="box-body" style="padding:0;">
                <table class="table" style="margin:0;">
                    <thead>
                        <tr style="font-family:'JetBrains Mono',monospace;font-size:0.7rem;color:rgba(0,255,0,0.6);letter-spacing:1px;">
                            <th>NAME</th><th>CAP</th><th>LIVE RSS</th><th>SWAP CAP</th><th>STATUS</th><th>CRASHES (24H)</th><th>SESSION RESTORES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($servers as $s)
                            @php
                                $h = $health->get($s->id);
                                $rss = $liveRss[$s->id] ?? null;
                                $rssPct = ($s->memory > 0 && $rss !== null) ? round($rss / $s->memory * 100) : null;
                                $rssColor = $rssPct === null ? 'rgba(255,255,255,0.4)' : ($rssPct >= 90 ? '#ff4444' : ($rssPct >= 70 ? '#ff9900' : '#00ff88'));
                                $c24 = (int) ($crashes24hByServer[$s->id] ?? 0);
                            @endphp
                            <tr>
                                <td><a href="{{ route('admin.servers.view', $s->id) }}" style="color:var(--wxn-neon);">{{ $s->name }}</a>
                                    <div style="font-size:0.65rem;color:rgba(255,255,255,0.3);font-family:monospace;">{{ $s->uuid }}</div>
                                </td>
                                <td>{{ $s->memory }} MB</td>
                                <td style="font-family:'JetBrains Mono',monospace;font-size:0.78rem;color:{{ $rssColor }};">
                                    @if($rss === null)—@else{{ $rss }}M @if($rssPct !== null)<small>({{ $rssPct }}%)</small>@endif @endif
                                </td>
                                <td>{{ $s->swap }} MB</td>
                                <td>{{ $s->status ?: 'running' }}</td>
                                <td>
                                    <span style="color:{{ $c24 >= 3 ? '#ff6666' : 'rgba(255,255,255,0.5)' }};">{{ $c24 }}</span>
                                </td>
                                <td>
                                    {{ $h?->session_restores ?? 0 }}
                                    @if($h?->last_session_restore_at)
                                        <div style="font-size:0.65rem;color:rgba(255,255,255,0.35);">{{ $h->last_session_restore_at->diffForHumans() }}</div>
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

@endsection
