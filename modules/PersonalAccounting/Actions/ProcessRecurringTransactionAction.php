<?php

namespace Modules\PersonalAccounting\Actions;

use Illuminate\Support\Facades\DB;
use Modules\PersonalAccounting\Models\PersonalRecurringTransaction;
use Modules\PersonalAccounting\Models\PersonalTransaction;

/**
 * Generates the next PersonalTransaction from a recurring template and advances
 * its schedule (next_run_at / last_run_at) according to the frequency.
 */
class ProcessRecurringTransactionAction
{
    public function __construct(private readonly CreateTransactionAction $createTransaction)
    {
    }

    public function handle(PersonalRecurringTransaction $recurring): ?PersonalTransaction
    {
        if (! $recurring->is_active) {
            return null;
        }

        return DB::transaction(function () use ($recurring): ?PersonalTransaction {
            $transaction = $this->createTransaction->handle($this->buildTransactionData($recurring));

            $recurring->forceFill([
                'last_run_at' => now(),
                'next_run_at' => $this->advanceDate($recurring->next_run_at, $recurring->frequency),
            ])->save();

            return $transaction;
        });
    }

    private function buildTransactionData(PersonalRecurringTransaction $recurring): array
    {
        $template = $recurring->template_data ?? [];

        return array_merge($template, [
            'tenant_id' => $recurring->tenant_id,
            'user_id' => $recurring->user_id,
            'account_id' => $recurring->account_id,
            'category_id' => $recurring->category_id,
            'type' => $recurring->type,
            'amount' => $recurring->amount,
            'date' => now()->toDateString(),
            'is_recurring' => true,
            'recurring_id' => $recurring->id,
        ]);
    }

    private function advanceDate(?\Illuminate\Support\Carbon $nextRunAt, string $frequency): \Illuminate\Support\Carbon
    {
        $base = $nextRunAt ?? now();

        return match ($frequency) {
            'daily' => $base->addDay(),
            'weekly' => $base->addWeek(),
            'yearly' => $base->addYear(),
            default => $base->addMonth(),
        };
    }
}
