<?php

namespace Modules\PersonalAccounting\Services;

use Illuminate\Support\Facades\DB;
use Modules\PersonalAccounting\Actions\CreateTransactionAction;
use Modules\PersonalAccounting\Actions\UpdateAccountBalanceAction;
use Modules\PersonalAccounting\Interfaces\PersonalTransactionServiceInterface;
use Modules\PersonalAccounting\Models\PersonalAccount;
use Modules\PersonalAccounting\Models\PersonalTransaction;
use Modules\PersonalAccounting\Repositories\PersonalTransactionRepository;

class PersonalTransactionService implements PersonalTransactionServiceInterface
{
    public function __construct(
        private readonly PersonalTransactionRepository $repository,
        private readonly CreateTransactionAction $createTransaction,
        private readonly UpdateAccountBalanceAction $updateBalance,
    ) {
    }

    public function createTransaction(array $data): PersonalTransaction
    {
        return $this->createTransaction->handle($data);
    }

    /**
     * Check whether a transaction with the same account + amount + date already
     * exists within the same tenant. Returns the duplicate or null.
     */
    public function detectDuplicate(int $accountId, float $amount, string $date, int $tenantId): ?PersonalTransaction
    {
        return PersonalTransaction::query()
            ->where('tenant_id', $tenantId)
            ->where('account_id', $accountId)
            ->where('amount', $amount)
            ->whereDate('date', $date)
            ->first();
    }

    /**
     * Apply a field change to multiple transactions at once (ownership-scoped).
     *
     * @param  array<int, int>  $ids
     */
    public function bulkUpdate(array $ids, string $field, mixed $value, int $tenantId, int $userId): int
    {
        $allowed = ['status', 'category_id'];

        if (! in_array($field, $allowed, true)) {
            abort(422, 'Unsupported bulk-update field.');
        }

        if ($field === 'status' && ! in_array($value, PersonalTransaction::STATUSES, true)) {
            abort(422, 'Invalid status.');
        }

        return PersonalTransaction::query()
            ->whereIn('id', $ids)
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->update([$field => $value]);
    }

    public function updateTransaction(int $id, array $data): PersonalTransaction
    {
        $transaction = $this->repository->findOrFail($id);

        return DB::transaction(function () use ($transaction, $data): PersonalTransaction {
            // Reverse the previous balance effect(s).
            $this->reverseEffects($transaction);

            $transaction->update($data);
            $transaction->refresh();

            // Apply the new balance effect(s).
            $this->applyEffects($transaction);

            return $transaction->fresh()->load(['account:id,name', 'toAccount:id,name', 'category:id,name']);
        });
    }

    public function deleteTransaction(int $id): void
    {
        $transaction = $this->repository->findOrFail($id);

        DB::transaction(function () use ($transaction): void {
            $this->reverseEffects($transaction);
            $transaction->delete();
        });
    }

    /**
     * Reverse a transaction's prior effect on account balance(s).
     */
    private function reverseEffects(PersonalTransaction $transaction): void
    {
        $account = PersonalAccount::findOrFail($transaction->account_id);

        if ($transaction->type === 'transfer' && $transaction->to_account_id) {
            $this->updateBalance->handle($account, abs((float) $transaction->amount));
            $this->updateBalance->handle(
                PersonalAccount::findOrFail($transaction->to_account_id),
                -abs((float) $transaction->amount),
            );
        } else {
            $this->updateBalance->handle(
                $account,
                -1 * $this->updateBalance->deltaFor($transaction->type, $transaction->amount)
            );
        }
    }

    /**
     * Apply a transaction's effect on account balance(s).
     */
    private function applyEffects(PersonalTransaction $transaction): void
    {
        $account = PersonalAccount::findOrFail($transaction->account_id);

        if ($transaction->type === 'transfer' && $transaction->to_account_id) {
            $this->updateBalance->handle($account, -abs((float) $transaction->amount));
            $this->updateBalance->handle(
                PersonalAccount::findOrFail($transaction->to_account_id),
                abs((float) $transaction->amount),
            );
        } else {
            $this->updateBalance->handle(
                $account,
                $this->updateBalance->deltaFor($transaction->type, $transaction->amount)
            );
        }
    }
}
