@extends('layouts.admin')

@section('title')
    Plan — {{ $plan->name }}
@endsection

@section('content-header')
    <h1>{{ $plan->name }}<small>Edit plan details, pricing, and resource limits.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.plans') }}">Plans</a></li>
        <li class="active">{{ $plan->name }}</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-tag"></i> Plan Details</h3>
            </div>
            <form action="{{ route('admin.plans.view', $plan->id) }}" method="POST">
                {!! csrf_field() !!}
                {!! method_field('PATCH') !!}
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Plan Name <span class="field-required">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $plan->name) }}" required />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Price ($/month) <span class="field-required">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-addon">$</span>
                                    <input type="number" name="price" class="form-control" value="{{ old('price', $plan->price) }}" step="0.01" min="0" required />
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" class="form-control" rows="3">{{ old('description', $plan->description) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <hr />
                    <h5><i class="fa fa-microchip"></i> Resource Limits</h5>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>RAM (MB) <span class="field-required">*</span></label>
                                <input type="number" name="memory" class="form-control" value="{{ old('memory', $plan->memory) }}" min="0" required />
                                <p class="text-muted small">Currently: <strong>{{ $plan->memory_formatted }}</strong> &mdash; 0 = Unlimited</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>CPU (%) <span class="field-required">*</span></label>
                                <input type="number" name="cpu" class="form-control" value="{{ old('cpu', $plan->cpu) }}" min="0" max="10000" required />
                                <p class="text-muted small">100% = 1 core &mdash; 0 = Unlimited</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Disk (MB) <span class="field-required">*</span></label>
                                <input type="number" name="disk" class="form-control" value="{{ old('disk', $plan->disk) }}" min="0" required />
                                <p class="text-muted small">Currently: <strong>{{ $plan->disk_formatted }}</strong> &mdash; 0 = Unlimited</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Block I/O</label>
                                <input type="number" name="io" class="form-control" value="{{ old('io', $plan->io) }}" min="10" max="1000" required />
                                <p class="text-muted small">10–1000</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Databases</label>
                                <input type="number" name="databases" class="form-control" value="{{ old('databases', $plan->databases) }}" min="0" required />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Backups</label>
                                <input type="number" name="backups" class="form-control" value="{{ old('backups', $plan->backups) }}" min="0" required />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Featured Plan?</label>
                                <div>
                                    <input type="hidden" name="is_featured" value="0" />
                                    <input type="checkbox" name="is_featured" value="1" {{ $plan->is_featured ? 'checked' : '' }} />
                                    <span class="text-muted">Highlight this plan</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Active?</label>
                                <div>
                                    <input type="hidden" name="is_active" value="0" />
                                    <input type="checkbox" name="is_active" value="1" {{ $plan->is_active ? 'checked' : '' }} />
                                    <span class="text-muted">Plan is visible/usable</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    <button type="submit" class="btn btn-primary btn-sm pull-right">
                        <i class="fa fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-4">
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-eye"></i> Plan Preview</h3>
            </div>
            <div class="box-body" style="text-align:center; padding:20px;">
                @if ($plan->is_featured)
                    <span class="label label-success" style="font-size:12px;">Featured</span><br><br>
                @endif
                <h3 style="margin-top:0;">{{ $plan->name }}</h3>
                <h1 style="color:#3c8dbc; margin:10px 0;">
                    ${{ number_format($plan->price, 2) }}
                    <small style="font-size:14px;">/mo</small>
                </h1>
                @if ($plan->description)
                    <p class="text-muted">{{ $plan->description }}</p>
                @endif
                <hr />
                <ul class="list-unstyled" style="text-align:left;">
                    <li><i class="fa fa-memory fa-fw text-muted"></i> <strong>{{ $plan->memory_formatted }}</strong> RAM</li>
                    <li><i class="fa fa-microchip fa-fw text-muted"></i> <strong>{{ $plan->cpu_formatted }}</strong> CPU</li>
                    <li><i class="fa fa-hdd-o fa-fw text-muted"></i> <strong>{{ $plan->disk_formatted }}</strong> Disk</li>
                    <li><i class="fa fa-database fa-fw text-muted"></i> <strong>{{ $plan->databases }}</strong> Databases</li>
                    <li><i class="fa fa-archive fa-fw text-muted"></i> <strong>{{ $plan->backups }}</strong> Backups</li>
                </ul>
            </div>
            <div class="box-footer text-center">
                @if ($plan->is_active)
                    <span class="label label-success">Active</span>
                @else
                    <span class="label label-default">Inactive</span>
                @endif
            </div>
        </div>

        <div class="box box-danger">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-trash"></i> Delete Plan</h3>
            </div>
            <div class="box-body">
                <p class="small text-muted">Deleting this plan is permanent and cannot be undone.</p>
                <form action="{{ route('admin.plans.view', $plan->id) }}" method="POST">
                    {!! csrf_field() !!}
                    {!! method_field('PATCH') !!}
                    <input type="hidden" name="action" value="delete" />
                    <button type="submit" class="btn btn-danger btn-sm"
                        onclick="return confirm('Are you sure you want to delete this plan?')">
                        <i class="fa fa-trash-o"></i> Delete Plan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
