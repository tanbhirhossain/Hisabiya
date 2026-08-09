<?php

namespace Modules\PersonalAccounting\Services;

use Illuminate\Validation\ValidationException;
use Modules\PersonalAccounting\Interfaces\PersonalSavingsGoalServiceInterface;
use Modules\PersonalAccounting\Models\PersonalSavingsGoal;

class PersonalSavingsGoalService implements PersonalSavingsGoalServiceInterface
{
    public function contribute(PersonalSavingsGoal $goal, float $amount): PersonalSavingsGoal
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Contribution must be greater than zero.']);
        }

        $goal->forceFill([
            'current_amount' => round((float) $goal->current_amount + $amount, 2),
        ])->save();

        if ($goal->isCompleted()) {
            $goal->forceFill(['status' => 'completed'])->save();
        }

        return $goal->fresh();
    }

    public function withdraw(PersonalSavingsGoal $goal, float $amount): PersonalSavingsGoal
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Withdrawal must be greater than zero.']);
        }

        $newBalance = (float) $goal->current_amount - $amount;

        if ($newBalance < 0) {
            throw ValidationException::withMessages(['amount' => 'Withdrawal cannot exceed the saved amount.']);
        }

        $goal->forceFill([
            'current_amount' => round($newBalance, 2),
            'status' => 'active',
        ])->save();

        return $goal->fresh();
    }

    public function calculateProjection(PersonalSavingsGoal $goal, float $monthlyContribution): array
    {
        $remaining = (float) $goal->target_amount - (float) $goal->current_amount;

        if ($monthlyContribution <= 0) {
            return [
                'remaining' => round($remaining, 2),
                'months_remaining' => null,
                'estimated_completion' => null,
                'note' => 'Add a monthly contribution to project a completion date.',
            ];
        }

        $months = (int) ceil($remaining / $monthlyContribution);

        return [
            'remaining' => round($remaining, 2),
            'monthly_contribution' => round($monthlyContribution, 2),
            'months_remaining' => $months,
            'estimated_completion' => now()->addMonths($months)->toDateString(),
        ];
    }
}
