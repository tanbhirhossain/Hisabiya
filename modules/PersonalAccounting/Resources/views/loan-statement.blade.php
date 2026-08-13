<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Loan Statement</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; color: #1f2937; font-size: 12px; }
        h1 { font-size: 20px; margin-bottom: 2px; }
        .muted { color: #6b7280; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #10b981; padding-bottom: 12px; margin-bottom: 16px; }
        .brand { font-weight: 800; color: #10b981; font-size: 18px; }
        .grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px; margin-bottom: 16px; }
        .card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; margin-bottom: 16px; }
        .card h2 { font-size: 13px; text-transform: uppercase; color: #6b7280; margin: 0 0 8px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; font-size: 11px; text-transform: uppercase; }
        .right { text-align: right; }
        .total { font-weight: 700; }
        .status { display: inline-block; padding: 2px 8px; border-radius: 9999px; background: #d1fae5; color: #065f46; font-size: 11px; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="brand">Hisabiya</div>
            <h1>Loan Statement</h1>
            <div class="muted">Generated {{ $generated_at }}</div>
        </div>
        <div class="right">
            <span class="status">{{ ucfirst($loan['direction']) }} · {{ ucfirst($loan['status']) }}</span>
        </div>
    </div>

    <div class="card">
        <h2>Loan details</h2>
        <div class="grid">
            <div>Name: <strong>{{ $loan['name'] }}</strong></div>
            <div>Contact: <strong>{{ $loan['contact'] ?? '—' }}</strong></div>
            <div>Principal: <strong>৳{{ number_format($loan['principal_amount'], 2) }}</strong></div>
            <div>Interest rate: <strong>{{ $loan['interest_rate'] }}%</strong></div>
            <div>Penalty rate: <strong>{{ $loan['penalty_rate'] }}%</strong></div>
            <div>Payment frequency: <strong>{{ $loan['payment_frequency'] }}</strong></div>
            <div>Start date: <strong>{{ $loan['start_date'] }}</strong></div>
            <div>Due date: <strong>{{ $loan['due_date'] ?? '—' }}</strong></div>
        </div>
    </div>

    <div class="grid">
        <div>Remaining balance: <strong>৳{{ number_format($loan['remaining_balance'], 2) }}</strong></div>
        <div>Total paid: <strong>৳{{ number_format($loan['total_paid'], 2) }}</strong></div>
    </div>

    <div class="card">
        <h2>Payment history</h2>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th class="right">Amount</th>
                    <th class="right">Principal</th>
                    <th class="right">Interest</th>
                    <th class="right">Penalty</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td>{{ $payment['date'] }}</td>
                        <td class="right">৳{{ number_format($payment['amount'], 2) }}</td>
                        <td class="right">৳{{ number_format($payment['principal_part'], 2) }}</td>
                        <td class="right">৳{{ number_format($payment['interest_part'], 2) }}</td>
                        <td class="right">৳{{ number_format($payment['penalty_amount'], 2) }}</td>
                        <td>{{ $payment['note'] ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No payments recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="muted" style="text-align:center; margin-top:24px;">Thank you for using Hisabiya.</p>
</body>
</html>
