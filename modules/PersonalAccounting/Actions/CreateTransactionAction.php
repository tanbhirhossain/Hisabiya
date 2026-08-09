<?php

namespace Modules\PersonalAccounting\Actions;

use Illuminate\Support\Facades\DB;
use Modules\PersonalAccounting\Models\PersonalAccount;
use Modules\PersonalAccounting\Models\PersonalTransaction;

/**
 * Creates a transaction and immediately updates the linked account balance,
 * both wrapped in a database transaction for atomicity.
 */
class CreateTransactionAction
{
    public function __construct(private readonly UpdateAccountBalanceAction $updateBalance)
    {
    }

    public function handle(array $data): PersonalTransaction
    {
        return DB::transaction(function () use ($data): PersonalTransaction {
            $transaction = PersonalTransaction::create($data);

            $account = PersonalAccount::findOrFail($transaction->account_id);
            $delta = $this->updateBalance->deltaFor($transaction->type, $transaction->amount);

            $this->updateBalance->handle($account, $delta);

            return $transaction->fresh()->load(['account:id,name', 'category:id,name']);
        });
    }
}
