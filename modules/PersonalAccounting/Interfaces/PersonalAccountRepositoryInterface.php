<?php

namespace Modules\PersonalAccounting\Interfaces;

use Illuminate\Support\Collection;

interface PersonalAccountRepositoryInterface extends RepositoryInterface
{
    /** Balances grouped by account type plus a grand total. */
    public function balanceSummary(int $userId): array;

    /** Most recent transactions across a user's accounts. */
    public function recentTransactions(int $userId, int $limit = 10): Collection;
}
