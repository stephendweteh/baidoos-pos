@extends('layouts.app')
@section('title', 'Sales Report')
@section('page-title', 'Sales Report')

@section('content')

{{-- ── Filters ──────────────────────────────────────────────────── --}}
<form method="GET" class="card border-0 shadow-sm mb-4 no-print">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-sm-auto">
                <label class="form-label fw-semibold mb-1" style="font-size:.8rem">From</label>
                <input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm" style="width:150px">
            </div>
            <div class="col-sm-auto">
                <label class="form-label fw-semibold mb-1" style="font-size:.8rem">To</label>
                <input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm" style="width:150px">
            </div>
            <div class="col-sm-auto">
                <label class="form-label fw-semibold mb-1" style="font-size:.8rem">Branch</label>
                <select name="branch_id" class="form-select form-select-sm" style="width:180px">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-auto">
                <label class="form-label fw-semibold mb-1" style="font-size:.8rem">Cashier</label>
                <select name="cashier_id" class="form-select form-select-sm" style="width:180px">
                    <option value="">All Cashiers</option>
                    @foreach($cashiers as $c)
                        <option value="{{ $c->id }}" {{ $cashierId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-auto d-flex gap-2 flex-wrap">
                @php
                    $presets = [
                        'Today'       => [today()->toDateString(),                       today()->toDateString()],
                        'This Week'   => [today()->startOfWeek()->toDateString(),         today()->toDateString()],
                        'This Month'  => [today()->startOfMonth()->toDateString(),        today()->toDateString()],
                        'Last Month'  => [today()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                                          today()->subMonthNoOverflow()->endOfMonth()->toDateString()],
                    ];
                @endphp
                @foreach($presets as $label => [$pFrom, $pTo])
                <a href="{{ route('reports.index', ['from' => $pFrom, 'to' => $pTo, 'staff_from' => $staffFrom, 'staff_to' => $staffTo, 'branch_id' => $branchId, 'cashier_id' => $cashierId]) }}"
                   class="btn btn-sm {{ $from === $pFrom && $to === $pTo ? 'btn-primary' : 'btn-outline-secondary' }}">
                    {{ $label }}
                </a>
                @endforeach
            </div>
            <div class="col-sm-auto ms-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-funnel"></i> Apply
                </button>
                <a href="{{ route('reports.export', ['from' => $from, 'to' => $to, 'branch_id' => $branchId, 'cashier_id' => $cashierId]) }}"
                   class="btn btn-sm btn-outline-success">
                    <i class="bi bi-download"></i> Export CSV
                </a>
                <a href="{{ route('reports.export-pdf', ['from' => $from, 'to' => $to, 'branch_id' => $branchId, 'cashier_id' => $cashierId]) }}"
                   class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-file-earmark-pdf"></i> Export Sales PDF
                </a>
                <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
        </div>
    </div>
</form>

<div class="mb-3 d-none d-print-block text-center">
    <h5 class="fw-bold mb-0">Baidoos POS — Sales Report</h5>
    <div style="font-size:.85rem">{{ \Carbon\Carbon::parse($from)->format('d M Y') }} - {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="stat-card text-center"><div class="text-muted mb-1" style="font-size:.7rem;text-transform:uppercase">Total Revenue</div><div class="fw-bold text-success" style="font-size:1.35rem">GH₵ {{ number_format($totalRevenue, 2) }}</div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card text-center"><div class="text-muted mb-1" style="font-size:.7rem;text-transform:uppercase">Transactions</div><div class="fw-bold" style="font-size:1.35rem">{{ number_format($totalTxns) }}</div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card text-center"><div class="text-muted mb-1" style="font-size:.7rem;text-transform:uppercase">Avg. Sale</div><div class="fw-bold text-primary" style="font-size:1.35rem">GH₵ {{ number_format($avgTxn, 2) }}</div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card text-center"><div class="text-muted mb-1" style="font-size:.7rem;text-transform:uppercase">Total Discounts</div><div class="fw-bold text-danger" style="font-size:1.35rem">GH₵ {{ number_format($totalDiscount, 2) }}</div></div></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold py-2">
                <i class="bi bi-person-lines-fill text-primary"></i> Staff Performance & Revenue
            </div>
            <div class="card-body p-0" style="max-height:320px; overflow-y:auto">
                <form method="GET" action="{{ route('reports.index') }}" class="d-flex flex-wrap gap-2 align-items-end p-2 border-bottom no-print">
                    <input type="hidden" name="from" value="{{ $from }}">
                    <input type="hidden" name="to" value="{{ $to }}">
                    <input type="hidden" name="branch_id" value="{{ $branchId }}">
                    <input type="hidden" name="cashier_id" value="{{ $cashierId }}">

                    <div>
                        <label class="form-label fw-semibold mb-1" style="font-size:.75rem">From</label>
                        <input type="date" name="staff_from" value="{{ $staffFrom }}" class="form-control form-control-sm">
                    </div>
                    <div>
                        <label class="form-label fw-semibold mb-1" style="font-size:.75rem">To</label>
                        <input type="date" name="staff_to" value="{{ $staffTo }}" class="form-control form-control-sm">
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-funnel"></i> Apply
                    </button>
                    <a href="{{ route('reports.export-staff-performance', ['from' => $from, 'to' => $to, 'staff_from' => $staffFrom, 'staff_to' => $staffTo, 'branch_id' => $branchId, 'cashier_id' => $cashierId]) }}" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-download"></i> Export Staff CSV
                    </a>
                    <a href="{{ route('reports.export-staff-performance-pdf', ['from' => $from, 'to' => $to, 'staff_from' => $staffFrom, 'staff_to' => $staffTo, 'branch_id' => $branchId, 'cashier_id' => $cashierId]) }}" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-file-earmark-pdf"></i> Export Staff PDF
                    </a>
                </form>

                @if($staffPerformance->isEmpty())
                    <div class="text-center text-muted py-4" style="font-size:.85rem">No data</div>
                @else
                <table class="table table-sm mb-0">
                    <thead><tr><th>Date</th><th>Branch</th><th>Staff</th><th class="text-end">Services</th><th class="text-end">Revenue</th></tr></thead>
                    <tbody>
                        @foreach($staffPerformance as $row)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($row->performance_date)->format('d M Y') }}</td>
                            <td>{{ $row->branch_name }}</td>
                            <td class="fw-semibold">{{ $row->staff_name }}</td>
                            <td class="text-end">{{ number_format($row->services_rendered) }}</td>
                            <td class="text-end fw-semibold text-success">GH₵ {{ number_format($row->amount_made, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold py-2">
                <i class="bi bi-building text-primary"></i> By Branch
            </div>
            <div class="card-body p-0">
                @if($byBranch->isEmpty())
                    <div class="text-center text-muted py-4" style="font-size:.85rem">No sales in this period</div>
                @else
                <table class="table table-sm mb-0">
                    <thead><tr><th>Branch</th><th class="text-end">Revenue</th><th class="text-end">Discount</th><th class="text-center">Txns</th></tr></thead>
                    <tbody>
                        @foreach($byBranch->sortByDesc('revenue') as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row->branch->name }}</td>
                            <td class="text-end text-success fw-semibold">GH₵ {{ number_format($row->revenue, 2) }}</td>
                            <td class="text-end text-danger">GH₵ {{ number_format($row->discount, 2) }}</td>
                            <td class="text-center">{{ number_format($row->txns) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold py-2">
                <i class="bi bi-stars text-warning"></i> Top 10 Items / Services
            </div>
            <div class="card-body p-0">
                @if($topItems->isEmpty())
                    <div class="text-center text-muted py-4" style="font-size:.85rem">No data</div>
                @else
                <table class="table table-sm mb-0">
                    <thead><tr><th>#</th><th>Item</th><th class="text-center">Qty</th><th class="text-end">Revenue</th></tr></thead>
                    <tbody>
                        @foreach($topItems as $i => $row)
                        <tr>
                            <td class="text-muted">{{ $i + 1 }}</td>
                            <td>{{ $row->item_name }}</td>
                            <td class="text-center">{{ number_format($row->total_qty) }}</td>
                            <td class="text-end fw-semibold">GH₵ {{ number_format($row->total_amount, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold py-2">
                <i class="bi bi-credit-card text-primary"></i> By Payment Method
            </div>
            <div class="card-body p-0" style="max-height:320px; overflow-y:auto">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Method</th><th class="text-end">Revenue</th><th class="text-center">Txns</th></tr></thead>
                    <tbody>
                        @foreach(['cash' => 'Cash', 'mtn_momo' => 'MTN MoMo'] as $key => $label)
                        @php $row = $byPayment[$key] ?? null @endphp
                        <tr>
                            <td class="fw-semibold text-capitalize">{{ $label }}</td>
                            <td class="text-end text-success fw-semibold">GH₵ {{ number_format($row?->revenue ?? 0, 2) }}</td>
                            <td class="text-center">{{ number_format($row?->txns ?? 0) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex align-items-center justify-content-between py-2">
        <span class="fw-semibold"><i class="bi bi-list-ul text-primary"></i> Transactions (latest 200)</span>
        <small class="text-muted">{{ number_format($sales->count()) }} records</small>
    </div>
    <div class="card-body p-0" style="max-height:420px; overflow-y:auto">
        @if($sales->isEmpty())
            <div class="text-center text-muted py-4" style="font-size:.85rem">No transactions in this period</div>
        @else
        <table class="table table-sm table-hover mb-0">
            <thead class="sticky-top bg-white">
                <tr>
                    <th>#</th><th>Date</th><th>Branch</th><th>Customer</th>
                    <th class="text-end">Subtotal</th><th class="text-end">Discount</th>
                    <th class="text-end">Total</th><th>Method</th><th>Cashier</th><th class="no-print"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($sales as $s)
                <tr>
                    <td class="text-muted">{{ $s->id }}</td>
                    <td style="font-size:.82rem">{{ $s->sale_date->format('d M Y') }}</td>
                    <td style="font-size:.82rem">{{ $s->branch->name }}</td>
                    <td style="font-size:.82rem">{{ $s->customer_name ?? '-' }}</td>
                    <td class="text-end">GH₵ {{ number_format($s->subtotal, 2) }}</td>
                    <td class="text-end text-danger">@if($s->discount > 0)GH₵ {{ number_format($s->discount, 2) }}@else -@endif</td>
                    <td class="text-end fw-semibold text-success">GH₵ {{ number_format($s->total, 2) }}</td>
                    <td class="text-uppercase" style="font-size:.78rem">{{ $s->payment_method }}</td>
                    <td style="font-size:.78rem">{{ $s->cashier->name }}</td>
                    <td class="no-print"><a href="{{ route('pos.receipt', $s) }}" class="btn btn-sm btn-link p-0" title="View receipt"><i class="bi bi-receipt"></i></a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

@endsection
