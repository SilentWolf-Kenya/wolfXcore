@extends('layouts.admin')

@section('title')
    Paystack Transactions
@endsection

@section('content-header')
    <h1>Paystack Transactions<small>Live records pulled directly from your Paystack account</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.super.index') }}">Super Panel</a></li>
        <li class="active">Paystack Transactions</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">

        {{-- Filter bar --}}
        <div class="box box-default" style="margin-bottom:14px;">
            <div class="box-body" style="padding:12px 16px;">
                <form method="GET" action="{{ route('admin.super.paystack-transactions') }}" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                    <label style="margin:0;font-weight:600;">Status</label>
                    <select name="status" class="form-control" style="width:160px;">
                        <option value=""      {{ $status === ''          ? 'selected' : '' }}>All</option>
                        <option value="success"  {{ $status === 'success'  ? 'selected' : '' }}>Success</option>
                        <option value="failed"   {{ $status === 'failed'   ? 'selected' : '' }}>Failed</option>
                        <option value="abandoned"{{ $status === 'abandoned'? 'selected' : '' }}>Abandoned</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    <a href="{{ route('admin.super.paystack-transactions') }}" class="btn btn-sm btn-default">Reset</a>
                    @if(!empty($meta))
                        <span class="text-muted" style="margin-left:auto;font-size:0.85rem;">
                            Page {{ $meta['page'] ?? $page }} of {{ $meta['pageCount'] ?? '?' }}
                            &nbsp;&mdash;&nbsp;
                            {{ number_format($meta['total'] ?? 0) }} total transactions
                        </span>
                    @endif
                </form>
            </div>
        </div>

        @if($error)
            <div class="alert alert-danger">
                <i class="fa fa-exclamation-triangle"></i> {{ $error }}
            </div>
        @else
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Transactions (page {{ $page }})</h3>
                <div class="box-tools">
                    <span class="wxn-hint">
                        Synced badge = recorded in local DB &nbsp;|&nbsp; Missing = Paystack recorded it but local DB missed it
                    </span>
                </div>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover table-condensed">
                    <thead>
                        <tr>
                            <th style="width:160px;">Reference</th>
                            <th>Customer</th>
                            <th>Channel</th>
                            <th style="width:110px;">Amount (KES)</th>
                            <th style="width:80px;">Status</th>
                            <th style="width:80px;">Local DB</th>
                            <th style="width:150px;">Paid At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $txn)
                            @php
                                $ref       = $txn['reference']    ?? '—';
                                $email     = $txn['customer']['email'] ?? '—';
                                $channel   = $txn['channel']      ?? '—';
                                $amountKes = number_format(($txn['amount'] ?? 0) / 100, 2);
                                $paidAt    = $txn['paid_at'] ? \Carbon\Carbon::parse($txn['paid_at'])->format('Y-m-d H:i') : '—';
                                $txnStatus = $txn['status'] ?? 'unknown';
                                $localStatus = $localRefs[$ref] ?? null;
                            @endphp
                            <tr>
                                <td>
                                    <code style="font-size:0.78rem;">{{ $ref }}</code>
                                </td>
                                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $email }}">
                                    {{ $email }}
                                </td>
                                <td>
                                    @if($channel === 'mobile_money')
                                        <span class="label label-info">M-Pesa</span>
                                    @elseif($channel === 'card')
                                        <span class="label label-default">Card</span>
                                    @else
                                        <span class="label label-default">{{ ucfirst($channel) }}</span>
                                    @endif
                                </td>
                                <td class="text-right" style="font-family:monospace;">
                                    {{ $amountKes }}
                                </td>
                                <td>
                                    @if($txnStatus === 'success')
                                        <span class="label label-success">Success</span>
                                    @elseif($txnStatus === 'failed')
                                        <span class="label label-danger">Failed</span>
                                    @elseif($txnStatus === 'abandoned')
                                        <span class="label label-warning">Abandoned</span>
                                    @else
                                        <span class="label label-default">{{ ucfirst($txnStatus) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($localStatus !== null)
                                        <span class="label label-success" title="Found in wxn_payments ({{ $localStatus }})">Synced</span>
                                    @else
                                        <span class="label label-danger" title="Not in wxn_payments">Missing</span>
                                    @endif
                                </td>
                                <td style="font-size:0.82rem;">{{ $paidAt }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted" style="padding:30px;">
                                    No transactions found for the selected filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if(($meta['pageCount'] ?? 1) > 1)
            <div class="box-footer">
                <div style="display:flex;gap:8px;align-items:center;justify-content:center;flex-wrap:wrap;">
                    @if($page > 1)
                        <a href="{{ route('admin.super.paystack-transactions', ['page' => $page - 1, 'status' => $status]) }}"
                           class="btn btn-sm btn-default"><i class="fa fa-chevron-left"></i> Prev</a>
                    @endif
                    <span class="text-muted">Page {{ $page }} / {{ $meta['pageCount'] ?? '?' }}</span>
                    @if($page < ($meta['pageCount'] ?? 1))
                        <a href="{{ route('admin.super.paystack-transactions', ['page' => $page + 1, 'status' => $status]) }}"
                           class="btn btn-sm btn-default">Next <i class="fa fa-chevron-right"></i></a>
                    @endif
                </div>
            </div>
            @endif
        </div>
        @endif

    </div>
</div>
@endsection
