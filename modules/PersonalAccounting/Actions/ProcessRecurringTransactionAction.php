<?php

namespace Modules\PersonalAccounting\Actions;

use Illuminate\Support\Facades\DB;
use Modules\PersonalAccounting\Models\PersonalRecurringLog;
use Modules\PersonalAccounting\Models\PersonalRecurringTransaction;
use Modules\PersonalAccounting\Models\PersonalTransaction;
use Modules\PersonalAccounting\Notifications\RecurringTransactionFailedNotification;
use Throwable;

/**
 * Generates the next PersonalTransaction from a recurring template and advances
 * its schedule (next_run_at / last_run_at) according to the frequency.
 *
 * Respects end conditions (never / on_date / after_occurrences), increments the
 * occurrence count, and records a log row for every run.
 */
class ProcessRecurringTransactionAction
{
    public function __construct(private readonly CreateTransactionAction $createTransaction)
    {
    }

    public function handle(PersonalRecurringTransaction $recurring): ?PersonalTransaction
    {
        if (! $recurring->is_active || $recurring->hasEnded()) {
            if ($recurring->hasEnded() && $recurring->is_active) {
                $recurring->forceFill(['is_active' => false])->save();
            }

            return null;
        }

        try {
            return DB::transaction(function () use ($recurring): ?PersonalTransaction {
                $transaction = $this->createTransaction->handle($this->buildTransactionData($recurring));

                $recurring->forceFill([
                    'last_run_at' => now(),
                    'next_run_at' => $this->advanceDate($recurring->next_run_at, $recurring->frequency),
                    'occurrences_count' => (int) $recurring->occurrences_count + 1,
                ])->save();

                // Auto-deactivate if the end condition is now met.
                if ($recurring->hasEnded()) {
                    $recurring->forceFill(['is_active' => false])->save();
                }

                $this->logRun($recurring, 'success', $transaction);

                return $transaction;
            });
        } catch (Throwable $e) {
            $this->logRun($recurring, 'failed', null, $e->getMessage());

            $recurring->user?->notify(new RecurringTransactionFailedNotification(
                $recurring->name,
                $e->getMessage(),
            ));

            throw $e;
        }
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

    private function logRun(PersonalRecurringTransaction $recurring, string $status, ?PersonalTransaction $transaction, ?string $error = null): void
    {
        PersonalRecurringLog::create([
            'tenant_id' => $recurring->tenant_id,
            'user_id' => $recurring->user_id,
            'recurring_id' => $recurring->id,
            'status' => $status,
            'transaction_id' => $transaction?->id,
            'error_message' => $error,
            'ran_at' => now(),
        ]);
    }
}
