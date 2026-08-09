<?php

namespace Modules\PersonalAccounting\Actions;

use Modules\PersonalAccounting\Models\PersonalAccount;

/**
 * Single responsibility: apply the balance impact of a single transaction
 * onto an account. Used by the transaction service and the recurring action.
 */
class UpdateAccountBalanceAction
{
    /**
     * Adjust an account balance by the given amount. A positive delta increases
     * the balance (income); a negative delta decreases it (expense / transfer out).
     */
    public function handle(PersonalAccount $account, float $delta): PersonalAccount
    {
        $account->forceFill([
            'balance' => round((float) $account->balance + $delta, 2),
        ])->save();

        return $account->fresh();
    }

    /** Convenience for computing the signed delta from a transaction's type. */
    public function deltaFor(string $type, float $amount): float
    {
        if ($type === 'income') {
            return (float) $amount;
        }

        // expense and transfer both reduce the source account balance.
        return -abs((float) $amount);
    }
}
