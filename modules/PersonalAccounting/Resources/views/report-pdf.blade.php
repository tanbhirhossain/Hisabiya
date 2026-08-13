<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Personal Finance Report</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; color: #1f2937; font-size: 11px; }
        .header { border-bottom: 2px solid #6366f1; padding-bottom: 10px; margin-bottom: 14px; }
        .brand { font-weight: 800; color: #6366f1; font-size: 16px; }
        h1 { font-size: 18px; margin: 2px 0; }
        .muted { color: #6b7280; }
        .cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 16px; }
        .card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px; }
        .card .label { text-transform: uppercase; font-size: 10px; color: #6b7280; }
        .card .val { font-size: 16px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th, td { border: 1px solid #e5e7eb; padding: 5px 7px; text-align: left; }
        th { background: #eef2ff; font-size: 10px; text-transform: uppercase; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">Hisabiya</div>
        <h1>Personal Finance Report</h1>
        <div class="muted">{{ $user_name }} · {{ $range['from'] }} → {{ $range['to'] }} · Generated {{ $generated_at }}</div>
    </div>

    <div class="cards">
        <div class="card"><div class="label">Income</div><div class="val" style="color:#10b981">৳{{ number_format($summary['income'], 2) }}</div></div>
        <div class="card"><div class="label">Expenses</div><div class="val" style="color:#f43f5e">৳{{ number_format($summary['expense'], 2) }}</div></div>
        <div class="card"><div class="label">Net</div><div class="val">৳{{ number_format($summary['net'], 2) }}</div></div>
    </div>

    <table>
        <thead>
            <tr><th>Month</th><th class="right">Income</th><th class="right">Expense</th></tr>
        </thead>
        <tbody>
            @foreach($monthlyTrend['labels'] as $i => $label)
                <tr>
                    <td>{{ $label }}</td>
                    <td class="right">৳{{ number_format($monthlyTrend['income'][$i] ?? 0, 2) }}</td>
                    <td class="right">৳{{ number_format($monthlyTrend['expense'][$i] ?? 0, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h3 style="margin-bottom:4px">Top spending categories</h3>
    <table>
        <thead><tr><th>Category</th><th class="right">Amount</th><th class="right">% of spend</th></tr></thead>
        <tbody>
            @forelse($topSpending as $cat)
                <tr>
                    <td>{{ $cat['category'] }}</td>
                    <td class="right">৳{{ number_format($cat['total'], 2) }}</td>
                    <td class="right">{{ $cat['percent'] }}%</td>
                </tr>
            @empty
                <tr><td colspan="3" class="muted">No spending in this range.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="muted" style="text-align:center; margin-top:20px;">Thank you for using Hisabiya.</p>
</body>
</html>
