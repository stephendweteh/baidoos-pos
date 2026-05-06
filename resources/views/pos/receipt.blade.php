@extends('layouts.app')
@section('title', 'Receipt #' . $sale->id)
@section('page-title', 'Receipt')

@section('content')
<div class="row justify-content-center">
<div class="col-md-6">
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">

        <div class="text-center mb-3">
            <h5 class="fw-bold mb-0">Baidoos POS</h5>
            <div class="text-muted" style="font-size:.82rem">{{ $sale->branch->name }}</div>
            <div class="text-muted" style="font-size:.78rem">{{ $sale->branch->address }}</div>
            <hr>
            <div style="font-size:.8rem">
                Receipt #{{ $sale->id }} &nbsp;|&nbsp;
                {{ $sale->sale_date->format('d M Y') }} &nbsp;|&nbsp;
                {{ $sale->created_at->format('h:i A') }}
            </div>
            @if($sale->customer_name)
            <div style="font-size:.8rem">Customer: <strong>{{ $sale->customer_name }}</strong></div>
            @endif
        </div>

        <table class="table table-sm receipt-table">
            <thead>
                <tr><th>Item</th><th class="text-center">Qty</th><th class="text-end">Amount</th></tr>
            </thead>
            <tbody>
                @foreach($sale->items as $line)
                <tr>
                    <td>{{ $line->item_name }}</td>
                    <td class="text-center">{{ $line->quantity }}</td>
                    <td class="text-end">GH₵ {{ number_format($line->subtotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr><td colspan="2" class="text-end text-muted">Subtotal</td>
                    <td class="text-end">GH₵ {{ number_format($sale->subtotal, 2) }}</td></tr>
                @if($sale->discount > 0)
                <tr><td colspan="2" class="text-end text-muted">Discount</td>
                    <td class="text-end text-danger">- GH₵ {{ number_format($sale->discount, 2) }}</td></tr>
                @endif
                <tr class="fw-bold">
                    <td colspan="2" class="text-end">TOTAL</td>
                    <td class="text-end text-primary fs-6">GH₵ {{ number_format($sale->total, 2) }}</td>
                </tr>
                <tr><td colspan="2" class="text-end text-muted">Payment</td>
                    <td class="text-end text-uppercase">{{ $sale->payment_method }}</td></tr>
            </tfoot>
        </table>

        @if($sale->notes)
        <p class="text-muted" style="font-size:.8rem"><em>{{ $sale->notes }}</em></p>
        @endif

        <div class="text-center mt-3" style="font-size:.78rem; color:#aaa">
            Served by: {{ $sale->cashier->name }}<br>
            Thank you!
        </div>

        <div class="d-flex gap-2 justify-content-center mt-4 no-print">
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-printer"></i> Print
            </button>
            <a href="{{ route('pos.sale') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-cart-plus"></i> New Sale
            </a>
        </div>
    </div>
</div>
</div>
</div>
@endsection
