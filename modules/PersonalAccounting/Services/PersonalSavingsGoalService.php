<?php

namespace Modules\PersonalAccounting\Services;

use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Modules\PersonalAccounting\Interfaces\PersonalSavingsGoalServiceInterface;
use Modules\PersonalAccounting\Models\PersonalCategory;
use Modules\PersonalAccounting\Models\PersonalSavingsGoal;
use Modules\PersonalAccounting\Models\PersonalTransaction;
use Modules\PersonalAccounting\Notifications\SavingsGoalMilestoneNotification;
use Modules\PersonalAccounting\Notifications\SavingsGoalReachedNotification;

class PersonalSavingsGoalService implements PersonalSavingsGoalServiceInterface
{
    public function __construct(private readonly PersonalTransactionService $transactions)
    {
    }

    public function contribute(PersonalSavingsGoal $goal, float $amount): PersonalSavingsGoal
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Contribution must be greater than zero.']);
        }

        $oldPercent = $goal->progressPercent();
        $wasCompleted = $goal->isCompleted();

        $goal->forceFill([
            'current_amount' => round((float) $goal->current_amount + $amount, 2),
        ])->save();

        // If the goal is linked to an account, create a real expense transaction.
        if ($goal->account_id) {
            $this->createGoalTransaction($goal, 'expense', $amount);
        }

        $newPercent = $goal->progressPercent();
        $isCompleted = $goal->isCompleted();

        if ($isCompleted && ! $wasCompleted) {
            $goal->forceFill(['status' => 'completed'])->save();
            $goal->user?->notify(new SavingsGoalReachedNotification(
                $goal->name,
                (float) $goal->target_amount,
            ));
        } else {
            // Milestones at 25 / 50 / 75 percent.
            foreach ([25, 50, 75] as $milestone) {
                if ($oldPercent < $milestone && $newPercent >= $milestone) {
                    $goal->user?->notify(new SavingsGoalMilestoneNotification(
                        $goal->name,
                        (float) $goal->current_amount,
                        (float) $goal->target_amount,
                        $milestone,
                    ));
                    break;
                }
            }
        }

        return $goal->fresh()->load('account:id,name');
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

        // If the goal is linked to an account, create a real income transaction (money back).
        if ($goal->account_id) {
            $this->createGoalTransaction($goal, 'income', $amount);
        }

        return $goal->fresh()->load('account:id,name');
    }

    /**
     * Audit trail of contributions/withdrawals for this goal.
     */
    public function getContributionHistory(PersonalSavingsGoal $goal): Collection
    {
        $note = "Savings goal: {$goal->name}";

        return PersonalTransaction::query()
            ->where('tenant_id', $goal->tenant_id)
            ->where('user_id', $goal->user_id)
            ->where('note', 'like', "{$note}%")
            ->with('account:id,name')
            ->orderByDesc('date')
            ->get(['id', 'type', 'amount', 'date', 'note', 'account_id']);
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

    /**
     * Create an income/expense transaction for a linked account contribution.
     */
    private function createGoalTransaction(PersonalSavingsGoal $goal, string $type, float $amount): void
    {
        // Find or create a "Savings Transfer" style category.
        $category = PersonalCategory::query()
            ->where('tenant_id', $goal->tenant_id)
            ->where('type', $type)
            ->where('name', 'Savings')
            ->first();

        $categoryId = $category?->id;

        $this->transactions->createTransaction([
            'tenant_id' => $goal->tenant_id,
            'user_id' => $goal->user_id,
            'account_id' => $goal->account_id,
            'category_id' => $categoryId,
            'type' => $type,
            'amount' => $amount,
            'date' => now()->toDateString(),
            'note' => "Savings goal: {$goal->name}",
        ]);
    }
}
