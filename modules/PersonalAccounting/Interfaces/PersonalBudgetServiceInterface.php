<?php

namespace Modules\PersonalAccounting\Interfaces;

use Modules\PersonalAccounting\Models\PersonalBudget;

interface PersonalBudgetServiceInterface
{
    public function createBudget(array $data): PersonalBudget;

    public function getBudgetProgress(PersonalBudget $budget): array;

    /** Returns budgets that have already exceeded their limit this period. */
    public function alertOverBudget(int $userId): array;
}
