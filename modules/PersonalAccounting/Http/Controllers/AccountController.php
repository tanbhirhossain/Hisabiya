<?php

namespace Modules\PersonalAccounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\PersonalAccounting\Models\PersonalAccount;
use Modules\PersonalAccounting\Models\PersonalTransaction;

class AccountController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $tenantId = (int) $user->tenant_id;

        return Inertia::render('PersonalAccounting::Accounts/Index', [
            'accounts' => PersonalAccount::query()
                ->forTenant($tenantId)
                ->where('user_id', $user->id)
                ->withCount('transactions')
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get(),
            'balance' => [
                'total' => (float) PersonalAccount::query()->forTenant($tenantId)->where('user_id', $user->id)->sum('balance'),
                'count' => PersonalAccount::query()->forTenant($tenantId)->where('user_id', $user->id)->count(),
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

    public function destroy(Request $request, PersonalAccount $account): RedirectResponse
    {
        $account->delete();

        return redirect()->route('personal.accounts.index')->with('success', 'Account deleted.');
    }
}
