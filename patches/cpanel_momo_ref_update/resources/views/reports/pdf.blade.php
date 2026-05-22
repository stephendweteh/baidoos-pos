<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Report PDF</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h2 { margin: 0 0 4px 0; }
        .muted { color: #666; margin-bottom: 10px; }
        .summary { margin-bottom: 12px; }
        .summary span { display: inline-block; margin-right: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d9d9d9; padding: 6px; vertical-align: top; }
        th { background: #f3f3f3; text-align: left; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h2>Baidoos POS - Sales Report</h2>
    <div class="muted">Period: {{ $from }} to {{ $to }}</div>

    <div class="summary">
        <span><strong>Total Revenue:</strong> GH₵ {{ number_format($summary['totalRevenue'], 2) }}</span>
        <span><strong>Total Discount:</strong> GH₵ {{ number_format($summary['totalDiscount'], 2) }}</span>
        <span><strong>Transactions:</strong> {{ number_format($summary['totalTxns']) }}</span>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Branch</th>
                <th>Customer</th>
                <th>Items</th>
                <th class="right">Subtotal</th>
                <th class="right">Discount</th>
                <th class="right">Total</th>
                <th>Payment</th>
                <th>MoMo Ref</th>
                <th>Cashier</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $sale)
                <tr>
                    <td>{{ $sale->id }}</td>
                    <td>{{ $sale->sale_date->toDateString() }}</td>
                    <td>{{ $sale->branch->name ?? '' }}</td>
                    <td>{{ $sale->customer_name ?? '-' }}</td>
                    <td>{{ $sale->items->map(fn ($i) => $i->item_name . ' x' . $i->quantity)->implode(', ') }}</td>
                    <td class="right">{{ number_format($sale->subtotal, 2) }}</td>
                    <td class="right">{{ number_format($sale->discount, 2) }}</td>
                    <td class="right">{{ number_format($sale->total, 2) }}</td>
                    <td>{{ strtoupper(str_replace('_', ' ', $sale->payment_method)) }}</td>
                    <td>{{ $sale->momo_ref ?? '-' }}</td>
                    <td>{{ $sale->cashier->name ?? '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11">No sales in this period.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>