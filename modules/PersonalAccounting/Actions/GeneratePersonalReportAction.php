<?php

namespace Modules\PersonalAccounting\Actions;

use Carbon\CarbonInterface;
use Modules\PersonalAccounting\Interfaces\PersonalAccountRepositoryInterface;
use Modules\PersonalAccounting\Interfaces\PersonalBudgetRepositoryInterface;
use Modules\PersonalAccounting\Interfaces\PersonalTransactionRepositoryInterface;

/**
 * Composes a self-contained personal finance report for a date range using the
 * module's repositories. It is the single entry point controllers/analytics use.
 */
class GeneratePersonalReportAction
{
    public function __construct(
        private readonly PersonalTransactionRepositoryInterface $transactions,
        private readonly PersonalAccountRepositoryInterface $accounts,
        private readonly PersonalBudgetRepositoryInterface $budgets,
    ) {
    }

    public function handle(int $userId, CarbonInterface $from, CarbonInterface $to): array
    {
        $income = $this->transactions->sumByType($userId, 'income', $from, $to);
        $expense = $this->transactions->sumByType($userId, 'expense', $from, $to);
        $savings = $income - $expense;

        return [
            'range' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'summary' => [
                'income' => $income,
                'expense' => $expense,
                'savings' => round($savings, 2),
                'savings_rate' => $income > 0 ? round(($savings / $income) * 100, 2) : 0.0,
            ],
            'expenses_by_category' => $this->transactions->sumByCategory($userId, 'expense', $from, $to),
            'income_by_category' => $this->transactions->sumByCategory($userId, 'income', $from, $to),
            'transactions' => $this->transactions->findByDateRange($userId, $from, $to),
            'accounts' => $this->accounts->balanceSummary($userId),
            'budgets' => $this->budgets->budgetVsActual($userId, $to->month, $to->year),
        ];
    }

    /**
     * Current year vs previous year, side by side per month.
     */
    public function yearOverYearComparison(int $userId, int $tenantId): array
    {
        $currentYear = now()->year;
        $prevYear = $currentYear - 1;

        $months = collect(range(1, 12))->map(function (int $month) use ($userId, $tenantId, $currentYear, $prevYear): array {
            $cur = $this->monthTotals($userId, $tenantId, $currentYear, $month);
            $prev = $this->monthTotals($userId, $tenantId, $prevYear, $month);

            return [
                'month' => now()->month($month)->format('M'),
                'current_income' => $cur['income'],
                'current_expense' => $cur['expense'],
                'current_net' => round($cur['income'] - $cur['expense'], 2),
                'prev_income' => $prev['income'],
                'prev_expense' => $prev['expense'],
                'prev_net' => round($prev['income'] - $prev['expense'], 2),
            ];
        });

        return [
            'years' => [$prevYear, $currentYear],
            'months' => $months,
        ];
    }

    /**
     * Top N expense categories by total spend, with % of total spend.
     */
    public function topSpendingCategories(int $userId, int $tenantId, string $startDate, string $endDate, int $limit = 5): array
    {
        $from = \Illuminate\Support\Carbon::parse($startDate);
        $to = \Illuminate\Support\Carbon::parse($endDate);

        $total = (float) $this->transactions->sumByType($userId, 'expense', $from, $to);

        return $this->transactions->sumByCategory($userId, 'expense', $from, $to)
            ->sortByDesc('total')
            ->take($limit)
            ->map(function (array $row) use ($total): array {
                return [
                    'category' => $row['category'],
                    'total' => $row['total'],
                    'percent' => $total > 0 ? round(($row['total'] / $total) * 100, 1) : 0.0,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Cash-flow structure: inflows (income by category) and outflows (expense by category).
     */
    public function cashFlowSummary(int $userId, int $tenantId, string $startDate, string $endDate): array
    {
        $from = \Illuminate\Support\Carbon::parse($startDate);
        $to = \Illuminate\Support\Carbon::parse($endDate);

        $inflows = $this->transactions->sumByCategory($userId, 'income', $from, $to)->values();
        $outflows = $this->transactions->sumByCategory($userId, 'expense', $from, $to)->values();

        $totalIn = (float) $inflows->sum('total');
        $totalOut = (float) $outflows->sum('total');

        return [
            'total_inflows' => $totalIn,
            'total_outflows' => $totalOut,
            'net_cash_flow' => round($totalIn - $totalOut, 2),
            'inflows' => $inflows,
            'outflows' => $outflows,
        ];
    }

    /**
     * Totals for a specific year+month.
     */
    private function monthTotals(int $userId, int $tenantId, int $year, int $month): array
    {
        $from = \Illuminate\Support\Carbon::create($year, $month)->startOfMonth();
        $to = $from->copy()->endOfMonth();

        return [
            'income' => $this->transactions->sumByType($userId, 'income', $from, $to),
            'expense' => $this->transactions->sumByType($userId, 'expense', $from, $to),
        ];
    }
}
