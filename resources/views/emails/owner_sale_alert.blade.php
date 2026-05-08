<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Sale Alert</title>
</head>
<body style="margin:0; padding:24px; background:#f5f7fb; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px; margin:0 auto; background:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #e5e7eb;">
        <tr>
            <td style="padding:24px; background:#1e3a5f; color:#ffffff;">
                <div style="font-size:22px; font-weight:700;">Baidoos POS</div>
                <div style="font-size:14px; opacity:.85; margin-top:4px;">Transaction Alert – Sale #{{ $sale->id }}</div>
            </td>
        </tr>
        <tr>
            <td style="padding:24px;">
                <p style="margin:0 0 16px; font-size:15px;">A new sale has been recorded on your system.</p>

                {{-- Sale summary --}}
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:20px; font-size:14px;">
                    <tr>
                        <td style="padding:6px 0; color:#6b7280;">Sale #</td>
                        <td style="padding:6px 0; text-align:right; font-weight:600;">{{ $sale->id }}</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0; color:#6b7280;">Date &amp; Time</td>
                        <td style="padding:6px 0; text-align:right; font-weight:600;">{{ $sale->created_at->format('d M Y h:i A') }}</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0; color:#6b7280;">Branch</td>
                        <td style="padding:6px 0; text-align:right; font-weight:600;">{{ $sale->branch->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0; color:#6b7280;">Cashier</td>
                        <td style="padding:6px 0; text-align:right; font-weight:600;">{{ $sale->cashier->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0; color:#6b7280;">Customer</td>
                        <td style="padding:6px 0; text-align:right; font-weight:600;">{{ $sale->customer_name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0; color:#6b7280;">Payment Method</td>
                        <td style="padding:6px 0; text-align:right; font-weight:600; text-transform:uppercase;">{{ str_replace('_', ' ', $sale->payment_method) }}</td>
                    </tr>
                </table>

                {{-- Items --}}
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse; margin-bottom:20px; font-size:14px;">
                    <thead>
                        <tr>
                            <th align="left" style="padding:10px 8px; border-bottom:1px solid #e5e7eb; background:#f8fafc;">Item</th>
                            <th align="center" style="padding:10px 8px; border-bottom:1px solid #e5e7eb; background:#f8fafc;">Qty</th>
                            <th align="right" style="padding:10px 8px; border-bottom:1px solid #e5e7eb; background:#f8fafc;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sale->items as $line)
                        <tr>
                            <td style="padding:10px 8px; border-bottom:1px solid #f1f5f9;">{{ $line->item_name }}</td>
                            <td align="center" style="padding:10px 8px; border-bottom:1px solid #f1f5f9;">{{ $line->quantity }}</td>
                            <td align="right" style="padding:10px 8px; border-bottom:1px solid #f1f5f9;">GH₵ {{ number_format($line->subtotal, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Totals --}}
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="font-size:14px; margin-bottom:20px;">
                    <tr>
                        <td style="padding:4px 0; color:#6b7280;">Subtotal</td>
                        <td style="padding:4px 0; text-align:right;">GH₵ {{ number_format($sale->subtotal, 2) }}</td>
                    </tr>
                    @if($sale->discount > 0)
                    <tr>
                        <td style="padding:4px 0; color:#6b7280;">Discount</td>
                        <td style="padding:4px 0; text-align:right; color:#b91c1c;">- GH₵ {{ number_format($sale->discount, 2) }}</td>
                    </tr>
                    @endif
                    <tr style="border-top:2px solid #e5e7eb;">
                        <td style="padding:10px 0 0; font-size:16px; font-weight:700;">Total</td>
                        <td style="padding:10px 0 0; text-align:right; font-size:16px; font-weight:700; color:#16a34a;">GH₵ {{ number_format($sale->total, 2) }}</td>
                    </tr>
                </table>

                @if($sale->notes)
                <p style="margin:0 0 20px; font-size:14px; color:#6b7280;"><strong>Notes:</strong> {{ $sale->notes }}</p>
                @endif

                <p style="margin:0; font-size:13px; color:#9ca3af;">This alert was sent automatically by Baidoos POS. Do not reply to this email.</p>
            </td>
        </tr>
    </table>
</body>
</html>
