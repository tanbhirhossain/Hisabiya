<?php

namespace Modules\PersonalAccounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\PersonalAccounting\Models\PersonalTransaction;
use Modules\PersonalAccounting\Services\PersonalAccountingSetupService;
use Modules\PersonalAccounting\Services\PersonalBudgetService;
use Modules\PersonalAccounting\Services\PersonalTransactionService;

class TransactionController extends Controller
{
    public function __construct(
        private readonly PersonalTransactionService $service,
        private readonly PersonalAccountingSetupService $setup,
        private readonly PersonalBudgetService $budgetService,
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
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('min_amount'), fn ($q) => $q->where('amount', '>=', $request->float('min_amount')))
            ->when($request->filled('max_amount'), fn ($q) => $q->where('amount', '<=', $request->float('max_amount')))
            ->when($request->filled('search'), fn ($q) => $q->where('note', 'like', '%'.$request->string('search').'%'))
            ->latest('date');

        return Inertia::render('PersonalAccounting::Transactions/Index', [
            'transactions' => $query->paginate($request->integer('per_page', 12))->withQueryString(),
            'filters' => $request->only(['from', 'to', 'type', 'category_id', 'account_id', 'status', 'min_amount', 'max_amount', 'search']),
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

        // Duplicate detection: if `force` is not set and a duplicate exists, redirect with a warning.
        if (! $request->boolean('force')) {
            $duplicate = $this->service->detectDuplicate(
                (int) $data['account_id'],
                (float) $data['amount'],
                $data['date'],
                $tenantId,
            );

            if ($duplicate) {
                return redirect()->back()->withErrors([
                    'duplicate' => 'A similar transaction exists on this date.',
                ])->withInput([
                    'duplicate_id' => $duplicate->id,
                    'duplicate_amount' => $duplicate->amount,
                    'duplicate_date' => $duplicate->date->toDateString(),
                ]);
            }
        }

        // These fields belong to the recurring template, not the transaction row.
        $transactionData = array_diff_key($data, array_flip(['frequency', 'is_recurring', 'end_type', 'end_date', 'max_occurrences']));

        $transaction = $this->service->createTransaction([
            ...$transactionData,
            'tenant_id' => $tenantId,
            'user_id' => (int) $user->id,
            'status' => $data['status'] ?? 'cleared',
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
                'end_type' => $data['end_type'] ?? 'never',
                'end_date' => $data['end_date'] ?? null,
                'max_occurrences' => $data['max_occurrences'] ?? null,
                'occurrences_count' => 0,
            ]);

            $transaction->forceFill(['is_recurring' => true])->save();
        }

        // Check budgets and dispatch warning/exceeded notifications.
        $this->budgetService->alertOverBudget((int) $user->id);

        return redirect()->back()->with('success', 'Transaction added.');
    }

    public function update(Request $request, PersonalTransaction $transaction): RedirectResponse
    {
        $data = $request->validate($this->rules());

        // For a transfer the destination is `to_account_id`; clear it otherwise.
        if (($data['type'] ?? '') !== 'transfer') {
            $data['to_account_id'] = null;
        }

        // `frequency` and end-condition fields are not transaction columns; drop them.
        $transactionData = array_diff_key($data, array_flip(['frequency', 'is_recurring', 'end_type', 'end_date', 'max_occurrences']));

        $this->service->updateTransaction((int) $transaction->id, $transactionData);

        return redirect()->back()->with('success', 'Transaction updated.');
    }

    public function bulkUpdate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
            'field' => ['required', 'string', Rule::in(['status', 'category_id'])],
            'value' => ['required'],
        ]);

        $user = $request->user();

        $count = $this->service->bulkUpdate(
            $data['ids'],
            $data['field'],
            $data['value'],
            (int) $user->tenant_id,
            (int) $user->id,
        );

        return response()->json(['success' => true, 'updated' => $count]);
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
            'status' => ['nullable', 'string', Rule::in(['cleared', 'pending'])],
            'is_recurring' => ['nullable', 'boolean'],
            'frequency' => ['nullable', 'string', Rule::in(['daily', 'weekly', 'monthly', 'yearly'])],
            'end_type' => ['nullable', 'string', Rule::in(['never', 'on_date', 'after_occurrences'])],
            'end_date' => ['nullable', 'date'],
            'max_occurrences' => ['nullable', 'integer', 'min:1'],
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
