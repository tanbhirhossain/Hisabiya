<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice_number }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; color: #1f2937; font-size: 12px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #6366f1; padding-bottom: 14px; margin-bottom: 20px; }
        .brand { font-weight: 800; color: #6366f1; font-size: 20px; }
        h1 { font-size: 18px; margin: 4px 0; }
        .muted { color: #6b7280; }
        .grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; margin-bottom: 24px; }
        .box { border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px; }
        .box h3 { margin: 0 0 8px 0; font-size: 11px; text-transform: uppercase; color: #6b7280; }
        .total-box { background: #eef2ff; border-color: #c7d2fe; text-align: center; padding: 20px; }
        .total-box .amount { font-size: 28px; font-weight: 800; color: #4338ca; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #e5e7eb; padding: 8px 10px; text-align: left; }
        th { background: #f3f4f6; font-size: 11px; text-transform: uppercase; }
        .right { text-align: right; }
        .footer { margin-top: 28px; text-align: center; color: #6b7280; font-size: 11px; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="brand">Hisabiya</div>
            <h1>Invoice</h1>
            <div class="muted">{{ $invoice_number }}</div>
        </div>
        <div class="right muted">
            <div>{{ $date }}</div>
        </div>
    </div>

    <div class="grid">
        <div class="box">
            <h3>Billed to</h3>
            <div style="font-size:14px; font-weight:600;">{{ $tenant['name'] ?? '—' }}</div>
            <div class="muted">{{ $tenant['email'] ?? '' }}</div>
        </div>
        <div class="box">
            <h3>Payment details</h3>
            <div>Provider: <strong>{{ ucfirst(str_replace('_', ' ', $provider)) }}</strong></div>
            <div>TRX: <strong>{{ $trx_id }}</strong></div>
            <div>Plan: <strong>{{ $plan_name ?? '—' }}</strong> ({{ $module ?? '—' }})</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $plan_name ?? 'Subscription' }} — {{ ucfirst(str_replace('_', ' ', $module ?? '')) }} (monthly)</td>
                <td class="right">{{ $currency }} {{ number_format($amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="total-box">
        <div class="muted">Total due</div>
        <div class="amount">{{ $currency }} {{ number_format($amount, 2) }}</div>
    </div>

    <div class="footer">Thank you for using Hisabiya.</div>
</body>
</html>
