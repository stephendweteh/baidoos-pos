<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt</title>
</head>
<body style="margin:0; padding:24px; background:#f5f7fb; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px; margin:0 auto; background:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #e5e7eb;">
        <tr>
            <td style="padding:24px; background:#0f172a; color:#ffffff;">
                <div style="font-size:22px; font-weight:700;">Baidoos POS</div>
                <div style="font-size:14px; opacity:.85; margin-top:4px;">Payment Receipt</div>
            </td>
        </tr>
        <tr>
            <td style="padding:24px;">
                <p style="margin:0 0 16px; font-size:15px;">Hello {{ $sale->customer_name }},</p>
                <p style="margin:0 0 20px; font-size:15px;">Thank you for your payment. Here is your receipt for sale #{{ $sale->id }}.</p>

                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:20px; font-size:14px;">
                    @php
                        $paymentLabel = match ($sale->payment_method) {
                            'mobile_money' => 'Mobile Money',
                            'mtn_momo' => 'MTN MoMo',
                            default => strtoupper(str_replace('_', ' ', $sale->payment_method)),
                        };
                    @endphp
                    <tr>
                        <td style="padding:6px 0; color:#6b7280;">Branch</td>
                        <td style="padding:6px 0; text-align:right; font-weight:600;">{{ $sale->branch->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0; color:#6b7280;">Date</td>
                        <td style="padding:6px 0; text-align:right; font-weight:600;">{{ $sale->created_at->format('d M Y h:i A') }}</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0; color:#6b7280;">Payment Method</td>
                        <td style="padding:6px 0; text-align:right; font-weight:600;">{{ $paymentLabel }}</td>
                    </tr>
                    @if(in_array($sale->payment_method, ['mobile_money', 'mtn_momo'], true) && $sale->momo_ref)
                    <tr>
                        <td style="padding:6px 0; color:#6b7280;">MoMo Ref</td>
                        <td style="padding:6px 0; text-align:right; font-weight:600;">{{ $sale->momo_ref }}</td>
                    </tr>
                    @endif
                </table>

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
                            <td style="padding:10px 8px; border-bottom:1px solid #f1f5f9;">
                                {{ $line->item_name }}
                                @if(is_null($line->item_id))
                                    <span style="display:inline-block; margin-left:6px; padding:1px 6px; border-radius:999px; font-size:11px; font-weight:600; background:#e2e8f0; color:#334155;">Custom</span>
                                @endif
                                @if(preg_match('/\(.+\)\s*$/', $line->item_name))
                                    <span style="display:inline-block; margin-left:6px; padding:1px 6px; border-radius:999px; font-size:11px; font-weight:600; background:#dbeafe; color:#1d4ed8;">Variation</span>
                                @endif
                            </td>
                            <td align="center" style="padding:10px 8px; border-bottom:1px solid #f1f5f9;">{{ $line->quantity }}</td>
                            <td align="right" style="padding:10px 8px; border-bottom:1px solid #f1f5f9;">GH₵ {{ number_format($line->subtotal, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

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
                    <tr>
                        <td style="padding:8px 0 0; font-weight:700;">Total</td>
                        <td style="padding:8px 0 0; text-align:right; font-weight:700; color:#2563eb;">GH₵ {{ number_format($sale->total, 2) }}</td>
                    </tr>
                </table>

                @if($sale->notes)
                <p style="margin:0 0 20px; font-size:14px; color:#6b7280;"><strong>Notes:</strong> {{ $sale->notes }}</p>
                @endif

                <p style="margin:0; font-size:14px; color:#6b7280;">Served by {{ $sale->cashier->name }}.</p>
            </td>
        </tr>
    </table>
</body>
</html>