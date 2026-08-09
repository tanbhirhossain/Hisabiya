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
}
