<?php

namespace Modules\PersonalAccounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Modules\PersonalAccounting\Interfaces\PersonalTransactionRepositoryInterface;
use Modules\PersonalAccounting\Models\PersonalTransaction;

class ReportController extends Controller
{
    public function __construct(private readonly PersonalTransactionRepositoryInterface $transactions)
    {
    }

    public function index(Request $request): Response
    {
        $user = $request->user();

        $from = $request->filled('from') ? Carbon::parse($request->string('from')) : now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->string('to')) : now()->endOfMonth();

        $incomeByCategory = $this->transactions->sumByCategory((int) $user->id, 'income', $from, $to);
        $expenseByCategory = $this->transactions->sumByCategory((int) $user->id, 'expense', $from, $to);

        $monthlyIncome = $this->transactions->monthlyTrend((int) $user->id, 'income', 12);
        $monthlyExpense = $this->transactions->monthlyTrend((int) $user->id, 'expense', 12);

        return Inertia::render('PersonalAccounting::Reports/Index', [
            'filters' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'summary' => [
                'income' => $this->transactions->sumByType((int) $user->id, 'income', $from, $to),
                'expense' => $this->transactions->sumByType((int) $user->id, 'expense', $from, $to),
                'net' => round(
                    $this->transactions->sumByType((int) $user->id, 'income', $from, $to)
                    - $this->transactions->sumByType((int) $user->id, 'expense', $from, $to),
                    2,
                ),
            ],
            'incomeByCategory' => $incomeByCategory,
            'expenseByCategory' => $expenseByCategory,
            'monthlyTrend' => [
                'labels' => $monthlyIncome->pluck('label')->values(),
                'income' => $monthlyIncome->pluck('total')->values(),
                'expense' => $monthlyExpense->pluck('total')->values(),
            ],
            'netWorth' => $this->netWorthHistory((int) $user->id, $from, $to),
        ]);
    }

    private function netWorthHistory(int $userId, Carbon $from, Carbon $to): array
    {
        // Aggregate cash-flow per month and produce a running net series.
        $startBalance = \Modules\PersonalAccounting\Models\PersonalAccount::query()
            ->forTenant((int) auth()->user()->tenant_id)
            ->where('user_id', $userId)
            ->sum('balance');

        $monthly = $this->transactions->monthlyTrend($userId, 'income', 12);

        $net = 0.0;
        return collect($monthly)->map(function (array $month) use (&$net, $startBalance): array {
            $net += $month['total'];
            $net -= $this->transactions->sumByType(
                (int) auth()->id(),
                'expense',
                Carbon::parse($month['month'])->startOfMonth(),
                Carbon::parse($month['month'])->endOfMonth(),
            );

            return [
                'label' => $month['label'],
                'value' => round((float) $startBalance + $net, 2),
            ];
        })->values()->all();
    }
}
