@extends('layouts.app')
@section('title', 'Day Closings')
@section('page-title', 'Day Closings')

@section('content')

{{-- Close Today button --}}
@if(auth()->user()->isOwner())
<div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
        <input type="month" name="month" value="{{ $month }}" class="form-control form-control-sm" style="width:160px"
               onchange="this.form.submit()">
        <select name="branch_id" class="form-select form-select-sm" style="width:200px" onchange="this.form.submit()">
            <option value="">All Branches</option>
            @foreach($branches as $b)
                <option value="{{ $b->id }}" {{ $branch == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
            @endforeach
        </select>
    </form>
    <a href="{{ route('day-closing.close') }}" class="btn btn-warning btn-sm fw-semibold">
        <i class="bi bi-lock"></i> Close A Branch Today
    </a>
</div>
@else
<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="text-muted">Your branch closing history</span>
    <a href="{{ route('day-closing.close') }}" class="btn btn-warning btn-sm fw-semibold">
        <i class="bi bi-lock"></i> Close Today
    </a>
</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Branch</th>
                    <th class="text-end">Total Sales</th>
                    <th class="text-end">Cash</th>
                    <th class="text-end">Transfer</th>
                    <th class="text-end">Card</th>
                    <th class="text-center">Txns</th>
                    <th class="text-end">Variance</th>
                    <th>Closed By</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($closings as $c)
                <tr>
                    <td class="fw-semibold">{{ $c->closing_date->format('d M Y') }}</td>
                    <td>
                        <div>{{ $c->branch->name }}</div>
                        <small class="text-muted">{{ $c->branch->businessType->name ?? '' }}</small>
                    </td>
                    <td class="text-end fw-bold text-success">GH₵ {{ number_format($c->total_sales, 2) }}</td>
                    <td class="text-end">GH₵ {{ number_format($c->total_cash_sales, 2) }}</td>
                    <td class="text-end">GH₵ {{ number_format($c->total_transfer_sales, 2) }}</td>
                    <td class="text-end">GH₵ {{ number_format($c->total_card_sales, 2) }}</td>
                    <td class="text-center">{{ $c->transaction_count }}</td>
                    <td class="text-end fw-semibold {{ $c->cash_variance < 0 ? 'text-danger' : ($c->cash_variance > 0 ? 'text-warning' : 'text-success') }}">
                        {{ $c->cash_variance >= 0 ? '+' : '' }}GH₵ {{ number_format($c->cash_variance, 2) }}
                    </td>
                    <td class="text-muted" style="font-size:.8rem">{{ $c->closedBy->name }}</td>
                    <td>
                        <a href="{{ route('day-closing.show', $c) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" class="text-center text-muted py-4">No day closings found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
