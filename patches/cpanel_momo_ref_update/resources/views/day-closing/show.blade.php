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

<div class="card border-0 shadow-sm mb-3 no-print">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('day-closing.show', $dayClosing) }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-semibold mb-1">Filter Service Staff</label>
                <select name="staff_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All staff</option>
                    @foreach($branchStaff as $staff)
                        <option value="{{ $staff->id }}" {{ $staffFilter == $staff->id ? 'selected' : '' }}>{{ $staff->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-auto">
                <a href="{{ route('day-closing.show', $dayClosing) }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
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

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted" style="font-size:.75rem">FILTERED SERVICES RENDERED</div>
                <div class="fw-bold fs-4">{{ $filteredSummary['services_rendered'] }}</div>
                <div class="small text-muted">{{ $staffFilter ? 'For selected staff' : 'Across all assigned staff' }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted" style="font-size:.75rem">FILTERED AMOUNT MADE</div>
                <div class="fw-bold fs-4 text-success">GH₵ {{ number_format($filteredSummary['amount_made'], 2) }}</div>
                <div class="small text-muted">Service revenue tied to assigned staff</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted" style="font-size:.75rem">SERVICE LINES</div>
                <div class="fw-bold fs-4">{{ $filteredSummary['service_lines'] }}</div>
                <div class="small text-muted">Count of sale lines with staff assignment</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Staff Service Summary</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead>
                <tr><th>Staff</th><th class="text-end">Service Lines</th><th class="text-end">Services Rendered</th><th class="text-end">Amount Made</th></tr>
            </thead>
            <tbody>
                @forelse($staffPerformance as $row)
                <tr>
                    <td>{{ $row->staff->name ?? 'Unassigned' }}</td>
                    <td class="text-end">{{ $row->service_lines }}</td>
                    <td class="text-end">{{ $row->services_rendered }}</td>
                    <td class="text-end fw-semibold text-success">GH₵ {{ number_format($row->amount_made, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted py-4">No staff-assigned services found for this day.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Service Assignments</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead>
                <tr><th>Time</th><th>Staff</th><th>Service</th><th class="text-end">Qty</th><th>Customer</th><th class="text-end">Amount</th></tr>
            </thead>
            <tbody>
                @forelse($serviceItems as $line)
                <tr>
                    <td>{{ $line->sale->created_at->format('h:i A') }}</td>
                    <td>{{ $line->staff->name ?? 'Unassigned' }}</td>
                    <td>{{ $line->item_name }}</td>
                    <td class="text-end">{{ $line->quantity }}</td>
                    <td>{{ $line->sale->customer_name ?? '—' }}</td>
                    <td class="text-end fw-semibold">GH₵ {{ number_format($line->subtotal, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No service assignments match the current filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Sales List --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">All Sales This Day</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead>
                <tr><th>#</th><th>Time</th><th>Customer</th><th>Items</th><th>Payment</th><th>MoMo Ref</th><th class="text-end">Total</th></tr>
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
                    <td class="text-uppercase" style="font-size:.8rem">{{ str_replace('_', ' ', $sale->payment_method) }}</td>
                    <td style="font-size:.8rem">{{ $sale->momo_ref ?? '—' }}</td>
                    <td class="text-end fw-semibold">GH₵ {{ number_format($sale->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="fw-bold">
                    <td colspan="6" class="text-end">Grand Total</td>
                    <td class="text-end text-success">GH₵ {{ number_format($dayClosing->total_sales, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
