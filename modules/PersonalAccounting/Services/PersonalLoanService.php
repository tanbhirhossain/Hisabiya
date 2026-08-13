<?php

namespace Modules\PersonalAccounting\Services;

use Illuminate\Support\Facades\DB;
use Modules\PersonalAccounting\Models\PersonalAccount;
use Modules\PersonalAccounting\Models\PersonalLoan;
use Modules\PersonalAccounting\Models\PersonalLoanPayment;
use Modules\PersonalAccounting\Notifications\LoanOverdueNotification;

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
            $paidDate = $date ?? now()->toDateString();

            // Late-payment penalty: if paid after the scheduled date and a penalty rate is set.
            $penalty = 0.0;
            if ($loan->next_payment_date
                && $paidDate > $loan->next_payment_date->toDateString()
                && (float) $loan->penalty_rate > 0) {
                $penalty = round((float) $loan->remaining_balance * ((float) $loan->penalty_rate / 100), 2);
            }

            // Split into principal + any penalty.
            $principalPart = max(0, round($amount - $penalty, 2));

            $payment = PersonalLoanPayment::create([
                'tenant_id' => $loan->tenant_id,
                'user_id' => $loan->user_id,
                'loan_id' => $loan->id,
                'amount' => $amount,
                'principal_part' => $principalPart,
                'interest_part' => 0,
                'penalty_amount' => $penalty,
                'paid_at' => $paidDate,
                'note' => $note,
            ]);

            $loan->forceFill([
                'remaining_balance' => max(0, round((float) $loan->remaining_balance - $principalPart, 2)),
                'total_paid' => round((float) $loan->total_paid + $amount, 2),
                'status' => (float) $loan->remaining_balance - $principalPart <= 0 ? 'completed' : 'active',
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

            $this->checkLoanNotifications($loan->fresh());

            return $payment->load('loan:id,name,direction');
        });
    }

    /**
     * After recording a payment, (re)check whether the loan is overdue and, if so,
     * dispatch an overdue notification.
     */
    private function checkLoanNotifications(PersonalLoan $loan): void
    {
        if ($loan->isOverdue() && $loan->status === 'active') {
            $daysOverdue = max(1, (int) now()->startOfDay()->diffInDays($loan->next_payment_date->startOfDay()));
            $loan->user?->notify(new LoanOverdueNotification(
                $loan->name,
                (float) $loan->remaining_balance,
                $daysOverdue,
            ));
        }
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

    /**
     * Structured loan statement for a printable/PDF export.
     *
     * @return array<string, mixed>
     */
    public function generateStatement(PersonalLoan $loan): array
    {
        $loan->load(['contact:id,name', 'payments' => fn ($q) => $q->orderBy('paid_at')]);

        $payments = $loan->payments->map(function (PersonalLoanPayment $payment): array {
            return [
                'date' => $payment->paid_at->toDateString(),
                'amount' => (float) $payment->amount,
                'principal_part' => (float) $payment->principal_part,
                'interest_part' => (float) $payment->interest_part,
                'penalty_amount' => (float) $payment->penalty_amount,
                'note' => $payment->note,
            ];
        })->values();

        return [
            'loan' => [
                'id' => $loan->id,
                'name' => $loan->name,
                'direction' => $loan->direction,
                'contact' => $loan->contact?->name,
                'principal_amount' => (float) $loan->principal_amount,
                'interest_rate' => (float) $loan->interest_rate,
                'penalty_rate' => (float) $loan->penalty_rate,
                'remaining_balance' => (float) $loan->remaining_balance,
                'total_paid' => (float) $loan->total_paid,
                'start_date' => $loan->start_date?->toDateString(),
                'due_date' => $loan->due_date?->toDateString(),
                'payment_frequency' => $loan->payment_frequency,
                'payment_amount' => (float) $loan->payment_amount,
                'status' => $loan->status,
            ],
            'payments' => $payments,
            'generated_at' => now()->format('d M Y H:i'),
        ];
    }
}
