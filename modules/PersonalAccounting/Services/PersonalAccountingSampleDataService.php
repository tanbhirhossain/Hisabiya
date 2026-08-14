<?php

namespace Modules\PersonalAccounting\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\CORE\Models\CoreSetting;
use Modules\PersonalAccounting\Models\PersonalAccount;
use Modules\PersonalAccounting\Models\PersonalBudget;
use Modules\PersonalAccounting\Models\PersonalCategory;
use Modules\PersonalAccounting\Models\PersonalLoan;
use Modules\PersonalAccounting\Models\PersonalSavingsGoal;
use Modules\PersonalAccounting\Models\PersonalTransaction;

/**
 * Generates a realistic set of sample data for a new tenant so they can explore
 * the Personal Accounting module without starting from an empty screen. Data is
 * fully tenant-scoped and idempotent (won't duplicate if run twice).
 */
class PersonalAccountingSampleDataService
{
    private const FLAG = 'personal_accounting.sample_data_loaded';

    /**
     * Whether sample data has already been loaded for this tenant.
     */
    public function loaded(int $tenantId): bool
    {
        return (bool) CoreSetting::get(self::FLAG.'_'.$tenantId, false);
    }

    /**
     * Load realistic sample data for a user/tenant. Safe to call once; repeated
     * calls are ignored unless $force is true.
     *
     * @return int number of records created
     */
    public function load(int $userId, int $tenantId, bool $force = false): int
    {
        if ($this->loaded($tenantId) && ! $force) {
            return 0;
        }

        $setup = app(PersonalAccountingSetupService::class);
        $setup->ensureSystemCategories($tenantId);

        $created = 0;

        // Two accounts.
        $cash = $setup->ensureDefaultAccount($userId, $tenantId);
        $bank = PersonalAccount::firstOrCreate(
            ['tenant_id' => $tenantId, 'user_id' => $userId, 'name' => 'Bank Account'],
            ['type' => 'bank', 'currency' => 'BDT', 'balance' => 0, 'color' => '#0ea5e9', 'icon' => 'landmark'],
        );

        $cat = fn (string $name, string $type) => PersonalCategory::firstOrCreate(
            ['tenant_id' => $tenantId, 'name' => $name, 'type' => $type],
        );

        // Income + expense categories.
        $salary = $cat('Salary', 'income');
        $freelance = $cat('Freelance', 'income');
        $food = $cat('Food & Dining', 'expense');
        $transport = $cat('Transport', 'expense');
        $shopping = $cat('Shopping', 'expense');
        $utilities = $cat('Utilities', 'expense');
        $rent = $cat('Housing & Rent', 'expense');
        $entertainment = $cat('Entertainment', 'expense');

        // Balance updates.
        $created += $this->seedTransactions($userId, $tenantId, $cash, $bank, [
            [$salary, 'income', 60000, 1],
            [$freelance, 'income', 15000, 1],
            [$rent, 'expense', 12000, 2],
            [$food, 'expense', 8000, 3],
            [$transport, 'expense', 3500, 3],
            [$utilities, 'expense', 3000, 2],
            [$shopping, 'expense', 6500, 2],
            [$entertainment, 'expense', 2500, 2],
            [$salary, 'income', 60000, 3],
            [$food, 'expense', 7500, 4],
            [$transport, 'expense', 2000, 4],
            [$shopping, 'expense', 4000, 5],
        ]);

        // A couple of budgets.
        $created += $this->seedBudgets($userId, $tenantId, [
            [$food, 10000],
            [$transport, 4000],
            [$shopping, 6000],
        ]);

        // A savings goal.
        $goal = PersonalSavingsGoal::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'account_id' => $bank->id,
            'name' => 'Emergency Fund',
            'target_amount' => 100000,
            'current_amount' => 25000,
            'deadline' => now()->addMonths(8)->toDateString(),
            'status' => 'active',
            'color' => '#10b981',
            'icon' => 'piggy-bank',
        ]);
        $created++;

        // A loan.
        $loan = PersonalLoan::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'account_id' => $bank->id,
            'name' => 'Home Renovation Loan',
            'direction' => 'borrowed',
            'counterparty' => 'Brac Bank',
            'principal_amount' => 200000,
            'interest_rate' => 9.5,
            'interest_type' => 'reducing',
            'remaining_balance' => 165000,
            'total_paid' => 35000,
            'start_date' => now()->subMonths(5)->toDateString(),
            'due_date' => now()->addYears(4)->toDateString(),
            'next_payment_date' => now()->addDays(10)->toDateString(),
            'payment_frequency' => 'monthly',
            'payment_amount' => 7000,
            'status' => 'active',
            'currency' => 'BDT',
        ]);
        $created++;

        CoreSetting::set(self::FLAG.'_'.$tenantId, true);

        return $created;
    }

    /**
     * @param  array<int, array{0: PersonalCategory, 1: string, 2: float, 3: int}>  $rows
     */
    private function seedTransactions(int $userId, int $tenantId, PersonalAccount $cash, PersonalAccount $bank, array $rows): int
    {
        $count = 0;

        foreach ($rows as [$category, $type, $amount, $weekOffset]) {
            $isIncome = $type === 'income';
            $account = $isIncome ? $bank : $cash;

            // Keep account balances roughly consistent with the transactions.
            $delta = $isIncome ? $amount : -$amount;

            PersonalTransaction::create([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'account_id' => $account->id,
                'category_id' => $category->id,
                'type' => $type,
                'amount' => $amount,
                'date' => now()->subWeeks($weekOffset)->toDateString(),
                'note' => 'Sample '.$type,
                'status' => 'cleared',
            ]);

            $account->increment('balance', $delta);
            $count++;
        }

        return $count;
    }

    /**
     * @param  array<int, array{0: PersonalCategory, 1: float}>  $rows
     */
    private function seedBudgets(int $userId, int $tenantId, array $rows): int
    {
        $count = 0;

        foreach ($rows as [$category, $amount]) {
            PersonalBudget::create([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'category_id' => $category->id,
                'amount' => $amount,
                'period' => 'monthly',
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->endOfMonth()->toDateString(),
            ]);
            $count++;
        }

        return $count;
    }
}
