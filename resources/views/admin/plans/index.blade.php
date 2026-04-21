@extends('layouts.admin')

@section('title')
    Plans
@endsection

@section('content-header')
    <h1>Server Plans<small>Define pricing and resource allocations for your hosting packages.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Plans</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Plan List</h3>
                <div class="box-tools">
                    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#newPlanModal">
                        <i class="fa fa-plus"></i> Create New Plan
                    </button>
                </div>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tbody>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Price / mo</th>
                            <th>RAM</th>
                            <th>CPU</th>
                            <th>Disk</th>
                            <th>DBs</th>
                            <th>Backups</th>
                            <th>Status</th>
                        </tr>
                        @forelse ($plans as $plan)
                            <tr>
                                <td><code>{{ $plan->id }}</code></td>
                                <td>
                                    <a href="{{ route('admin.plans.view', $plan->id) }}">{{ $plan->name }}</a>
                                    @if ($plan->is_featured)
                                        <span class="label label-success" style="margin-left:4px;">Featured</span>
                                    @endif
                                </td>
                                <td><strong>${{ number_format($plan->price, 2) }}</strong></td>
                                <td>{{ $plan->memory_formatted }}</td>
                                <td>{{ $plan->cpu_formatted }}</td>
                                <td>{{ $plan->disk_formatted }}</td>
                                <td>{{ $plan->databases }}</td>
                                <td>{{ $plan->backups }}</td>
                                <td>
                                    @if ($plan->is_active)
                                        <span class="label label-success">Active</span>
                                    @else
                                        <span class="label label-default">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted" style="padding:20px;">
                                    No plans created yet. Click <strong>Create New Plan</strong> to get started.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Create Plan Modal --}}
<div class="modal fade" id="newPlanModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.plans') }}" method="POST">
                {!! csrf_field() !!}
                <div class="modal-header" style="display:flex;align-items:center;justify-content:space-between;">
                    <h4 class="modal-title" style="margin:0;"><i class="fa fa-tag"></i> Create New Plan</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                        style="color:#00ff00;opacity:0.8;font-size:1.5rem;font-weight:300;line-height:1;background:none;border:none;cursor:pointer;padding:0 4px;float:none;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Plan Name <span class="field-required">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Starter, Pro, Enterprise" required />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Price ($/month) <span class="field-required">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-addon">$</span>
                                    <input type="number" name="price" class="form-control" placeholder="0.00" step="0.01" min="0" value="0.00" required />
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" class="form-control" rows="2" placeholder="Short description shown to users..."></textarea>
                            </div>
                        </div>
                    </div>
                    <hr style="margin:10px 0;" />
                    <h5><i class="fa fa-microchip"></i> Resource Limits</h5>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>RAM (MB) <span class="field-required">*</span></label>
                                <input type="number" name="memory" class="form-control" value="512" min="0" required />
                                <p class="text-muted small">1024 MB = 1 GB &mdash; 0 = Unlimited</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>CPU (%) <span class="field-required">*</span></label>
                                <input type="number" name="cpu" class="form-control" value="100" min="0" max="10000" required />
                                <p class="text-muted small">100% = 1 core &mdash; 0 = Unlimited</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Disk (MB) <span class="field-required">*</span></label>
                                <input type="number" name="disk" class="form-control" value="5120" min="0" required />
                                <p class="text-muted small">1024 MB = 1 GB &mdash; 0 = Unlimited</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Block I/O</label>
                                <input type="number" name="io" class="form-control" value="500" min="10" max="1000" required />
                                <p class="text-muted small">10–1000</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Databases</label>
                                <input type="number" name="databases" class="form-control" value="0" min="0" required />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Backups</label>
                                <input type="number" name="backups" class="form-control" value="0" min="0" required />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Featured Plan?</label>
                                <div>
                                    <input type="hidden" name="is_featured" value="0" />
                                    <input type="checkbox" name="is_featured" value="1" /> <span class="text-muted">Highlight this plan</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Active?</label>
                                <div>
                                    <input type="hidden" name="is_active" value="0" />
                                    <input type="checkbox" name="is_active" value="1" checked /> <span class="text-muted">Plan is visible/usable</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="display:flex;align-items:center;justify-content:space-between;">
                    <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">
                        <i class="fa fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="fa fa-save"></i> Create Plan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
