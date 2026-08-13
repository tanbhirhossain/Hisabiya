<?php

namespace Modules\PersonalAccounting\Services;

use Illuminate\Validation\ValidationException;
use Modules\PersonalAccounting\Models\PersonalAccount;
use Modules\PersonalAccounting\Models\PersonalTransaction;

/**
 * Domain logic for accounts: archiving (with a guard) and balance history.
 */
class PersonalAccountService
{
    /**
     * Archive an account. Blocks archiving if it's the only non-archived account.
     */
    public function archive(PersonalAccount $account): void
    {
        $activeCount = PersonalAccount::query()
            ->where('tenant_id', $account->tenant_id)
            ->where('user_id', $account->user_id)
            ->active()
            ->count();

        if ($activeCount <= 1) {
            throw ValidationException::withMessages([
                'account' => 'You must keep at least one active account.',
            ]);
        }

        $account->forceFill(['is_archived' => true])->save();

        // If this was the default, hand the default flag to another active account.
        if ($account->is_default) {
            $replacement = PersonalAccount::query()
                ->where('tenant_id', $account->tenant_id)
                ->where('user_id', $account->user_id)
                ->where('id', '!=', $account->id)
                ->active()
                ->orderBy('id')
                ->first();

            if ($replacement) {
                $replacement->forceFill(['is_default' => true])->save();
            }
        }
    }

    /**
     * Running balance over time for an account.
     *
     * @return array<int, array{date: string, balance: float}>
     */
    public function getBalanceHistory(PersonalAccount $account, string $period = 'month'): array
    {
        // Use a fresh balance so the anchor reflects reality.
        $account = $account->fresh();

        $from = now()->startOfDay();
        $to = now();

        switch ($period) {
            case 'week':
                $from = now()->startOfWeek();
                break;
            case 'year':
                $from = now()->startOfYear();
                break;
            default:
                $from = now()->startOfMonth();
        }

        $transactions = PersonalTransaction::query()
            ->where('tenant_id', $account->tenant_id)
            ->where('user_id', $account->user_id)
            ->where(function ($q) use ($account): void {
                $q->where('account_id', $account->id)->orWhere('to_account_id', $account->id);
            })
            ->where('date', '>=', $from->toDateString())
            ->orderBy('date')
            ->get(['type', 'amount', 'to_account_id', 'account_id', 'date']);

        // Start from the account's current balance and work backward is complex;
        // instead compute a running total from the period start, anchored so the
        // last point equals the current balance.
        $running = 0.0;
        $points = [];

        foreach ($transactions as $txn) {
            if ($txn->type === 'income') {
                $delta = $txn->account_id === $account->id ? (float) $txn->amount : 0;
            } elseif ($txn->type === 'expense') {
                $delta = $txn->account_id === $account->id ? -(float) $txn->amount : 0;
            } else {
                // transfer: out of this account (debit) or into it (credit).
                if ($txn->account_id === $account->id) {
                    $delta = -(float) $txn->amount;
                } elseif ($txn->to_account_id === $account->id) {
                    $delta = (float) $txn->amount;
                } else {
                    $delta = 0;
                }
            }

            $running += $delta;
            $points[] = ['date' => $txn->date->toDateString(), 'balance' => round($running, 2)];
        }

        // Anchor the series to end at the current account balance.
        if (! empty($points)) {
            $last = (float) $account->balance;
            $diff = $last - (float) $points[count($points) - 1]['balance'];
            foreach ($points as &$point) {
                $point['balance'] = round($point['balance'] + $diff, 2);
            }
        }

        return $points;
    }
}
