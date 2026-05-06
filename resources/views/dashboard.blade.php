@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

{{-- Date & Branch Filters --}}
<form method="GET" class="d-flex gap-2 flex-wrap align-items-center mb-3 no-print">
    <input type="date" name="date" value="{{ $date }}" class="form-control form-control-sm" style="width:155px" onchange="this.form.submit()">
    @if(auth()->user()->isOwner())
    <select name="branch_id" class="form-select form-select-sm" style="width:200px" onchange="this.form.submit()">
        <option value="">All Branches</option>
        @foreach($branches as $b)
            <option value="{{ $b->id }}" {{ $branch == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
        @endforeach
    </select>
    @endif
    @if($date !== today()->toDateString())
    <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary">Today</a>
    @endif
</form>

{{-- KPI Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card text-center">
            <div class="text-muted mb-1" style="font-size:.72rem; text-transform:uppercase">Total Sales</div>
            <div class="fw-bold text-success" style="font-size:1.4rem">GH₵ {{ number_format($totalSales, 2) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card text-center">
            <div class="text-muted mb-1" style="font-size:.72rem; text-transform:uppercase">Transactions</div>
            <div class="fw-bold" style="font-size:1.4rem">{{ $transactionCount }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card text-center">
            <div class="text-muted mb-1" style="font-size:.72rem; text-transform:uppercase">Cash Sales</div>
            <div class="fw-bold" style="font-size:1.4rem">GH₵ {{ number_format($cashSales, 2) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card text-center">
            <div class="text-muted mb-1" style="font-size:.72rem; text-transform:uppercase">Transfer + Card</div>
            <div class="fw-bold" style="font-size:1.4rem">GH₵ {{ number_format($transferSales + $cardSales, 2) }}</div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Sales by Branch (owner only) --}}
    @if(auth()->user()->isOwner() && $salesByBranch->isNotEmpty())
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-building text-primary"></i> By Branch</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Branch</th><th class="text-end">Sales</th><th class="text-end">Txns</th></tr></thead>
                    <tbody>
                        @foreach($salesByBranch as $row)
                        <tr>
                            <td>
                                <div style="font-size:.85rem" class="fw-semibold">{{ $row->branch->name }}</div>
                            </td>
                            <td class="text-end fw-semibold text-success">GH₵ {{ number_format($row->total_sales, 2) }}</td>
                            <td class="text-end">{{ $row->txn_count }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Top Items --}}
    <div class="{{ auth()->user()->isOwner() && $salesByBranch->isNotEmpty() ? 'col-lg-4' : 'col-lg-6' }}">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-stars text-warning"></i> Top Items</div>
            <div class="card-body p-0">
                @if($topItems->isEmpty())
                    <div class="text-center text-muted py-4" style="font-size:.85rem">No sales recorded</div>
                @else
                <table class="table table-sm mb-0">
                    <thead><tr><th>Item</th><th class="text-center">Qty</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                        @foreach($topItems as $row)
                        <tr>
                            <td style="font-size:.85rem">{{ $row->item_name }}</td>
                            <td class="text-center">{{ $row->total_qty }}</td>
                            <td class="text-end fw-semibold">GH₵ {{ number_format($row->total_amount, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>
    </div>

    {{-- Recent Sales --}}
    <div class="{{ auth()->user()->isOwner() && $salesByBranch->isNotEmpty() ? 'col-lg-3' : 'col-lg-6' }}">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between">
                <span class="fw-semibold"><i class="bi bi-clock-history text-primary"></i> Recent Sales</span>
            </div>
            <div class="card-body p-0" style="max-height:320px; overflow-y:auto">
                @forelse($recentSales as $s)
                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                    <div>
                        <div style="font-size:.82rem" class="fw-semibold">
                            @if(auth()->user()->isOwner())
                                {{ $s->branch->name }}
                            @endif
                            {{ $s->customer_name ? '— '.$s->customer_name : '' }}
                        </div>
                        <small class="text-muted text-uppercase">{{ $s->payment_method }} · {{ $s->created_at->format('h:i A') }}</small>
                    </div>
                    <div>
                        <span class="fw-semibold text-success" style="font-size:.85rem">GH₵ {{ number_format($s->total, 2) }}</span>
                        <a href="{{ route('pos.receipt', $s) }}" class="btn btn-sm btn-link p-0 ms-1" title="View receipt">
                            <i class="bi bi-receipt"></i>
                        </a>
                    </div>
                </div>
                @empty
                <div class="text-center text-muted py-4" style="font-size:.85rem">No sales yet</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Quick Actions --}}
<div class="row g-3 mt-2">
    <div class="col-12">
        <div class="d-flex gap-2 flex-wrap">
            @if(auth()->user()->isCashier())
            <a href="{{ route('pos.sale') }}" class="btn btn-success">
                <i class="bi bi-cart-plus"></i> New Sale
            </a>
            <a href="{{ route('day-closing.close') }}" class="btn btn-warning">
                <i class="bi bi-lock"></i> Close Today
            </a>
            @else
            <a href="{{ route('pos.sale') }}" class="btn btn-success">
                <i class="bi bi-cart-plus"></i> New Sale
            </a>
            <a href="{{ route('day-closing.close') }}" class="btn btn-warning">
                <i class="bi bi-lock"></i> Close A Branch Today
            </a>
            <a href="{{ route('day-closing.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-calendar-check"></i> Closing History
            </a>
            @endif
        </div>
    </div>
</div>

@endsection
