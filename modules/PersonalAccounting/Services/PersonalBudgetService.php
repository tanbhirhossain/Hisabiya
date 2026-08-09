<?php

namespace Modules\PersonalAccounting\Services;

use Carbon\CarbonInterface;
use Modules\PersonalAccounting\Interfaces\PersonalBudgetServiceInterface;
use Modules\PersonalAccounting\Models\PersonalBudget;
use Modules\PersonalAccounting\Models\PersonalTransaction;
use Modules\PersonalAccounting\Repositories\PersonalBudgetRepository;

class PersonalBudgetService implements PersonalBudgetServiceInterface
{
    public function __construct(private readonly PersonalBudgetRepository $repository)
    {
    }

    public function createBudget(array $data): PersonalBudget
    {
        return $this->repository->create($data);
    }

    public function getBudgetProgress(PersonalBudget $budget): array
    {
        $from = $budget->start_date;
        $to = $budget->end_date ?? $this->periodEnd($budget);

        $actual = PersonalTransaction::query()
            ->where('tenant_id', $budget->tenant_id)
            ->where('user_id', $budget->user_id)
            ->where('type', 'expense')
            ->where('category_id', $budget->category_id)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');

        $amount = (float) $budget->amount;
        $actual = (float) $actual;

        return [
            'budget_id' => $budget->id,
            'category' => $budget->category?->name ?? 'Uncategorised',
            'amount' => $amount,
            'actual' => $actual,
            'remaining' => round($amount - $actual, 2),
            'usage_percent' => $amount > 0 ? round(($actual / $amount) * 100, 2) : 0.0,
            'is_over' => $actual > $amount,
        ];
    }

    public function alertOverBudget(int $userId): array
    {
        return $this->repository->budgetVsActual($userId, now()->month, now()->year)
            ->filter(fn (array $row) => $row['is_over'])
            ->values()
            ->all();
    }

    private function periodEnd(PersonalBudget $budget): CarbonInterface
    {
        $start = $budget->start_date->copy();

        return match ($budget->period) {
            'daily' => $start->endOfDay(),
            'weekly' => $start->endOfWeek(),
            'yearly' => $start->endOfYear(),
            default => $start->endOfMonth(),
        };
    }
}
