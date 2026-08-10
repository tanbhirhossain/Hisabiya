<?php

namespace Modules\PersonalAccounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\PersonalAccounting\Models\PersonalTransaction;
use Modules\PersonalAccounting\Services\PersonalAccountingSetupService;
use Modules\PersonalAccounting\Services\PersonalTransactionService;

class TransactionController extends Controller
{
    public function __construct(
        private readonly PersonalTransactionService $service,
        private readonly PersonalAccountingSetupService $setup,
    ) {
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $tenantId = (int) $user->tenant_id;

        $this->setup->ensureSystemCategories($tenantId);

        $query = PersonalTransaction::query()
            ->with(['account:id,name,color', 'toAccount:id,name,color', 'category:id,name,icon,color'])
            ->when($request->filled('from'), fn ($q) => $q->where('date', '>=', $request->string('from')))
            ->when($request->filled('to'), fn ($q) => $q->where('date', '<=', $request->string('to')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')))
            ->when($request->filled('account_id'), fn ($q) => $q->where('account_id', $request->integer('account_id')))
            ->when($request->filled('search'), fn ($q) => $q->where('note', 'like', '%'.$request->string('search').'%'))
            ->latest('date');

        return Inertia::render('PersonalAccounting::Transactions/Index', [
            'transactions' => $query->paginate($request->integer('per_page', 12))->withQueryString(),
            'filters' => $request->only(['from', 'to', 'type', 'category_id', 'account_id', 'search']),
            'accounts' => $this->accountOptions($tenantId, (int) $user->id),
            'categories' => $this->categoryOptions($tenantId),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $user = $request->user();
        $tenantId = (int) $user->tenant_id;

        // For a transfer the destination is `to_account_id`; clear it otherwise.
        if (($data['type'] ?? '') !== 'transfer') {
            $data['to_account_id'] = null;
        }

        // `frequency` belongs to a recurring template, not the transaction row.
        $transactionData = array_diff_key($data, array_flip(['frequency', 'is_recurring']));

        $transaction = $this->service->createTransaction([
            ...$transactionData,
            'tenant_id' => $tenantId,
            'user_id' => (int) $user->id,
            'is_recurring' => (bool) ($data['is_recurring'] ?? false),
            'attachment_path' => null,
        ]);

        // If the user asked for a recurring transaction, create the schedule template.
        if (($data['is_recurring'] ?? false)) {
            \Modules\PersonalAccounting\Models\PersonalRecurringTransaction::create([
                'tenant_id' => $tenantId,
                'user_id' => (int) $user->id,
                'name' => $data['note'] ?: ('Recurring '.$data['type']),
                'account_id' => $data['account_id'],
                'category_id' => $data['category_id'] ?? null,
                'type' => $data['type'],
                'amount' => $data['amount'],
                'frequency' => $data['frequency'] ?? 'monthly',
                'next_run_at' => now(),
                'is_active' => true,
            ]);

            $transaction->forceFill(['is_recurring' => true])->save();
        }

        return redirect()->back()->with('success', 'Transaction added.');
    }

    public function update(Request $request, PersonalTransaction $transaction): RedirectResponse
    {
        $data = $request->validate($this->rules());

        // For a transfer the destination is `to_account_id`; clear it otherwise.
        if (($data['type'] ?? '') !== 'transfer') {
            $data['to_account_id'] = null;
        }

        // `frequency` is not a transaction column; drop it before updating.
        $transactionData = array_diff_key($data, array_flip(['frequency', 'is_recurring']));

        $this->service->updateTransaction((int) $transaction->id, $transactionData);

        return redirect()->back()->with('success', 'Transaction updated.');
    }

    public function destroy(Request $request, PersonalTransaction $transaction): RedirectResponse
    {
        $this->service->deleteTransaction((int) $transaction->id);

        return redirect()->back()->with('success', 'Transaction deleted.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $ids = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']])['ids'];

        foreach (PersonalTransaction::whereIn('id', $ids)->get() as $transaction) {
            $this->service->deleteTransaction((int) $transaction->id);
        }

        return redirect()->back()->with('success', count($ids).' transaction(s) deleted.');
    }

    private function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['income', 'expense', 'transfer'])],
            'amount' => ['required', 'numeric', 'gt:0'],
            'account_id' => ['required', 'integer', 'exists:personal_accounts,id'],
            'to_account_id' => ['nullable', 'integer', 'different:account_id', 'exists:personal_accounts,id'],
            'category_id' => ['nullable', 'integer', 'exists:personal_categories,id'],
            'date' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
            'is_recurring' => ['nullable', 'boolean'],
            'frequency' => ['nullable', 'string', Rule::in(['daily', 'weekly', 'monthly', 'yearly'])],
        ];
    }

    private function accountOptions(int $tenantId, int $userId): \Illuminate\Support\Collection
    {
        return \Modules\PersonalAccounting\Models\PersonalAccount::query()
            ->forTenant($tenantId)
            ->where('user_id', $userId)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'color', 'balance', 'currency']);
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
