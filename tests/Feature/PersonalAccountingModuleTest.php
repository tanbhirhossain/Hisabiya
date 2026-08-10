<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CORE\Models\Tenant;
use Modules\PersonalAccounting\Models\PersonalAccount;
use Modules\PersonalAccounting\Models\PersonalBudget;
use Modules\PersonalAccounting\Models\PersonalCategory;
use Modules\PersonalAccounting\Models\PersonalRecurringTransaction;
use Modules\PersonalAccounting\Models\PersonalSavingsGoal;
use Modules\PersonalAccounting\Models\PersonalTransaction;

uses(RefreshDatabase::class);

function paUser(): User
{
    $tenant = Tenant::create(['name' => 'PA Tenant', 'slug' => 'pa-tenant', 'status' => 'active', 'plan' => 'free']);

    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    // Grant the Personal Accounting module permissions so the middleware allows access.
    $permissions = [
        'personal-accounting.view',
        'personal-accounting.transactions.view',
        'personal-accounting.transactions.create',
        'personal-accounting.transactions.update',
        'personal-accounting.transactions.delete',
        'personal-accounting.accounts.view',
        'personal-accounting.accounts.manage',
        'personal-accounting.budgets.view',
        'personal-accounting.budgets.manage',
        'personal-accounting.goals.view',
        'personal-accounting.goals.manage',
        'personal-accounting.reports.view',
        'personal-accounting.loans.view',
        'personal-accounting.loans.manage',
        'personal-accounting.contacts.view',
        'personal-accounting.contacts.manage',
    ];
    foreach ($permissions as $name) {
        Spatie\Permission\Models\Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }
    $user->givePermissionTo($permissions);

    return $user;
}

function paAccount(User $user): PersonalAccount
{
    return PersonalAccount::create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
        'name' => 'Wallet',
        'type' => 'cash',
        'currency' => 'BDT',
        'balance' => 0,
        'is_default' => true,
    ]);
}

function paCategory(User $user, string $type, string $name): PersonalCategory
{
    return PersonalCategory::create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
        'name' => $name,
        'type' => $type,
    ]);
}

test('transaction service creates income and updates account balance', function () {
    $user = paUser();
    $this->actingAs($user);
    $account = paAccount($user);
    $category = paCategory($user, 'income', 'Salary');

    $transaction = app(\Modules\PersonalAccounting\Services\PersonalTransactionService::class)
        ->createTransaction([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'income',
            'amount' => 50000,
            'date' => now()->toDateString(),
        ]);

    expect($transaction->account->fresh()->balance)->toBe('50000.00');
});

test('expense transaction decreases account balance', function () {
    $user = paUser();
    $this->actingAs($user);
    $account = paAccount($user);
    $account->update(['balance' => 50000]);
    $category = paCategory($user, 'expense', 'Food');

    app(\Modules\PersonalAccounting\Services\PersonalTransactionService::class)
        ->createTransaction([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 1200,
            'date' => now()->toDateString(),
        ]);

    expect($account->fresh()->balance)->toBe('48800.00');
});

test('updating and deleting a transaction keeps the balance consistent', function () {
    $user = paUser();
    $this->actingAs($user);
    $account = paAccount($user);
    $category = paCategory($user, 'expense', 'Food');
    $service = app(\Modules\PersonalAccounting\Services\PersonalTransactionService::class);

    $txn = $service->createTransaction([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
        'account_id' => $account->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 500,
        'date' => now()->toDateString(),
    ]);

    $account = $txn->account->fresh();
    expect($account->balance)->toBe('-500.00');

    // Update amount from 500 -> 300.
    $service->updateTransaction($txn->id, [
        'account_id' => $account->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 300,
        'date' => now()->toDateString(),
    ]);

    expect($account->fresh()->balance)->toBe('-300.00');

    // Delete -> balance returns to 0.
    $service->deleteTransaction($txn->id);
    expect($account->fresh()->balance)->toBe('0.00');
    expect(PersonalTransaction::find($txn->id))->toBeNull();
});

test('repository reports sum by type and monthly trend', function () {
    $user = paUser();
    $this->actingAs($user);
    $account = paAccount($user);
    $category = paCategory($user, 'income', 'Salary');
    $repo = app(\Modules\PersonalAccounting\Interfaces\PersonalTransactionRepositoryInterface::class);

    PersonalTransaction::create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
        'account_id' => $account->id,
        'category_id' => $category->id,
        'type' => 'income',
        'amount' => 1000,
        'date' => now()->toDateString(),
    ]);
    PersonalTransaction::create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
        'account_id' => $account->id,
        'category_id' => $category->id,
        'type' => 'income',
        'amount' => 2000,
        'date' => now()->toDateString(),
    ]);

    $sum = $repo->sumByType($user->id, 'income', now()->startOfMonth(), now()->endOfMonth());
    expect($sum)->toBe(3000.0);

    $trend = $repo->monthlyTrend($user->id, 'income', 12);
    expect($trend)->toHaveCount(12);
    expect((float) $trend->last()['total'])->toBe(3000.0);
});

