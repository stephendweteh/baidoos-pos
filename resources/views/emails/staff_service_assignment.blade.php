<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Assignment</title>
</head>
<body style="margin:0; padding:24px; background:#f5f7fb; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px; margin:0 auto; background:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #e5e7eb;">
        <tr>
            <td style="padding:24px; background:#0f172a; color:#ffffff;">
                <div style="font-size:22px; font-weight:700;">Baidoos POS</div>
                <div style="font-size:14px; opacity:.85; margin-top:4px;">Service Assignment Notice</div>
            </td>
        </tr>
        <tr>
            <td style="padding:24px;">
                <p style="margin:0 0 16px; font-size:15px;">Hello {{ $staff->name }},</p>
                <p style="margin:0 0 20px; font-size:15px;">You have been assigned to the following service(s) from sale #{{ $sale->id }}.</p>

                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:20px; font-size:14px;">
                    <tr>
                        <td style="padding:6px 0; color:#6b7280;">Date &amp; Time</td>
                        <td style="padding:6px 0; text-align:right; font-weight:600;">{{ $sale->created_at->format('d M Y h:i A') }}</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0; color:#6b7280;">Branch</td>
                        <td style="padding:6px 0; text-align:right; font-weight:600;">{{ $sale->branch->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0; color:#6b7280;">Customer</td>
                        <td style="padding:6px 0; text-align:right; font-weight:600;">{{ $sale->customer_name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0; color:#6b7280;">Recorded By</td>
                        <td style="padding:6px 0; text-align:right; font-weight:600;">{{ $sale->cashier->name }}</td>
                    </tr>
                </table>

                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse; margin-bottom:20px; font-size:14px;">
                    <thead>
                        <tr>
                            <th align="left" style="padding:10px 8px; border-bottom:1px solid #e5e7eb; background:#f8fafc;">Assigned Service</th>
                            <th align="center" style="padding:10px 8px; border-bottom:1px solid #e5e7eb; background:#f8fafc;">Qty</th>
                            <th align="right" style="padding:10px 8px; border-bottom:1px solid #e5e7eb; background:#f8fafc;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $assignedTotal = 0;
                        @endphp
                        @foreach($assignedItems as $line)
                        @php
                            $assignedTotal += (float) $line->subtotal;
                        @endphp
                        <tr>
                            <td style="padding:10px 8px; border-bottom:1px solid #f1f5f9;">{{ $line->item_name }}</td>
                            <td align="center" style="padding:10px 8px; border-bottom:1px solid #f1f5f9;">{{ $line->quantity }}</td>
                            <td align="right" style="padding:10px 8px; border-bottom:1px solid #f1f5f9;">GH₵ {{ number_format($line->subtotal, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="font-size:14px; margin-bottom:20px;">
                    <tr>
                        <td style="padding:4px 0; color:#6b7280;">Your Assigned Work Total</td>
                        <td style="padding:4px 0; text-align:right; font-weight:700; color:#2563eb;">GH₵ {{ number_format($assignedTotal, 2) }}</td>
                    </tr>
                </table>

                <p style="margin:0; font-size:13px; color:#9ca3af;">This alert was sent automatically by Baidoos POS.</p>
            </td>
        </tr>
    </table>
</body>
</html>
