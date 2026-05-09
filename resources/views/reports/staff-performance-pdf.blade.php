<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Staff Performance Report</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 12px;
        }
        .header {
            margin-bottom: 14px;
        }
        .title {
            font-size: 20px;
            font-weight: 700;
            margin: 0;
        }
        .subtitle {
            margin-top: 4px;
            color: #4b5563;
            font-size: 12px;
        }
        .summary {
            width: 100%;
            margin: 10px 0 14px;
            border-collapse: collapse;
        }
        .summary td {
            border: 1px solid #d1d5db;
            padding: 8px;
        }
        .summary .label {
            background: #f3f4f6;
            color: #374151;
            width: 25%;
            font-weight: 600;
        }
        table.report {
            width: 100%;
            border-collapse: collapse;
        }
        table.report th,
        table.report td {
            border: 1px solid #d1d5db;
            padding: 7px;
            vertical-align: top;
        }
        table.report th {
            background: #f9fafb;
            text-align: left;
        }
        .right {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="title">Staff Performance &amp; Revenue</h1>
        <div class="subtitle">
            Period: {{ \Carbon\Carbon::parse($staffFrom)->format('d M Y') }} to {{ \Carbon\Carbon::parse($staffTo)->format('d M Y') }}
        </div>
    </div>

    <table class="summary">
        <tr>
            <td class="label">Total Rows</td>
            <td>{{ number_format($summary['totalRows']) }}</td>
            <td class="label">Total Services Rendered</td>
            <td>{{ number_format($summary['totalServices']) }}</td>
        </tr>
        <tr>
            <td class="label">Total Revenue</td>
            <td colspan="3">GH₵ {{ number_format($summary['totalRevenue'], 2) }}</td>
        </tr>
    </table>

    <table class="report">
        <thead>
            <tr>
                <th style="width: 16%">Date</th>
                <th style="width: 28%">Branch</th>
                <th style="width: 28%">Staff</th>
                <th style="width: 14%" class="right">Services</th>
                <th style="width: 14%" class="right">Revenue</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($row->performance_date)->format('d M Y') }}</td>
                    <td>{{ $row->branch_name }}</td>
                    <td>{{ $row->staff_name }}</td>
                    <td class="right">{{ number_format($row->services_rendered) }}</td>
                    <td class="right">GH₵ {{ number_format($row->amount_made, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center; color:#6b7280;">No staff performance data for this period.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