test('savings goal service contributes, withdraws and projects', function () {
    $user = paUser();
    $this->actingAs($user);
    $goal = PersonalSavingsGoal::create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
        'name' => 'Emergency fund',
        'target_amount' => 100000,
        'current_amount' => 0,
    ]);
    $service = app(\Modules\PersonalAccounting\Services\PersonalSavingsGoalService::class);

    $goal = $service->contribute($goal, 25000);
    expect((float) $goal->current_amount)->toBe(25000.0);

    $goal = $service->withdraw($goal, 5000);
    expect((float) $goal->current_amount)->toBe(20000.0);

    $projection = $service->calculateProjection($goal, 10000);
    expect($projection['remaining'])->toBe(80000.0);
    expect($projection['months_remaining'])->toBe(8);
});

test('budget service reports progress and over-budget alerts', function () {
    $user = paUser();
    $this->actingAs($user);
    $account = paAccount($user);
    $category = paCategory($user, 'expense', 'Food');
    $budget = PersonalBudget::create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
        'category_id' => $category->id,
        'amount' => 1000,
        'period' => 'monthly',
        'start_date' => now()->startOfMonth()->toDateString(),
    ]);
    $service = app(\Modules\PersonalAccounting\Services\PersonalBudgetService::class);

    $progress = $service->getBudgetProgress($budget);
    expect($progress['usage_percent'])->toBe(0.0);

    app(\Modules\PersonalAccounting\Services\PersonalTransactionService::class)->createTransaction([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
        'account_id' => $account->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 1500,
        'date' => now()->toDateString(),
    ]);

    $progress = $service->getBudgetProgress($budget);
    expect($progress['is_over'])->toBeTrue();

    $alerts = $service->alertOverBudget($user->id);
    expect($alerts)->toHaveCount(1);
});

test('recurring job processes due recurring transactions', function () {
    $user = paUser();
    $this->actingAs($user);
    $account = paAccount($user);
    $category = paCategory($user, 'expense', 'Rent');

    $recurring = PersonalRecurringTransaction::create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
        'name' => 'Monthly rent',
        'account_id' => $account->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 15000,
        'frequency' => 'monthly',
        'next_run_at' => now()->subDay(),
    ]);

    (new \Modules\PersonalAccounting\Jobs\RecurringTransactionJob())->handle(
        app(\Modules\PersonalAccounting\Actions\ProcessRecurringTransactionAction::class)
    );

    expect(PersonalTransaction::where('recurring_id', $recurring->id)->count())->toBe(1);
    expect($account->fresh()->balance)->toBe('-15000.00');
    expect($recurring->fresh()->next_run_at->gt(now()))->toBeTrue();
});

// --- UI / controller layer tests -------------------------------------------------

test('all personal accounting pages render for an authenticated user', function () {
    $user = paUser();
    $this->actingAs($user);

    foreach (['personal.dashboard', 'personal.transactions.index', 'personal.accounts.index', 'personal.budgets.index', 'personal.goals.index', 'personal.reports.index'] as $route) {
        $this->get(route($route))->assertOk();
    }
});

test('dashboard seeds system categories and a default account on first visit', function () {
    $user = paUser();
    $this->actingAs($user);

    $this->get(route('personal.dashboard'))->assertOk();

    expect(\Modules\PersonalAccounting\Models\PersonalCategory::query()->forTenant((int) $user->tenant_id)->count())->toBeGreaterThan(0);
    expect(\Modules\PersonalAccounting\Models\PersonalAccount::query()->forTenant((int) $user->tenant_id)->where('user_id', $user->id)->count())->toBe(1);
});

test('transaction store endpoint creates a transaction and updates balance', function () {
    $user = paUser();
    $this->actingAs($user);
    $account = paAccount($user);

    $this->post(route('personal.transactions.store'), [
        'type' => 'expense',
        'amount' => 750,
        'account_id' => $account->id,
        'date' => now()->toDateString(),
        'note' => 'Coffee',
    ])->assertRedirect();

    expect(\Modules\PersonalAccounting\Models\PersonalTransaction::count())->toBe(1);
    expect($account->fresh()->balance)->toBe('-750.00');
});

