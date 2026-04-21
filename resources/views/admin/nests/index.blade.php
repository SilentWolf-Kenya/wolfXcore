@extends('layouts.admin')

@section('title')
    Nests
@endsection

@section('content-header')
    <h1>Nests<small>All nests currently available on this system.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Nests</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="alert alert-danger">
            Eggs are a powerful feature of wolfXcore that allow for extreme flexibility and configuration. Please note that while powerful, modifying an egg wrongly can very easily brick your servers and cause more problems. Please avoid editing default eggs unless you are absolutely sure of what you are doing.
        </div>
    </div>
</div>
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Configured Nests</h3>
                <div class="box-tools">
                    <a href="#" class="btn btn-sm btn-success" data-toggle="modal" data-target="#importServiceOptionModal" role="button"><i class="fa fa-upload"></i> Import Egg</a>
                    <a href="{{ route('admin.nests.new') }}" class="btn btn-primary btn-sm">Create New</a>
                </div>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th class="text-center">Eggs</th>
                        <th class="text-center">Servers</th>
                    </tr>
                    @foreach($nests as $nest)
                        <tr style="cursor:pointer;" onclick="window.location='{{ route('admin.nests.view', $nest->id) }}'">
                            <td class="middle"><code>{{ $nest->id }}</code></td>
                            <td class="middle">{{ $nest->name }} <small class="text-muted">&lt;{{ $nest->author }}&gt;</small></td>
                            <td class="col-xs-6 middle">{{ $nest->description }}</td>
                            <td class="text-center middle">{{ $nest->eggs_count }}</td>
                            <td class="text-center middle">{{ $nest->servers_count }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" tabindex="-1" role="dialog" id="importServiceOptionModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Import an Egg</h4>
            </div>
            <form action="{{ route('admin.nests.egg.import') }}" enctype="multipart/form-data" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="control-label" for="pImportFile">Egg File <span class="field-required"></span></label>
                        <div>
                            <input id="pImportFile" type="file" name="import_file" class="form-control" accept="application/json" />
                            <p class="small text-muted">Select the <code>.json</code> file for the new egg that you wish to import.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Associated Nest <span class="field-required"></span></label>
                        <input type="hidden" name="import_to_nest" id="pImportToNest" value="{{ $nests->first()->id ?? '' }}">
                        <div class="wxn-nest-picker">
                            <div class="wxn-nest-trigger" id="wxnNestTrigger" onclick="wxnToggleNestPicker(event)">
                                <span id="wxnNestLabel">{{ $nests->first() ? $nests->first()->name . ' <' . $nests->first()->author . '>' : 'Select a nest...' }}</span>
                                <span class="wxn-nest-arrow" id="wxnNestArrow">&#9660;</span>
                            </div>
                            <div class="wxn-nest-list" id="wxnNestList" style="display:none;">
                                @foreach($nests as $nest)
                                    <div class="wxn-nest-item{{ $loop->first ? ' wxn-nest-active' : '' }}"
                                         data-id="{{ $nest->id }}"
                                         data-label="{{ $nest->name }} &lt;{{ $nest->author }}&gt;"
                                         onclick="wxnSelectNest(this)">
                                        <span class="wxn-nest-check">&#10003;</span>
                                        {{ $nest->name }} <span class="wxn-nest-author">&lt;{{ $nest->author }}&gt;</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <p class="small text-muted" style="margin-top:6px;">Select the nest that this egg will be associated with. Create the nest first if it does not exist.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    {{ csrf_field() }}
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <style>
        .wxn-nest-picker { position: relative; }
        .wxn-nest-trigger {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(4,16,4,0.95);
            border: 1px solid rgba(0,255,0,0.25);
            color: #e8ffe8;
            padding: 8px 12px;
            cursor: pointer;
            border-radius: 3px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
            transition: border-color 0.15s;
            user-select: none;
        }
        .wxn-nest-trigger:hover { border-color: rgba(0,255,0,0.55); }
        .wxn-nest-trigger.open { border-color: rgba(0,255,0,0.6); border-bottom-color: transparent; border-radius: 3px 3px 0 0; }
        .wxn-nest-arrow { font-size: 0.7rem; color: rgba(0,255,0,0.6); transition: transform 0.15s; }
        .wxn-nest-list {
            background: rgba(4,16,4,0.98);
            border: 1px solid rgba(0,255,0,0.6);
            border-top: none;
            border-radius: 0 0 3px 3px;
            max-height: 180px;
            overflow-y: auto;
        }
        .wxn-nest-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 12px;
            color: #c8f5c8;
            cursor: pointer;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.82rem;
            border-bottom: 1px solid rgba(0,255,0,0.07);
            transition: background 0.1s;
        }
        .wxn-nest-item:last-child { border-bottom: none; }
        .wxn-nest-item:hover { background: rgba(0,255,0,0.1); color: #fff; }
        .wxn-nest-item.wxn-nest-active { background: rgba(0,255,0,0.07); color: #00ff00; }
        .wxn-nest-check { color: rgba(0,255,0,0.5); font-size: 0.75rem; visibility: hidden; flex-shrink: 0; }
        .wxn-nest-item.wxn-nest-active .wxn-nest-check { visibility: visible; color: #00ff00; }
        .wxn-nest-author { color: rgba(0,255,0,0.4); font-size: 0.78rem; }
    </style>
    <script>
        function wxnToggleNestPicker(e) {
            e.stopPropagation();
            var list   = document.getElementById('wxnNestList');
            var trigger = document.getElementById('wxnNestTrigger');
            var arrow  = document.getElementById('wxnNestArrow');
            var open = list.style.display !== 'none';
            list.style.display   = open ? 'none' : 'block';
            arrow.innerHTML      = open ? '&#9660;' : '&#9650;';
            trigger.classList.toggle('open', !open);
        }

        function wxnSelectNest(el) {
            document.getElementById('pImportToNest').value = el.getAttribute('data-id');
            document.getElementById('wxnNestLabel').textContent = el.getAttribute('data-label');
            document.querySelectorAll('.wxn-nest-item').forEach(function(i) {
                i.classList.remove('wxn-nest-active');
            });
            el.classList.add('wxn-nest-active');
            document.getElementById('wxnNestList').style.display = 'none';
            document.getElementById('wxnNestArrow').innerHTML = '&#9660;';
            document.getElementById('wxnNestTrigger').classList.remove('open');
        }

        document.addEventListener('click', function(e) {
            var picker = document.querySelector('.wxn-nest-picker');
            if (picker && !picker.contains(e.target)) {
                document.getElementById('wxnNestList').style.display = 'none';
                document.getElementById('wxnNestArrow').innerHTML = '&#9660;';
                document.getElementById('wxnNestTrigger').classList.remove('open');
            }
        });

        $('#importServiceOptionModal').on('hidden.bs.modal', function () {
            document.getElementById('wxnNestList').style.display = 'none';
            document.getElementById('wxnNestArrow').innerHTML = '&#9660;';
            document.getElementById('wxnNestTrigger').classList.remove('open');
        });
    </script>
@endsection
