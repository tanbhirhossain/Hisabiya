<?php

namespace Modules\PersonalAccounting\Services;

use App\Models\User;
use Carbon\CarbonInterface;
use Modules\PersonalAccounting\Interfaces\PersonalBudgetServiceInterface;
use Modules\PersonalAccounting\Models\PersonalBudget;
use Modules\PersonalAccounting\Models\PersonalTransaction;
use Modules\PersonalAccounting\Notifications\BudgetExceededNotification;
use Modules\PersonalAccounting\Notifications\BudgetWarningNotification;
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
        $budget = $budget->fresh();

        $from = $budget->start_date;
        $to = $budget->end_date ?? $this->periodEnd($budget);

        $actual = PersonalTransaction::query()
            ->where('tenant_id', $budget->tenant_id)
            ->where('user_id', $budget->user_id)
            ->where('type', 'expense')
            ->where('category_id', $budget->category_id)
            ->whereBetween('date', [$from->startOfDay()->toDateTimeString(), $to->endOfDay()->toDateTimeString()])
            ->sum('amount');

        $baseAmount = (float) $budget->amount;
        $rollover = (float) $budget->rollover_amount;
        // Effective limit = amount + rollover (carried from the previous period).
        $effectiveLimit = $baseAmount + $rollover;
        $actual = (float) $actual;

        return [
            'budget_id' => $budget->id,
            'category' => $budget->category?->name ?? 'Uncategorised',
            'amount' => $baseAmount,
            'rollover_amount' => $rollover,
            'rollover_enabled' => (bool) $budget->rollover_enabled,
            'effective_limit' => $effectiveLimit,
            'actual' => $actual,
            'remaining' => round($effectiveLimit - $actual, 2),
            'usage_percent' => $effectiveLimit > 0 ? round(($actual / $effectiveLimit) * 100, 2) : 0.0,
            'is_over' => $actual > $effectiveLimit,
            'notify_at_percent' => $budget->notify_at_percent ?? 80,
        ];
    }

    /**
     * Compute the unused amount from the previous period and store it as rollover.
     */
    public function calculateRollover(PersonalBudget $budget): float
    {
        $budget = $budget->fresh();

        if (! $budget->rollover_enabled) {
            $budget->forceFill(['rollover_amount' => 0])->save();

            return 0.0;
        }

        $periodStart = $budget->start_date->copy();
        $prevStart = $this->previousPeriodStart($budget);
        $prevEnd = $periodStart->subDay();

        $spent = PersonalTransaction::query()
            ->where('tenant_id', $budget->tenant_id)
            ->where('user_id', $budget->user_id)
            ->where('type', 'expense')
            ->where('category_id', $budget->category_id)
            ->whereBetween('date', [$prevStart->startOfDay()->toDateTimeString(), $prevEnd->endOfDay()->toDateTimeString()])
            ->sum('amount');

        $unused = max(0, (float) $budget->amount - (float) $spent);
        $budget->forceFill(['rollover_amount' => $unused])->save();

        return $unused;
    }

    public function alertOverBudget(int $userId): array
    {
        $rows = $this->repository->budgetVsActual($userId, now()->month, now()->year);

        $user = User::find($userId);

        foreach ($rows as $row) {
            $usage = (float) $row['usage_percent'];
            $isOver = (bool) $row['is_over'];
            $notifyAt = $row['notify_at_percent'] ?? 80;

            if (! $user) {
                continue;
            }

            if ($isOver) {
                $user->notify(new BudgetExceededNotification(
                    $row['category'],
                    (float) $row['actual'],
                    (float) $row['amount'],
                ));
            } elseif ($usage >= (float) $notifyAt) {
                $user->notify(new BudgetWarningNotification(
                    $row['category'],
                    (float) $row['actual'],
                    (float) $row['amount'],
                    (int) $notifyAt,
                ));
            }
        }

        return $rows->filter(fn (array $row) => $row['is_over'])
            ->values()
            ->all();
    }

    /**
     * Forecast total spending for the period based on the daily spend rate so far.
     */
    public function getSpendingForecast(PersonalBudget $budget): array
    {
        $budget = $budget->fresh();

        $periodStart = $budget->start_date;
        $periodEnd = $budget->end_date ?? $this->periodEnd($budget);

        $today = now()->startOfDay();
        $periodEnd = $periodEnd->lt($today) ? $today : $periodEnd;

        $spentSoFar = PersonalTransaction::query()
            ->where('tenant_id', $budget->tenant_id)
            ->where('user_id', $budget->user_id)
            ->where('type', 'expense')
            ->where('category_id', $budget->category_id)
            ->whereBetween('date', [$periodStart->startOfDay()->toDateTimeString(), $today->endOfDay()->toDateTimeString()])
            ->sum('amount');

        $daysElapsed = $periodStart->diffInDays($today) + 1;
        $daysInPeriod = $periodStart->diffInDays($periodEnd) + 1;
        $daysElapsed = max(1, $daysElapsed);
        $daysInPeriod = max(1, $daysInPeriod);

        $dailyRate = $spentSoFar / $daysElapsed;
        $projectedSpend = $dailyRate * $daysInPeriod;

        $effectiveLimit = (float) $budget->amount + (float) $budget->rollover_amount;
        $willExceed = $projectedSpend > $effectiveLimit;

        return [
            'projected_spend' => round($projectedSpend, 2),
            'days_remaining' => (int) max(0, $daysInPeriod - $daysElapsed),
            'days_elapsed' => (int) $daysElapsed,
            'days_in_period' => (int) $daysInPeriod,
            'spent_so_far' => round((float) $spentSoFar, 2),
            'effective_limit' => $effectiveLimit,
            'will_exceed' => $willExceed,
            'overage' => $willExceed ? round($projectedSpend - $effectiveLimit, 2) : 0.0,
        ];
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

    private function previousPeriodStart(PersonalBudget $budget): CarbonInterface
    {
        $start = $budget->start_date->copy();

        return match ($budget->period) {
            'daily' => $start->subDay(),
            'weekly' => $start->subWeek(),
            'yearly' => $start->subYear(),
            default => $start->subMonth(),
        };
    }
}
