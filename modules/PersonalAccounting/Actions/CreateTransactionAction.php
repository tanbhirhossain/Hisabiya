<?php

namespace Modules\PersonalAccounting\Actions;

use Illuminate\Support\Facades\DB;
use Modules\PersonalAccounting\Models\PersonalAccount;
use Modules\PersonalAccounting\Models\PersonalTransaction;

/**
 * Creates a transaction and immediately updates the linked account balance(s),
 * wrapped in a database transaction for atomicity. Transfers debit the source
 * account and credit the destination account.
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

            if ($transaction->type === 'transfer' && $transaction->to_account_id) {
                // Money leaves the source and lands in the destination.
                $this->updateBalance->handle($account, -abs((float) $transaction->amount));
                $this->updateBalance->handle(
                    PersonalAccount::findOrFail($transaction->to_account_id),
                    abs((float) $transaction->amount),
                );
            } else {
                $delta = $this->updateBalance->deltaFor($transaction->type, $transaction->amount);
                $this->updateBalance->handle($account, $delta);
            }

            return $transaction->fresh()->load(['account:id,name', 'toAccount:id,name', 'category:id,name']);
        });
    }
}
