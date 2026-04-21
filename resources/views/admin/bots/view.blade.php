@extends('layouts.admin')

@section('title', isset($repo) && $repo->exists ? 'Edit: ' . $repo->name : 'New Bot Repo')

@section('content-header')
    <h1>{{ isset($repo) && $repo->exists ? $repo->name : 'New Bot Repo' }} <small>{{ isset($repo) && $repo->exists ? 'Manage repo and server slots' : 'Add a new bot repository' }}</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.bots') }}">Bots</a></li>
        <li class="active">{{ isset($repo) && $repo->exists ? $repo->name : 'New' }}</li>
    </ol>
@endsection

@section('content')
<div class="row">
    {{-- Left: Repo Form --}}
    <div class="col-sm-8">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-code-fork" style="color:#00ff00;"></i>
                    {{ isset($repo) && $repo->exists ? 'Edit Repository' : 'New Repository' }}
                </h3>
                @if(isset($repo) && $repo->exists)
                <div class="box-tools">
                    <form action="{{ route('admin.bots.update', $repo->id) }}" method="POST" style="display:inline">
                        @csrf @method('PATCH')
                        <input type="hidden" name="action" value="refresh">
                        <button type="submit" class="btn btn-xs btn-info" onclick="return confirm('Refresh app.json from GitHub?')">
                            <i class="fa fa-refresh"></i> Refresh app.json
                        </button>
                    </form>
                </div>
                @endif
            </div>
            <div class="box-body">
                {{-- URL Fetcher --}}
                @unless(isset($repo) && $repo->exists)
                <div class="form-group">
                    <label>GitHub Repository URL</label>
                    <div class="input-group">
                        <input type="url" id="fetchUrl" class="form-control" placeholder="https://github.com/owner/repo">
                        <span class="input-group-btn">
                            <button id="fetchBtn" type="button" class="btn btn-info">
                                <i class="fa fa-download"></i> Fetch app.json
                            </button>
                        </span>
                    </div>
                    <p class="help-block">Paste the GitHub URL — we'll read the app.json automatically.</p>
                </div>
                <hr>
                @endunless

                <form id="repoForm" action="{{ isset($repo) && $repo->exists ? route('admin.bots.update', $repo->id) : route('admin.bots.store') }}" method="POST">
                    @csrf
                    @if(isset($repo) && $repo->exists) @method('PATCH') @endif

                    <input type="hidden" name="env_schema" id="envSchemaInput" value="{{ $repo->env_schema ?? '' }}">
                    <input type="hidden" name="app_json_raw" id="appJsonRaw" value="">

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Bot Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $repo->name ?? '') }}" required>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Main File <span class="text-danger">*</span></label>
                                <input type="text" name="main_file" id="mainFileInput" class="form-control" value="{{ old('main_file', $repo->main_file ?? 'index.js') }}" required placeholder="index.js">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description', $repo->description ?? '') }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-sm-8">
                            <div class="form-group">
                                <label>Repo URL (for display)</label>
                                <input type="url" name="repo_url" id="repoUrlInput" class="form-control" value="{{ old('repo_url', $repo->repo_url ?? '') }}" placeholder="https://github.com/owner/repo">
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>Active?</label>
                                <select name="is_active" class="form-control">
                                    <option value="1" {{ ($repo->is_active ?? true) ? 'selected' : '' }}>Yes</option>
                                    <option value="0" {{ !($repo->is_active ?? true) ? 'selected' : '' }}>No</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Git Address (clone URL)</label>
                        <input type="text" name="git_address" id="gitAddressInput" class="form-control" value="{{ old('git_address', $repo->git_address ?? '') }}" placeholder="https://github.com/owner/repo.git">
                        <p class="help-block">Used by containers to clone the repo on startup.</p>
                    </div>

                    <div class="form-group">
                        <label>Bot Image URL</label>
                        <div class="input-group">
                            <input type="text" name="image_url" id="imageUrlInput" class="form-control" value="{{ old('image_url', $repo->image_url ?? '') }}" placeholder="https://...">
                            <span class="input-group-addon">
                                <img id="imagePreview" src="{{ $repo->image_url ?? '' }}" alt="" style="width:24px;height:24px;object-fit:cover;display:{{ isset($repo) && $repo->image_url ? 'block' : 'none' }}">
                            </span>
                        </div>
                    </div>

                    {{-- Parsed env fields preview --}}
                    <div id="envPreview" style="{{ isset($repo) && $repo->env_schema ? '' : 'display:none' }}">
                        <label>Config Fields (from app.json)</label>
                        <div id="envFields" class="well" style="background:#0d1a0d;border-color:#00ff00;padding:12px;">
                            @if(isset($repo) && $repo->exists)
                            @foreach(json_decode($repo->env_schema ?? '[]', true) ?? [] as $field)
                            <div style="display:flex;gap:8px;margin-bottom:6px;align-items:center;">
                                <code style="background:#1a2e1a;color:#00ff00;padding:3px 8px;border-radius:4px;min-width:140px;">{{ $field['key'] }}</code>
                                <small style="color:#aaa;">{{ $field['description'] }}</small>
                                @if($field['required'])<span class="label label-danger">required</span>@endif
                            </div>
                            @endforeach
                            @endif
                        </div>
                    </div>

                    <div class="form-group" style="margin-top:20px;">
                        <button type="submit" class="btn btn-success">
                            <i class="fa fa-save"></i> {{ isset($repo) && $repo->exists ? 'Save Changes' : 'Create Bot Repo' }}
                        </button>
                        @if(isset($repo) && $repo->exists)
                        <button type="button" onclick="deleteRepo()" class="btn btn-danger pull-right">
                            <i class="fa fa-trash"></i> Delete Repo
                        </button>
                        @endif
                    </div>
                </form>

                @if(isset($repo) && $repo->exists)
                <form id="deleteForm" action="{{ route('admin.bots.update', $repo->id) }}" method="POST" style="display:none">
                    @csrf @method('PATCH')
                    <input type="hidden" name="action" value="delete">
                </form>
                @endif
            </div>
        </div>
    </div>

    {{-- Right: Slot Preparation --}}
    @if(isset($repo) && $repo->exists)
    <div class="col-sm-4">
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-server" style="color:#00ff00;"></i> Prepare Server Slots</h3>
            </div>
            <div class="box-body">
                <div style="display:flex;gap:10px;margin-bottom:16px;text-align:center;">
                    <div style="flex:1;background:#0d1a0d;border:1px solid #00ff00;border-radius:6px;padding:12px;">
                        <div style="font-size:22px;font-weight:700;color:#00ff00;">{{ $allocations->where('status','available')->count() }}</div>
                        <div style="font-size:11px;color:#aaa;">Available</div>
                    </div>
                    <div style="flex:1;background:#0d1a0d;border:1px solid #ffa500;border-radius:6px;padding:12px;">
                        <div style="font-size:22px;font-weight:700;color:#ffa500;">{{ $allocations->where('status','assigned')->count() }}</div>
                        <div style="font-size:11px;color:#aaa;">Assigned</div>
                    </div>
                    <div style="flex:1;background:#0d1a0d;border:1px solid #666;border-radius:6px;padding:12px;">
                        <div style="font-size:22px;font-weight:700;color:#fff;">{{ $allocations->count() }}</div>
                        <div style="font-size:11px;color:#aaa;">Total</div>
                    </div>
                </div>

                <form action="{{ route('admin.bots.prepare', $repo->id) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>Plan (specs for new slots)</label>
                        <select name="plan_id" class="form-control" required>
                            @foreach($plans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }} — KES {{ number_format($plan->price) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Number of Slots to Prepare</label>
                        <input type="number" name="slots" class="form-control" value="1" min="1" max="50" required>
                        <p class="help-block" style="color:#aaa;font-size:11px;">Each slot is a real provisioned server sitting ready for a user.</p>
                    </div>
                    <button type="submit" class="btn btn-success btn-block" onclick="return confirm('Provision servers now? This cannot be undone.')">
                        <i class="fa fa-server"></i> Prepare Slots
                    </button>
                </form>
            </div>
        </div>

        {{-- Allocations list --}}
        @if($allocations->count() > 0)
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">Server Slots</h3>
            </div>
            <div class="box-body no-padding" style="max-height:300px;overflow-y:auto;">
                <table class="table table-condensed table-hover" style="font-size:12px;">
                    <thead><tr><th>UUID</th><th>Status</th><th>User</th><th></th></tr></thead>
                    <tbody>
                        @foreach($allocations as $alloc)
                        <tr>
                            <td><code style="font-size:10px;">{{ substr($alloc->server_uuid ?? 'N/A', 0, 8) }}...</code></td>
                            <td>
                                @if($alloc->status === 'available') <span class="label label-success">available</span>
                                @elseif($alloc->status === 'assigned') <span class="label label-warning">assigned</span>
                                @elseif($alloc->status === 'error') <span class="label label-danger">error</span>
                                @else <span class="label label-default">{{ $alloc->status }}</span>
                                @endif
                            </td>
                            <td>{{ $alloc->user_id ? '#'.$alloc->user_id : '—' }}</td>
                            <td>
                                @if($alloc->status !== 'assigned')
                                <form action="{{ route('admin.bots.slot.remove', [$repo->id, $alloc->id]) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Remove slot?')"><i class="fa fa-times"></i></button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
    @endif
</div>

@endsection

@section('footer-scripts')
@parent
<script>
@unless(isset($repo) && $repo->exists)
document.getElementById('fetchBtn').addEventListener('click', function () {
    const url = document.getElementById('fetchUrl').value.trim();
    if (!url) return alert('Enter a GitHub URL first.');
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Fetching...';

    fetch('/admin/bots/fetch-app-json', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ repo_url: url }),
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false; btn.innerHTML = '<i class="fa fa-download"></i> Fetch app.json';
        if (!res.success) { alert('Error: ' + res.error); return; }
        const d = res.data;
        document.querySelector('[name=name]').value       = d.name || '';
        document.querySelector('[name=description]').value = d.description || '';
        document.getElementById('mainFileInput').value    = d.main_file || 'index.js';
        document.getElementById('repoUrlInput').value     = url;
        document.getElementById('gitAddressInput').value  = d.git_address || '';
        document.getElementById('imageUrlInput').value    = d.image_url || '';
        document.getElementById('envSchemaInput').value   = d.env_schema || '[]';
        document.getElementById('appJsonRaw').value       = d.app_json_raw || '';
        if (d.image_url) {
            const img = document.getElementById('imagePreview');
            img.src = d.image_url; img.style.display = 'block';
        }
        renderEnvFields(JSON.parse(d.env_schema || '[]'));
    })
    .catch(e => { btn.disabled = false; btn.innerHTML = '<i class="fa fa-download"></i> Fetch app.json'; alert('Fetch failed: ' + e.message); });
});
@endunless

function renderEnvFields(schema) {
    const container = document.getElementById('envFields');
    const wrapper   = document.getElementById('envPreview');
    if (!schema || !schema.length) { wrapper.style.display = 'none'; return; }
    wrapper.style.display = '';
    container.innerHTML = schema.map(f =>
        `<div style="display:flex;gap:8px;margin-bottom:6px;align-items:center;">
            <code style="background:#1a2e1a;color:#00ff00;padding:3px 8px;border-radius:4px;min-width:140px;">${f.key}</code>
            <small style="color:#aaa;">${f.description}</small>
            ${f.required ? '<span class="label label-danger">required</span>' : ''}
        </div>`
    ).join('');
}

@if(isset($repo) && $repo->exists)
function deleteRepo() {
    if (confirm('Delete "{{ $repo->name }}"? This will also remove all unassigned slots.')) {
        document.getElementById('deleteForm').submit();
    }
}
@endif
</script>
@endsection
