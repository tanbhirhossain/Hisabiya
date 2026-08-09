<?php

namespace Modules\PersonalAccounting\Interfaces;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

interface PersonalTransactionRepositoryInterface extends RepositoryInterface
{
    /** Transactions for a user within an inclusive date range. */
    public function findByDateRange(int $userId, CarbonInterface $from, CarbonInterface $to): Collection;

    /** Total amount grouped by category, filtered by type and date range. */
    public function sumByCategory(int $userId, string $type, CarbonInterface $from, CarbonInterface $to): Collection;

    /** Total amount for a given transaction type within a date range. */
    public function sumByType(int $userId, string $type, CarbonInterface $from, CarbonInterface $to): float;

    /** Month-over-month totals for a transaction type. */
    public function monthlyTrend(int $userId, string $type, int $months = 12): Collection;
}
