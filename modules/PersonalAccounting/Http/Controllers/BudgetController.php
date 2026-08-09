<?php

namespace Modules\PersonalAccounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\PersonalAccounting\Models\PersonalBudget;
use Modules\PersonalAccounting\Repositories\PersonalBudgetRepository;
use Modules\PersonalAccounting\Services\PersonalBudgetService;
use Modules\PersonalAccounting\Services\PersonalAccountingSetupService;

class BudgetController extends Controller
{
    public function __construct(
        private readonly PersonalBudgetRepository $repository,
        private readonly PersonalBudgetService $service,
        private readonly PersonalAccountingSetupService $setup,
    ) {
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $tenantId = (int) $user->tenant_id;

        $this->setup->ensureSystemCategories($tenantId);

        return Inertia::render('PersonalAccounting::Budgets/Index', [
            'budgets' => $this->repository->budgetVsActual((int) $user->id, now()->month, now()->year),
            'categories' => \Modules\PersonalAccounting\Models\PersonalCategory::query()
                ->forTenant($tenantId)
                ->where('type', 'expense')
                ->orderBy('name')
                ->get(['id', 'name', 'icon', 'color']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:personal_categories,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'period' => ['required', Rule::in(['daily', 'weekly', 'monthly', 'yearly'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $this->service->createBudget([
            ...$data,
            'tenant_id' => (int) $request->user()->tenant_id,
            'user_id' => (int) $request->user()->id,
        ]);

        return redirect()->back()->with('success', 'Budget created.');
    }

    public function destroy(Request $request, PersonalBudget $budget): RedirectResponse
    {
        $budget->delete();

        return redirect()->back()->with('success', 'Budget deleted.');
    }
}
