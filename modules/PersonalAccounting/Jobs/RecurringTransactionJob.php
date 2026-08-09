<?php

namespace Modules\PersonalAccounting\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Modules\PersonalAccounting\Actions\ProcessRecurringTransactionAction;
use Modules\PersonalAccounting\Models\PersonalRecurringTransaction;

/**
 * Finds every recurring transaction that is due and processes it through the
 * ProcessRecurringTransactionAction. Scheduled daily by the module provider.
 */
class RecurringTransactionJob implements ShouldQueue
{
    use Queueable;

    public function handle(ProcessRecurringTransactionAction $processAction): void
    {
        PersonalRecurringTransaction::query()
            ->due()
            ->each(function (PersonalRecurringTransaction $recurring) use ($processAction): void {
                $processAction->handle($recurring);
            });
    }
}
