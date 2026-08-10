<?php

namespace Modules\PersonalAccounting\Services;

use Illuminate\Support\Facades\DB;
use Modules\PersonalAccounting\Models\PersonalAccount;
use Modules\PersonalAccounting\Models\PersonalLoan;
use Modules\PersonalAccounting\Models\PersonalLoanPayment;

/**
 * Business logic for personal loans: creating a loan and recording payments.
 */
class PersonalLoanService
{
    public function __construct(private readonly PersonalTransactionService $transactions)
    {
    }

    /**
     * Create a loan. For "lent" loans the principal leaves the user's money;
     * for "borrowed" loans it arrives into the account.
     */
    public function create(array $data): PersonalLoan
    {
        return DB::transaction(function () use ($data): PersonalLoan {
            $data['remaining_balance'] = $data['principal_amount'];
            $data['total_paid'] = 0;

            $loan = PersonalLoan::create($data);

            // Move money in/out of the linked account for the principal.
            if (! empty($data['account_id']) && ($data['direction'] === 'lent' || $data['direction'] === 'borrowed')) {
                $this->transactions->createTransaction([
                    'tenant_id' => $data['tenant_id'],
                    'user_id' => $data['user_id'],
                    'account_id' => $data['account_id'],
                    'type' => $data['direction'] === 'lent' ? 'expense' : 'income',
                    'amount' => $data['principal_amount'],
                    'date' => $data['start_date'] ?? now()->toDateString(),
                    'note' => ($data['direction'] === 'lent' ? 'Lent to ' : 'Borrowed from ').($data['name'] ?? 'contact'),
                ]);
            }

            return $loan->fresh()->load('contact:id,name');
        });
    }

    /**
     * Record a payment against a loan and (optionally) move the money to/from an account.
     */
    public function recordPayment(PersonalLoan $loan, float $amount, ?int $accountId = null, ?string $note = null, ?string $date = null): PersonalLoanPayment
    {
        if ($amount <= 0) {
            abort(422, 'Payment amount must be greater than zero.');
        }

        if ($amount > (float) $loan->remaining_balance) {
            abort(422, 'Payment cannot exceed the remaining balance.');
        }

        return DB::transaction(function () use ($loan, $amount, $accountId, $note, $date): PersonalLoanPayment {
            // Split into principal (reduce balance) — interest is assumed simple at 0 for MVP.
            $payment = PersonalLoanPayment::create([
                'tenant_id' => $loan->tenant_id,
                'user_id' => $loan->user_id,
                'loan_id' => $loan->id,
                'amount' => $amount,
                'principal_part' => $amount,
                'interest_part' => 0,
                'paid_at' => $date ?? now()->toDateString(),
                'note' => $note,
            ]);

            $loan->forceFill([
                'remaining_balance' => max(0, round((float) $loan->remaining_balance - $amount, 2)),
                'total_paid' => round((float) $loan->total_paid + $amount, 2),
                'status' => (float) $loan->remaining_balance - $amount <= 0 ? 'completed' : 'active',
            ])->save();

            // If a payment is made via an account, reflect it in the ledger.
            if ($accountId) {
                // For a lent loan, receiving a repayment is income; for a borrowed
                // loan, making a repayment is an expense.
                $this->transactions->createTransaction([
                    'tenant_id' => $loan->tenant_id,
                    'user_id' => $loan->user_id,
                    'account_id' => $accountId,
                    'type' => $loan->direction === 'lent' ? 'income' : 'expense',
                    'amount' => $amount,
                    'date' => $date ?? now()->toDateString(),
                    'note' => $note ?? ($loan->direction === 'lent' ? 'Loan repayment received' : 'Loan payment made'),
                ]);
            }

            return $payment->load('loan:id,name,direction');
        });
    }

    /**
     * Compute a simple projection for a loan (scheduled monthly payment).
     */
    public function projection(PersonalLoan $loan): array
    {
        $remaining = (float) $loan->remaining_balance;
        $monthly = (float) $loan->payment_amount;

        if ($monthly <= 0) {
            return ['months_remaining' => null, 'estimated_clear' => null];
        }

        $months = (int) ceil($remaining / $monthly);

        return [
            'months_remaining' => $months,
            'estimated_clear' => now()->addMonths($months)->toDateString(),
        ];
    }
}
