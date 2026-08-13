<?php

namespace Modules\PersonalAccounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\PersonalAccounting\Models\PersonalSavingsGoal;
use Modules\PersonalAccounting\Services\PersonalSavingsGoalService;

class GoalController extends Controller
{
    public function __construct(private readonly PersonalSavingsGoalService $service)
    {
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $tenantId = (int) $user->tenant_id;

        $goals = PersonalSavingsGoal::query()
            ->forTenant($tenantId)
            ->where('user_id', $user->id)
            ->with('account:id,name,type,color')
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('PersonalAccounting::Goals/Index', [
            'goals' => $goals->map(function (PersonalSavingsGoal $goal): array {
                return [
                    ...$goal->toArray(),
                    'progress_percent' => $goal->progressPercent(),
                    'contribution_history' => $this->service->getContributionHistory($goal),
                ];
            }),
            'accounts' => \Modules\PersonalAccounting\Models\PersonalAccount::query()
                ->forTenant($tenantId)
                ->where('user_id', $user->id)
                ->active()
                ->orderBy('name')
                ->get(['id', 'name', 'type']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'target_amount' => ['required', 'numeric', 'gt:0'],
            'deadline' => ['nullable', 'date'],
            'color' => ['nullable', 'string', 'max:20'],
            'icon' => ['nullable', 'string', 'max:50'],
            'account_id' => ['nullable', 'integer', 'exists:personal_accounts,id'],
        ]);

        PersonalSavingsGoal::create([
            ...$data,
            'tenant_id' => (int) $request->user()->tenant_id,
            'user_id' => (int) $request->user()->id,
            'current_amount' => 0,
            'status' => 'active',
            'color' => $data['color'] ?? '#10b981',
        ]);

        return redirect()->back()->with('success', 'Savings goal created.');
    }

    public function contribute(Request $request, PersonalSavingsGoal $goal): RedirectResponse
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'gt:0']]);
        $this->service->contribute($goal, (float) $data['amount']);

        return redirect()->back()->with('success', 'Contribution recorded.');
    }

    public function withdraw(Request $request, PersonalSavingsGoal $goal): RedirectResponse
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'gt:0']]);
        $this->service->withdraw($goal, (float) $data['amount']);

        return redirect()->back()->with('success', 'Withdrawal recorded.');
    }

    public function destroy(Request $request, PersonalSavingsGoal $goal): RedirectResponse
    {
        $goal->delete();

        return redirect()->back()->with('success', 'Goal deleted.');
    }
}
