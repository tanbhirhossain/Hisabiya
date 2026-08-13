<?php

namespace Modules\PersonalAccounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\PersonalAccounting\Models\PersonalAccount;
use Modules\PersonalAccounting\Models\PersonalTransaction;
use Modules\PersonalAccounting\Services\PersonalAccountService;

class AccountController extends Controller
{
    public function __construct(private readonly PersonalAccountService $service)
    {
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $tenantId = (int) $user->tenant_id;
        $includeArchived = $request->boolean('archived');

        $query = PersonalAccount::query()
            ->forTenant($tenantId)
            ->where('user_id', $user->id);

        if (! $includeArchived) {
            $query->active();
        }

        return Inertia::render('PersonalAccounting::Accounts/Index', [
            'accounts' => $query
                ->withCount('transactions')
                ->orderBy('is_archived')
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get(),
            'balance' => [
                'total' => (float) PersonalAccount::query()->forTenant($tenantId)->where('user_id', $user->id)->active()->sum('balance'),
                'count' => PersonalAccount::query()->forTenant($tenantId)->where('user_id', $user->id)->active()->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(['cash', 'bank', 'mobile_banking'])],
            'currency' => ['required', 'string', 'max:10'],
            'balance' => ['nullable', 'numeric'],
            'is_default' => ['nullable', 'boolean'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $user = $request->user();
        $tenantId = (int) $user->tenant_id;

        if (($data['is_default'] ?? false)) {
            PersonalAccount::query()->forTenant($tenantId)->where('user_id', $user->id)->update(['is_default' => false]);
        }

        PersonalAccount::create([
            ...$data,
            'tenant_id' => $tenantId,
            'user_id' => (int) $user->id,
            'balance' => (float) ($data['balance'] ?? 0),
            'is_default' => (bool) ($data['is_default'] ?? false),
            'color' => $data['color'] ?? '#6366f1',
        ]);

        return redirect()->back()->with('success', 'Account added.');
    }

    public function show(Request $request, PersonalAccount $account): Response
    {
        $user = $request->user();

        return Inertia::render('PersonalAccounting::Accounts/Show', [
            'account' => $account->loadCount('transactions'),
            'transactions' => $account->transactions()
                ->with(['category:id,name,icon,color'])
                ->latest('date')
                ->paginate(15),
        ]);
    }

    /**
     * Archive an account (POST). Blocks if it's the only active account.
     */
    public function archive(Request $request, PersonalAccount $account): RedirectResponse
    {
        try {
            $this->service->archive($account);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }

        return redirect()->back()->with('success', 'Account archived.');
    }

    /**
     * Return the balance history for a chart (JSON).
     */
    public function balanceHistory(Request $request, PersonalAccount $account): JsonResponse
    {
        $period = $request->string('period', 'month');

        return response()->json([
            'data' => $this->service->getBalanceHistory($account, (string) $period),
            'account' => ['id' => $account->id, 'name' => $account->name, 'balance' => (float) $account->balance],
        ]);
    }

    public function destroy(Request $request, PersonalAccount $account): RedirectResponse
    {
        $account->delete();

        return redirect()->route('personal.accounts.index')->with('success', 'Account deleted.');
    }
}
