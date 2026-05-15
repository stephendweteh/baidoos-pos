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
            @if($sale->customer_phone)
            <div style="font-size:.78rem; color:#555">Phone: {{ $sale->customer_phone }}</div>
            @endif
            @if($sale->customer_email)
            <div style="font-size:.78rem; color:#555">Email: {{ $sale->customer_email }}</div>
            @endif
        </div>

        <table class="table table-sm receipt-table">
            <thead>
                <tr><th>Item</th><th class="text-center">Qty</th><th class="text-end">Amount</th></tr>
            </thead>
            <tbody>
                @foreach($sale->items as $line)
                <tr>
                    <td>
                        {{ $line->item_name }}
                        @if(is_null($line->item_id))
                            <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1">Custom</span>
                        @endif
                        @if(preg_match('/\(.+\)\s*$/', $line->item_name))
                            <span class="badge bg-info-subtle text-info-emphasis ms-1">Variation</span>
                        @endif
                    </td>
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
                @if($sale->payment_method === 'mtn_momo')
                <tr>
                    <td colspan="2" class="text-end text-muted">MoMo Status</td>
                    <td class="text-end">
                        <span class="badge {{ $sale->payment_status === 'success' ? 'bg-success' : ($sale->payment_status === 'failed' ? 'bg-danger' : 'bg-warning text-dark') }}" id="momoStatusBadge">
                            {{ strtoupper($sale->payment_status) }}
                        </span>
                    </td>
                </tr>
                @endif
            </tfoot>
        </table>

        @if($sale->notes)
        <p class="text-muted" style="font-size:.8rem"><em>{{ $sale->notes }}</em></p>
        @endif

        <div class="text-center mt-3" style="font-size:.78rem; color:#aaa">
            Served by: {{ $sale->cashier->name }}<br>
            Thank you!
        </div>

        @if($sale->payment_method === 'mtn_momo')
        <div id="momoStatusAlert" class="alert {{ $sale->payment_status === 'success' ? 'alert-success' : ($sale->payment_status === 'failed' ? 'alert-danger' : 'alert-warning') }} py-2 mt-3 text-center no-print" style="font-size:.82rem">
            @if($sale->payment_status === 'success')
                <i class="bi bi-check-circle-fill"></i> MTN payment confirmed.
            @elseif($sale->payment_status === 'failed')
                <i class="bi bi-x-circle-fill"></i> MTN payment failed. Ask customer to retry.
            @else
                <i class="bi bi-hourglass-split"></i> Waiting for customer to approve MTN prompt and enter PIN.
            @endif
        </div>
        <div class="text-center no-print mb-2">
            <button type="button" id="checkMomoBtn" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-arrow-repeat"></i> Check MTN Status
            </button>
        </div>
        @endif

        @if($sale->customer_phone && $sale->payment_status === 'success')
        <div class="alert alert-success py-1 mt-3 text-center no-print" style="font-size:.8rem">
            <i class="bi bi-chat-dots-fill"></i> SMS receipt sent to {{ $sale->customer_phone }}
        </div>
        @endif

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

@if($sale->payment_method === 'mtn_momo' && $sale->payment_status === 'pending')
@push('scripts')
<script>
(function () {
    const btn = document.getElementById('checkMomoBtn');
    const alertBox = document.getElementById('momoStatusAlert');
    const badge = document.getElementById('momoStatusBadge');
    let polling = null;
    let failedChecks = 0;

    async function checkStatus() {
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Checking...';

        try {
            const response = await fetch('{{ route('pos.receipt.momo-status', $sale) }}', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();

            if (!data.ok) {
                throw new Error(data.message || 'Status check failed.');
            }

            failedChecks = 0;

            badge.textContent = String(data.payment_status || '').toUpperCase();

            if (data.payment_status === 'success') {
                badge.className = 'badge bg-success';
                alertBox.className = 'alert alert-success py-2 mt-3 text-center no-print';
                alertBox.innerHTML = '<i class="bi bi-check-circle-fill"></i> ' + data.message;
                clearInterval(polling);
                setTimeout(() => window.location.reload(), 1200);
                return;
            }

            if (data.payment_status === 'failed') {
                badge.className = 'badge bg-danger';
                alertBox.className = 'alert alert-danger py-2 mt-3 text-center no-print';
                alertBox.innerHTML = '<i class="bi bi-x-circle-fill"></i> ' + data.message;
                clearInterval(polling);
                return;
            }

            badge.className = 'badge bg-warning text-dark';
            alertBox.className = 'alert alert-warning py-2 mt-3 text-center no-print';
            alertBox.innerHTML = '<i class="bi bi-hourglass-split"></i> ' + data.message;
        } catch (error) {
            failedChecks += 1;
            alertBox.className = 'alert alert-danger py-2 mt-3 text-center no-print';
            alertBox.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i> ' + (error.message || 'Could not check payment status.');

            if (failedChecks >= 3 && polling) {
                clearInterval(polling);
                alertBox.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i> Unable to verify status right now. Please click "Check MTN Status" after a moment.';
            }
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Check MTN Status';
        }
    }

    btn.addEventListener('click', checkStatus);
    polling = setInterval(checkStatus, 7000);
})();
</script>
@endpush
@endif
