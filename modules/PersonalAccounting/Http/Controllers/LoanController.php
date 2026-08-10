<?php

namespace Modules\PersonalAccounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\PersonalAccounting\Models\PersonalLoan;
use Modules\PersonalAccounting\Services\PersonalLoanService;

class LoanController extends Controller
{
    public function __construct(private readonly PersonalLoanService $service)
    {
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $tenantId = (int) $user->tenant_id;

        $loans = PersonalLoan::query()
            ->forTenant($tenantId)
            ->where('user_id', $user->id)
            ->with(['contact:id,name', 'payments:id,loan_id,amount,paid_at'])
            ->latest()
            ->get();

        return Inertia::render('PersonalAccounting::Loans/Index', [
            'loans' => $loans->map(function (PersonalLoan $loan): array {
                return [
                    ...$loan->toArray(),
                    'progress_percent' => $loan->progressPercent(),
                    'is_overdue' => $loan->isOverdue(),
                    'projection' => $this->service->projection($loan),
                    'payments_count' => $loan->payments->count(),
                ];
            }),
            'summary' => [
                'total_lent' => (float) $loans->where('direction', 'lent')->sum('remaining_balance'),
                'total_borrowed' => (float) $loans->where('direction', 'borrowed')->sum('remaining_balance'),
                'net' => round(
                    (float) $loans->where('direction', 'lent')->sum('remaining_balance')
                    - (float) $loans->where('direction', 'borrowed')->sum('remaining_balance'),
                    2,
                ),
            ],
            'contacts' => \Modules\PersonalAccounting\Models\PersonalContact::query()
                ->forTenant($tenantId)
                ->where('user_id', $user->id)
                ->orderBy('name')
                ->get(['id', 'name', 'type', 'phone']),
            'accounts' => \Modules\PersonalAccounting\Models\PersonalAccount::query()
                ->forTenant($tenantId)
                ->where('user_id', $user->id)
                ->orderBy('name')
                ->get(['id', 'name', 'type', 'balance']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'direction' => ['required', Rule::in(['borrowed', 'lent'])],
            'contact_id' => ['nullable', 'integer', 'exists:personal_contacts,id'],
            'principal_amount' => ['required', 'numeric', 'gt:0'],
            'interest_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'start_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'payment_frequency' => ['required', Rule::in(['weekly', 'biweekly', 'monthly', 'quarterly', 'yearly'])],
            'payment_amount' => ['nullable', 'numeric', 'min:0'],
            'account_id' => ['nullable', 'integer', 'exists:personal_accounts,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->service->create([
            ...$data,
            'tenant_id' => (int) $request->user()->tenant_id,
            'user_id' => (int) $request->user()->id,
            'interest_type' => 'simple',
            'currency' => 'BDT',
            'status' => 'active',
            'payment_amount' => $data['payment_amount'] ?? 0,
        ]);

        return redirect()->route('personal.loans.index')->with('success', 'Loan created.');
    }

    public function pay(Request $request, PersonalLoan $loan): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'account_id' => ['nullable', 'integer', 'exists:personal_accounts,id'],
            'paid_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->service->recordPayment(
            $loan,
            (float) $data['amount'],
            isset($data['account_id']) ? (int) $data['account_id'] : null,
            $data['note'] ?? null,
            $data['paid_at'] ?? null,
        );

        return redirect()->route('personal.loans.index')->with('success', 'Payment recorded.');
    }

    public function destroy(Request $request, PersonalLoan $loan): RedirectResponse
    {
        $loan->delete();

        return redirect()->route('personal.loans.index')->with('success', 'Loan deleted.');
    }
}
