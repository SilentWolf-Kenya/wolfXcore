@extends('layouts.admin')

@section('title', 'Bot Repositories')

@section('content-header')
    <h1>Bot Repositories <small>Manage hosted bot repos and server slots</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Bots</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">All Bot Repositories</h3>
                <div class="box-tools">
                    <a href="{{ route('admin.bots.new') }}" class="btn btn-sm btn-success">
                        <i class="fa fa-plus"></i> Add New Bot Repo
                    </a>
                </div>
            </div>
            <div class="box-body no-padding">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Bot</th>
                            <th>Repo URL</th>
                            <th>Slots</th>
                            <th>Available</th>
                            <th>Assigned</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($repos as $repo)
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    @if($repo->image_url)
                                        <img src="{{ $repo->image_url }}" alt="" style="width:36px;height:36px;border-radius:6px;object-fit:cover;border:1px solid #00ff00;">
                                    @else
                                        <div style="width:36px;height:36px;border-radius:6px;background:#0d1a0d;border:1px solid #00ff00;display:flex;align-items:center;justify-content:center;color:#00ff00;font-size:16px;">
                                            <i class="fa fa-robot"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <strong>{{ $repo->name }}</strong><br>
                                        <small style="color:#999;">{{ Str::limit($repo->description, 50) }}</small>
                                    </div>
                                </div>
                            </td>
                            <td><small><a href="{{ $repo->repo_url }}" target="_blank">{{ Str::limit($repo->repo_url, 40) }}</a></small></td>
                            <td><span class="label label-default">{{ $repo->allocations_count }}</span></td>
                            <td><span class="label label-success">{{ $repo->available_count }}</span></td>
                            <td><span class="label label-warning">{{ $repo->assigned_count }}</span></td>
                            <td>
                                @if($repo->is_active)
                                    <span class="label label-success">Active</span>
                                @else
                                    <span class="label label-danger">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.bots.view', $repo->id) }}" class="btn btn-xs btn-primary">
                                    <i class="fa fa-edit"></i> Manage
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" style="text-align:center;padding:30px;color:#666;">
                                No bot repositories yet. <a href="{{ route('admin.bots.new') }}">Add one now.</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
