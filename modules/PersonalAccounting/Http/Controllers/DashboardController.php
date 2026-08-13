<?php

namespace Modules\PersonalAccounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Modules\PersonalAccounting\Interfaces\PersonalAccountRepositoryInterface;
use Modules\PersonalAccounting\Interfaces\PersonalTransactionRepositoryInterface;
use Modules\PersonalAccounting\Models\PersonalAccount;
use Modules\PersonalAccounting\Models\PersonalLoan;
use Modules\PersonalAccounting\Models\PersonalRecurringTransaction;
use Modules\PersonalAccounting\Models\PersonalSavingsGoal;
use Modules\PersonalAccounting\Repositories\PersonalBudgetRepository;
use Modules\PersonalAccounting\Services\PersonalAccountingSetupService;

class DashboardController extends Controller
{
    public function __construct(
        private readonly PersonalAccountRepositoryInterface $accounts,
        private readonly PersonalTransactionRepositoryInterface $transactions,
        private readonly PersonalBudgetRepository $budgets,
        private readonly PersonalAccountingSetupService $setup,
    ) {
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $tenantId = (int) $user->tenant_id;

        $this->setup->ensureSystemCategories($tenantId);
        $this->setup->ensureDefaultAccount((int) $user->id, $tenantId);

        // Resolve the date range from the period query param.
        [$from, $to, $period] = $this->resolveRange($request);

        $income = (float) $this->transactions->sumByType((int) $user->id, 'income', $from, $to);
        $expense = (float) $this->transactions->sumByType((int) $user->id, 'expense', $from, $to);
        $net = round($income - $expense, 2);
        $savingsRate = $income > 0 ? round(($net / $income) * 100, 2) : 0.0;

        $balance = $this->accounts->balanceSummary((int) $user->id);
        $budgets = $this->budgets->budgetVsActual((int) $user->id, now()->month, now()->year);

        $netWorth = $this->netWorth($tenantId, (int) $user->id);

        return Inertia::render('PersonalAccounting::Dashboard/Index', [
            'date_range' => [
                'period' => $period,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'balance' => $balance,
            'month' => [
                'income' => $income,
                'expense' => $expense,
                'net' => $net,
                'savings_rate' => $savingsRate,
            ],
            'net_worth' => [
                'value' => $netWorth,
                'vs_last_month' => $net > 0 ? 'up' : 'down',
                'change' => $net,
            ],
            'spending_velocity' => $this->spendingVelocity((int) $user->id, $tenantId),
            'upcoming_recurring' => $this->upcomingRecurring($tenantId, (int) $user->id),
            'recentTransactions' => $this->accounts->recentTransactions((int) $user->id, 10),
            'topBudgets' => collect($budgets)->sortByDesc('usage_percent')->take(3)->values(),
            'categories' => $this->categoryOptions($tenantId),
            'accounts' => $balance['accounts'],
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function resolveRange(Request $request): array
    {
        $period = (string) $request->string('period', 'month');

        switch ($period) {
            case 'today':
                return [now()->startOfDay(), now()->endOfDay(), 'today'];
            case 'week':
                return [now()->startOfWeek(), now()->endOfWeek(), 'week'];
            case 'custom':
                $from = $request->filled('from') ? Carbon::parse($request->string('from'))->startOfDay() : now()->startOfDay();
                $to = $request->filled('to') ? Carbon::parse($request->string('to'))->endOfDay() : now()->endOfDay();

                return [$from, $to, 'custom'];
            default:
                return [now()->startOfMonth(), now()->endOfMonth(), 'month'];
        }
    }

    private function netWorth(int $tenantId, int $userId): float
    {
        $accountTotal = (float) PersonalAccount::query()
            ->forTenant($tenantId)->where('user_id', $userId)->active()->sum('balance');

        $goalTotal = (float) PersonalSavingsGoal::query()
            ->forTenant($tenantId)->where('user_id', $userId)->where('status', 'active')->sum('current_amount');

        $borrowedTotal = (float) PersonalLoan::query()
            ->forTenant($tenantId)->where('user_id', $userId)
            ->where('direction', 'borrowed')->where('status', 'active')->sum('remaining_balance');

        return round($accountTotal + $goalTotal - $borrowedTotal, 2);
    }

    /**
     * @return array<string, mixed>
     */
    private function spendingVelocity(int $userId, int $tenantId): array
    {
        $periodStart = now()->startOfMonth();
        $periodEnd = now()->endOfMonth();
        $today = now()->startOfDay();

        $spentSoFar = (float) $this->transactions->sumByType($userId, 'expense', $periodStart, $today);

        $totalBudget = (float) \Modules\PersonalAccounting\Models\PersonalBudget::query()
            ->forTenant($tenantId)->where('user_id', $userId)
            ->get()
            ->sum(fn ($b) => (float) $b->amount + (float) $b->rollover_amount);

        $daysElapsed = max(1, (int) $periodStart->diffInDays($today) + 1);
        $daysInPeriod = max(1, (int) $periodStart->diffInDays($periodEnd) + 1);
        $projectedTotal = round(($spentSoFar / $daysElapsed) * $daysInPeriod, 2);

        return [
            'spent_so_far' => $spentSoFar,
            'total_budget' => $totalBudget,
            'days_elapsed' => $daysElapsed,
            'days_in_period' => $daysInPeriod,
            'projected_total' => $projectedTotal,
        ];
    }

    private function upcomingRecurring(int $tenantId, int $userId): \Illuminate\Support\Collection
    {
        return PersonalRecurringTransaction::query()
            ->forTenant($tenantId)
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->where('next_run_at', '<=', now()->addDays(7))
            ->with('account:id,name,color')
            ->orderBy('next_run_at')
            ->limit(5)
            ->get(['id', 'name', 'type', 'amount', 'next_run_at', 'account_id']);
    }

    private function categoryOptions(int $tenantId): \Illuminate\Support\Collection
    {
        return \Modules\PersonalAccounting\Models\PersonalCategory::query()
            ->forTenant($tenantId)
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'icon', 'color']);
    }
}
