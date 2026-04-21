@extends('layouts.admin')

@section('title')
    Administration
@endsection

@section('content-header')
    <h1>Administrative Overview<small>A quick glance at your system.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Overview</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box {{ $version->isLatestPanel() ? 'box-success' : 'box-danger' }}">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-fw fa-info-circle"></i> System Information</h3>
            </div>
            <div class="box-body">
                @if ($version->isLatestPanel())
                    <i class="fa fa-check-circle" style="color:#00ff00;"></i>
                    You are running <strong>wolfXcore</strong> version <code>{{ config('app.version') }}</code>. Your panel is up-to-date.
                @else
                    <i class="fa fa-exclamation-triangle" style="color:#ff5252;"></i>
                    Your panel is <strong>not up-to-date!</strong> The latest version is
                    <a href="https://github.com/sil3nt-wolf/wolfXcore/releases" target="_blank"><code>{{ $version->getPanel() }}</code></a>
                    and you are currently running <code>{{ config('app.version') }}</code>.
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Quick Stats --}}
<div class="row" style="margin-bottom:8px;">
    <div class="col-xs-12 col-sm-3">
        <div class="info-box">
            <span class="info-box-icon"><i class="fa fa-server"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Servers</span>
                <span class="info-box-number">{{ \Pterodactyl\Models\Server::count() }}</span>
            </div>
        </div>
    </div>
    <div class="col-xs-12 col-sm-3">
        <div class="info-box">
            <span class="info-box-icon"><i class="fa fa-users"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Users</span>
                <span class="info-box-number">{{ \Pterodactyl\Models\User::count() }}</span>
            </div>
        </div>
    </div>
    <div class="col-xs-12 col-sm-3">
        <div class="info-box">
            <span class="info-box-icon"><i class="fa fa-sitemap"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Nodes</span>
                <span class="info-box-number">{{ \Pterodactyl\Models\Node::count() }}</span>
            </div>
        </div>
    </div>
    <div class="col-xs-12 col-sm-3">
        <div class="info-box">
            <span class="info-box-icon"><i class="fa fa-tag"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Active Plans</span>
                <span class="info-box-number">{{ \Pterodactyl\Models\Plan::where('is_active', true)->count() }}</span>
            </div>
        </div>
    </div>
</div>

{{-- Action Buttons --}}
<div class="row" style="margin-top:10px;">
    <div class="col-xs-6 col-sm-3 text-center" style="margin-bottom:10px;">
        <a href="https://discord.gg/tNYvK42j" target="_blank">
            <button class="btn btn-warning" style="width:100%;">
                <i class="fa fa-fw fa-comments"></i> Get Help
                <small>(Discord)</small>
            </button>
        </a>
    </div>
    <div class="col-xs-6 col-sm-3 text-center" style="margin-bottom:10px;">
        <a href="https://github.com/sil3nt-wolf/wolfXcore/wiki" target="_blank">
            <button class="btn btn-primary" style="width:100%;">
                <i class="fa fa-fw fa-book"></i> Documentation
            </button>
        </a>
    </div>
    <div class="clearfix visible-xs-block">&nbsp;</div>
    <div class="col-xs-6 col-sm-3 text-center" style="margin-bottom:10px;">
        <a href="https://github.com/sil3nt-wolf/wolfXcore" target="_blank">
            <button class="btn btn-primary" style="width:100%;">
                <i class="fa fa-fw fa-github"></i> GitHub
            </button>
        </a>
    </div>
    <div class="col-xs-6 col-sm-3 text-center" style="margin-bottom:10px;">
        <a href="https://github.com/sil3nt-wolf/wolfXcore" target="_blank">
            <button class="btn btn-success" style="width:100%;">
                <i class="fa fa-fw fa-star"></i> Support the Project
            </button>
        </a>
    </div>
</div>
@endsection
