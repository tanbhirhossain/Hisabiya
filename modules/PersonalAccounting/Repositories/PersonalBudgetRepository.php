<?php

namespace Modules\PersonalAccounting\Repositories;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Modules\PersonalAccounting\Interfaces\PersonalBudgetRepositoryInterface;
use Modules\PersonalAccounting\Models\PersonalBudget;
use Modules\PersonalAccounting\Models\PersonalTransaction;

class PersonalBudgetRepository extends BaseRepository implements PersonalBudgetRepositoryInterface
{
    protected string $model = PersonalBudget::class;

    public function budgetVsActual(int $userId, int $month, int $year): Collection
    {
        $start = \Illuminate\Support\Carbon::create($year, $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $budgets = $this->tenantScope()
            ->with('category:id,name')
            ->where('user_id', $userId)
            ->where(function ($query) use ($start, $end): void {
                $query->where(function ($q) use ($start): void {
                    $q->where('start_date', '<=', $start->toDateString());
                })->orWhere(function ($q) use ($end): void {
                    $q->where('start_date', '<=', $end->toDateString());
                });
            })
            ->get();

        // Actual spending per category within the month.
        $actuals = PersonalTransaction::query()
            ->withoutGlobalScope(\Modules\PersonalAccounting\Traits\Scopes\TenantScope::class)
            ->where('tenant_id', auth()->user()?->tenant_id)
            ->where('user_id', $userId)
            ->where('type', 'expense')
            ->whereNotNull('category_id')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy('category_id')
            ->map->sum('amount');

        return $budgets->map(function (PersonalBudget $budget) use ($actuals): array {
            $actual = (float) ($actuals[$budget->category_id] ?? 0);
            $amount = (float) $budget->amount;

            return [
                'budget_id' => $budget->id,
                'category' => $budget->category?->name ?? 'Uncategorised',
                'amount' => $amount,
                'actual' => $actual,
                'remaining' => round($amount - $actual, 2),
                'usage_percent' => $amount > 0 ? round(($actual / $amount) * 100, 2) : 0.0,
                'is_over' => $actual > $amount,
            ];
        });
    }
}
