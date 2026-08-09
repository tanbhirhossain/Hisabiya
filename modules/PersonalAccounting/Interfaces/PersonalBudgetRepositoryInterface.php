<?php

namespace Modules\PersonalAccounting\Interfaces;

use Illuminate\Support\Collection;

interface PersonalBudgetRepositoryInterface extends RepositoryInterface
{
    /** Budget vs actual spent, per budget, for a given month & year. */
    public function budgetVsActual(int $userId, int $month, int $year): Collection;
}
