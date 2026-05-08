@extends('layouts.app')
@section('title', 'Day Report — ' . $dayClosing->closing_date->format('d M Y'))
@section('page-title', 'Day Report — ' . $dayClosing->closing_date->format('d M Y'))

@section('content')
<div class="d-flex justify-content-between mb-3 no-print">
    <a href="{{ route('day-closing.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-printer"></i> Print Report
    </button>
</div>

{{-- Header --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h5 class="fw-bold mb-1">{{ $dayClosing->branch->name }}</h5>
        <div class="text-muted mb-3">{{ $dayClosing->branch->businessType->name ?? '' }} · Closed by {{ $dayClosing->closedBy->name }} at {{ $dayClosing->created_at->format('h:i A') }}</div>

        <div class="row g-3 text-center">
            <div class="col-6 col-md-2">
                <div class="stat-card">
                    <div class="text-muted" style="font-size:.68rem">TOTAL SALES</div>
                    <div class="fw-bold text-success">GH₵ {{ number_format($dayClosing->total_sales, 2) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="stat-card">
                    <div class="text-muted" style="font-size:.68rem">TRANSACTIONS</div>
                    <div class="fw-bold">{{ $dayClosing->transaction_count }}</div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="stat-card">
                    <div class="text-muted" style="font-size:.68rem">CASH</div>
                    <div class="fw-bold">GH₵ {{ number_format($dayClosing->total_cash_sales, 2) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="stat-card">
                    <div class="text-muted" style="font-size:.68rem">MTN MOMO</div>
                    <div class="fw-bold">GH₵ {{ number_format($dayClosing->total_momo_sales, 2) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="stat-card">
                    <div class="text-muted" style="font-size:.68rem">VARIANCE</div>
                    <div class="fw-bold {{ $dayClosing->cash_variance < 0 ? 'text-danger' : ($dayClosing->cash_variance > 0 ? 'text-warning' : 'text-success') }}">
                        {{ $dayClosing->cash_variance >= 0 ? '+' : '' }}GH₵ {{ number_format($dayClosing->cash_variance, 2) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-4">
                <small class="text-muted d-block">Opening Cash</small>
                <span>GH₵ {{ number_format($dayClosing->opening_cash, 2) }}</span>
            </div>
            <div class="col-md-4">
                <small class="text-muted d-block">Cash Counted</small>
                <span>GH₵ {{ number_format($dayClosing->cash_counted, 2) }}</span>
            </div>
            <div class="col-md-4">
                <small class="text-muted d-block">Notes</small>
                <span>{{ $dayClosing->notes ?? '—' }}</span>
            </div>
        </div>
    </div>
</div>

{{-- Sales List --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">All Sales This Day</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead>
                <tr><th>#</th><th>Time</th><th>Customer</th><th>Items</th><th>Payment</th><th class="text-end">Total</th></tr>
            </thead>
            <tbody>
                @foreach($dayClosing->sales as $sale)
                <tr>
                    <td>{{ $sale->id }}</td>
                    <td>{{ $sale->created_at->format('h:i A') }}</td>
                    <td>{{ $sale->customer_name ?? '—' }}</td>
                    <td>
                        @foreach($sale->items as $li)
                            <small>{{ $li->item_name }} ×{{ $li->quantity }}</small>{{ !$loop->last ? ', ' : '' }}
                        @endforeach
                    </td>
                    <td class="text-uppercase" style="font-size:.8rem">{{ $sale->payment_method }}</td>
                    <td class="text-end fw-semibold">GH₵ {{ number_format($sale->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="fw-bold">
                    <td colspan="5" class="text-end">Grand Total</td>
                    <td class="text-end text-success">GH₵ {{ number_format($dayClosing->total_sales, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
