<?php

namespace Modules\PersonalAccounting\Services;

use Modules\PersonalAccounting\Models\PersonalAccount;
use Modules\PersonalAccounting\Models\PersonalCategory;

/**
 * Ensures a tenant has the default system categories (and, on first use, a
 * default cash account) so the UI always has meaningful options to present.
 */
class PersonalAccountingSetupService
{
    private const DEFAULT_EXPENSE_CATEGORIES = [
        ['Food & Dining', 'utensils', '#f97316'],
        ['Transport', 'car', '#0ea5e9'],
        ['Housing & Rent', 'home', '#8b5cf6'],
        ['Shopping', 'shopping-bag', '#ec4899'],
        ['Utilities', 'zap', '#eab308'],
        ['Entertainment', 'tv', '#6366f1'],
        ['Health & Medical', 'heart-pulse', '#ef4444'],
        ['Education', 'book', '#14b8a6'],
        ['Savings & Investments', 'piggy-bank', '#10b981'],
        ['Other', 'more', '#64748b'],
    ];

    private const DEFAULT_INCOME_CATEGORIES = [
        ['Salary', 'banknote', '#10b981'],
        ['Business', 'briefcase', '#0ea5e9'],
        ['Freelance', 'laptop', '#8b5cf6'],
        ['Investment', 'trending-up', '#eab308'],
        ['Gift', 'gift', '#ec4899'],
        ['Other Income', 'more', '#64748b'],
    ];

    public function ensureSystemCategories(int $tenantId): void
    {
        foreach (self::DEFAULT_EXPENSE_CATEGORIES as [$name, $icon, $color]) {
            PersonalCategory::firstOrCreate(
                ['tenant_id' => $tenantId, 'name' => $name, 'type' => 'expense', 'is_system' => true],
                ['icon' => $icon, 'color' => $color, 'is_system' => true],
            );
        }

        foreach (self::DEFAULT_INCOME_CATEGORIES as [$name, $icon, $color]) {
            PersonalCategory::firstOrCreate(
                ['tenant_id' => $tenantId, 'name' => $name, 'type' => 'income', 'is_system' => true],
                ['icon' => $icon, 'color' => $color, 'is_system' => true],
            );
        }
    }

    public function ensureDefaultAccount(int $userId, int $tenantId): PersonalAccount
    {
        return PersonalAccount::firstOrCreate(
            ['tenant_id' => $tenantId, 'user_id' => $userId, 'is_default' => true],
            [
                'name' => 'Cash Wallet',
                'type' => 'cash',
                'currency' => 'BDT',
                'balance' => 0,
                'color' => '#6366f1',
            ],
        );
    }
}