test('account store endpoint creates an account', function () {
    $user = paUser();
    $this->actingAs($user);

    $this->post(route('personal.accounts.store'), [
        'name' => 'bKash',
        'type' => 'mobile_banking',
        'currency' => 'BDT',
        'balance' => 500,
    ])->assertRedirect();

    expect(\Modules\PersonalAccounting\Models\PersonalAccount::where('name', 'bKash')->exists())->toBeTrue();
});

test('goal store and contribute endpoints work', function () {
    $user = paUser();
    $this->actingAs($user);

    $this->post(route('personal.goals.store'), ['name' => 'Trip', 'target_amount' => 20000])->assertRedirect();
    $goal = \Modules\PersonalAccounting\Models\PersonalSavingsGoal::where('name', 'Trip')->firstOrFail();

    $this->post(route('personal.goals.contribute', $goal), ['amount' => 5000])->assertRedirect();

    expect($goal->fresh()->current_amount)->toBe('5000.00');
});

// --- Transfer (two-account) & Loan feature tests -----------------------------

test('transfer moves money between two accounts', function () {
    $user = paUser();
    $this->actingAs($user);
    $from = paAccount($user);
    $to = \Modules\PersonalAccounting\Models\PersonalAccount::create([
        'tenant_id' => $user->tenant_id, 'user_id' => $user->id,
        'name' => 'Savings', 'type' => 'bank', 'currency' => 'BDT', 'balance' => 0,
    ]);
    $from->update(['balance' => 10000]);

    $service = app(\Modules\PersonalAccounting\Services\PersonalTransactionService::class);
    $txn = $service->createTransaction([
        'tenant_id' => $user->tenant_id, 'user_id' => $user->id,
        'account_id' => $from->id, 'to_account_id' => $to->id,
        'type' => 'transfer', 'amount' => 3000, 'date' => now()->toDateString(),
    ]);

    expect($from->fresh()->balance)->toBe('7000.00');
    expect($to->fresh()->balance)->toBe('3000.00');
    expect($txn->to_account_id)->toBe($to->id);
});

test('loan create with lent direction creates an expense transaction', function () {
    $user = paUser();
    $this->actingAs($user);
    $account = paAccount($user);
    $account->update(['balance' => 50000]);

    $contact = \Modules\PersonalAccounting\Models\PersonalContact::create([
        'tenant_id' => $user->tenant_id, 'user_id' => $user->id, 'name' => 'Rahim', 'type' => 'person',
    ]);

    $service = app(\Modules\PersonalAccounting\Services\PersonalLoanService::class);
    $loan = $service->create([
        'tenant_id' => $user->tenant_id, 'user_id' => $user->id,
        'name' => 'Personal loan', 'direction' => 'lent',
        'contact_id' => $contact->id, 'principal_amount' => 20000,
        'interest_rate' => 0, 'start_date' => now()->toDateString(),
        'payment_frequency' => 'monthly', 'payment_amount' => 5000,
        'account_id' => $account->id,
    ]);

    expect($loan->remaining_balance)->toBe('20000.00');
    expect($account->fresh()->balance)->toBe('30000.00');
    expect(\Modules\PersonalAccounting\Models\PersonalTransaction::where('note', 'like', 'Lent to%')->count())->toBe(1);
});

test('loan payment reduces remaining balance and creates income for lent', function () {
    $user = paUser();
    $this->actingAs($user);
    $account = paAccount($user);

    $loan = \Modules\PersonalAccounting\Models\PersonalLoan::create([
        'tenant_id' => $user->tenant_id, 'user_id' => $user->id,
        'name' => 'Lent', 'direction' => 'lent', 'principal_amount' => 10000,
        'remaining_balance' => 10000, 'total_paid' => 0,
        'interest_rate' => 0, 'interest_type' => 'simple', 'status' => 'active',
        'start_date' => now()->toDateString(), 'payment_frequency' => 'monthly', 'payment_amount' => 1000,
        'currency' => 'BDT',
    ]);

    $service = app(\Modules\PersonalAccounting\Services\PersonalLoanService::class);
    $service->recordPayment($loan, 4000, $account->id);

    expect($loan->fresh()->remaining_balance)->toBe('6000.00');
    expect($loan->fresh()->total_paid)->toBe('4000.00');
    expect($account->fresh()->balance)->toBe('4000.00');
    expect(\Modules\PersonalAccounting\Models\PersonalLoanPayment::where('loan_id', $loan->id)->count())->toBe(1);
});

test('loans and contacts pages render', function () {
    $user = paUser();
    $this->actingAs($user);

    $this->get(route('personal.loans.index'))->assertOk();
    $this->get(route('personal.contacts.index'))->assertOk();
});
