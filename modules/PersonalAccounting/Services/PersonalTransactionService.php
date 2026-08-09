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

    public function updateTransaction(int $id, array $data): PersonalTransaction
    {
        $transaction = $this->repository->findOrFail($id);

        return DB::transaction(function () use ($transaction, $data): PersonalTransaction {
            // Reverse the previous balance effect.
            $account = PersonalAccount::findOrFail($transaction->account_id);
            $this->updateBalance->handle(
                $account,
                -1 * $this->updateBalance->deltaFor($transaction->type, $transaction->amount)
            );

            $transaction->update($data);
            $transaction->refresh();

            // Apply the new balance effect.
            $updatedAccount = PersonalAccount::findOrFail($transaction->account_id);
            $this->updateBalance->handle(
                $updatedAccount,
                $this->updateBalance->deltaFor($transaction->type, $transaction->amount)
            );

            return $transaction->fresh()->load(['account:id,name', 'category:id,name']);
        });
    }

    public function deleteTransaction(int $id): void
    {
        $transaction = $this->repository->findOrFail($id);

        DB::transaction(function () use ($transaction): void {
            $account = PersonalAccount::findOrFail($transaction->account_id);
            $this->updateBalance->handle(
                $account,
                -1 * $this->updateBalance->deltaFor($transaction->type, $transaction->amount)
            );

            $transaction->delete();
        });
    }
}
