<?php

namespace Modules\PersonalAccounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\PersonalAccounting\Interfaces\PersonalAccountRepositoryInterface;
use Modules\PersonalAccounting\Interfaces\PersonalTransactionRepositoryInterface;
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

        $from = now()->startOfMonth();
        $to = now()->endOfMonth();

        $balance = $this->accounts->balanceSummary((int) $user->id);
        $budgets = $this->budgets->budgetVsActual((int) $user->id, now()->month, now()->year);

        return Inertia::render('PersonalAccounting::Dashboard/Index', [
            'balance' => $balance,
            'month' => [
                'income' => $this->transactions->sumByType((int) $user->id, 'income', $from, $to),
                'expense' => $this->transactions->sumByType((int) $user->id, 'expense', $from, $to),
            ],
            'recentTransactions' => $this->accounts->recentTransactions((int) $user->id, 10),
            'topBudgets' => collect($budgets)->sortByDesc('usage_percent')->take(3)->values(),
            'categories' => $this->categoryOptions($tenantId),
            'accounts' => $balance['accounts'],
        ]);
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
