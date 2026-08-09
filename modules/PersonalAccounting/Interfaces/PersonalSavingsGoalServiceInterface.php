<?php

namespace Modules\PersonalAccounting\Interfaces;

use Modules\PersonalAccounting\Models\PersonalSavingsGoal;

interface PersonalSavingsGoalServiceInterface
{
    public function contribute(PersonalSavingsGoal $goal, float $amount): PersonalSavingsGoal;

    public function withdraw(PersonalSavingsGoal $goal, float $amount): PersonalSavingsGoal;

    /** Projection of when the goal will be reached given a monthly contribution. */
    public function calculateProjection(PersonalSavingsGoal $goal, float $monthlyContribution): array;
}
