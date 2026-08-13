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
        'personal-accounting.transactions.import',
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

// --- Notifications system tests ----------------------------------------------

test('budget exceeded notification is stored via database channel', function () {
    $user = paUser();
    $this->actingAs($user);

    $user->notify(new \Modules\PersonalAccounting\Notifications\BudgetExceededNotification('Food', 1200, 1000));

    $this->assertDatabaseCount('notifications', 1);
    $notification = \Illuminate\Notifications\DatabaseNotification::first();
    expect($notification->data['category_name'])->toBe('Food');
    expect((float) $notification->data['spent'])->toBe(1200.0);
    expect((float) $notification->data['limit'])->toBe(1000.0);
});

test('notifications index page renders for authenticated user', function () {
    $user = paUser();
    $this->actingAs($user);
    $user->notify(new \Modules\PersonalAccounting\Notifications\SavingsGoalReachedNotification('Emergency', 50000));

    $this->get(route('personal.notifications.index'))->assertOk();
});

test('mark notification as read and read-all endpoints work', function () {
    $user = paUser();
    $this->actingAs($user);
    $user->notify(new \Modules\PersonalAccounting\Notifications\BudgetWarningNotification('Food', 800, 1000, 70));

    $id = \Illuminate\Notifications\DatabaseNotification::first()->id;

    $this->postJson(route('personal.notifications.read', $id))->assertOk();
    $this->assertNotNull(\Illuminate\Notifications\DatabaseNotification::first()->read_at);

    $user->notify(new \Modules\PersonalAccounting\Notifications\BudgetWarningNotification('Rent', 500, 1000, 70));
    $this->postJson(route('personal.notifications.read-all'))->assertOk();
    $this->assertCount(0, \Illuminate\Notifications\DatabaseNotification::whereNull('read_at')->get());
});

test('savings goal contribution dispatches milestone notification', function () {
    $user = paUser();
    $this->actingAs($user);
    $goal = \Modules\PersonalAccounting\Models\PersonalSavingsGoal::create([
        'tenant_id' => $user->tenant_id, 'user_id' => $user->id,
        'name' => 'Trip', 'target_amount' => 10000, 'current_amount' => 0,
    ]);

    app(\Modules\PersonalAccounting\Services\PersonalSavingsGoalService::class)->contribute($goal, 3000);

    // 3000 / 10000 = 30% -> crosses the 25% milestone.
    expect(\Illuminate\Notifications\DatabaseNotification::where('type', \Modules\PersonalAccounting\Notifications\SavingsGoalMilestoneNotification::class)->count())->toBe(1);
});

// --- CSV Bank Statement Import tests -----------------------------------------

test('import service parses CSV and detects columns', function () {
    $user = paUser();
    $this->actingAs($user);

    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('statement.csv', implode("\n", [
        'Date,Description,Amount,Type',
        '2026-08-01,Grocery,-500,debit',
        '2026-08-02,Salary,+30000,credit',
    ]));

    $service = app(\Modules\PersonalAccounting\Services\TransactionImportService::class);
    $rows = $service->parseCSV($file);

    expect($rows)->toHaveCount(2);
    expect($rows[0]['date'])->toBe('2026-08-01');
    expect($rows[0]['description'])->toBe('Grocery');

    $map = $service->detectColumns($rows);
    expect($map['date'])->toBe('date');
    expect($map['amount'])->toBe('amount');
    expect($map['description'])->toBe('description');
});

test('import endpoint creates transactions and updates balance', function () {
    $user = paUser();
    $this->actingAs($user);
    $account = paAccount($user);

    $service = app(\Modules\PersonalAccounting\Services\TransactionImportService::class);
    $result = $service->import([
        ['date' => '2026-08-01', 'amount' => '-500', 'description' => 'Groceries', 'type' => 'debit'],
        ['date' => '2026-08-02', 'amount' => '+2000', 'description' => 'Freelance', 'type' => 'credit'],
    ], ['date' => 'date', 'amount' => 'amount', 'description' => 'description', 'type' => 'type'],
        (int) $account->id, (int) $user->id, (int) $user->tenant_id);

    expect($result['imported'])->toBe(2);
    expect($result['failed'])->toBe(0);
    expect($account->fresh()->balance)->toBe('1500.00'); // -500 + 2000
    expect(\Modules\PersonalAccounting\Models\PersonalTransaction::count())->toBe(2);
});

test('import page renders for a user with import permission', function () {
    $user = paUser();
    $this->actingAs($user);

    $this->get(route('personal.transactions.import'))->assertOk();
});

// --- Account improvements (archive, balance history, default reassignment) ----

test('account can be archived', function () {
    $user = paUser();
    $this->actingAs($user);
    $a1 = paAccount($user);
    $a2 = \Modules\PersonalAccounting\Models\PersonalAccount::create([
        'tenant_id' => $user->tenant_id, 'user_id' => $user->id,
        'name' => 'Savings', 'type' => 'bank', 'currency' => 'BDT', 'balance' => 0,
    ]);

    $service = app(\Modules\PersonalAccounting\Services\PersonalAccountService::class);
    $service->archive($a1);

    expect($a1->fresh()->is_archived)->toBeTrue();
    expect(PersonalAccount::query()->where('user_id', $user->id)->active()->count())->toBe(1);
});

test('last active account cannot be archived', function () {
    $user = paUser();
    $this->actingAs($user);
    $a1 = paAccount($user);

    $service = app(\Modules\PersonalAccounting\Services\PersonalAccountService::class);

    $this->expectException(\Illuminate\Validation\ValidationException::class);
    $service->archive($a1);
});

test('deleting default account reassigns default to next active account', function () {
    $user = paUser();
    $this->actingAs($user);
    $a1 = paAccount($user); // default
    $a2 = \Modules\PersonalAccounting\Models\PersonalAccount::create([
        'tenant_id' => $user->tenant_id, 'user_id' => $user->id,
        'name' => 'Savings', 'type' => 'bank', 'currency' => 'BDT', 'balance' => 0,
    ]);

    $a1->delete();

    expect($a2->fresh()->is_default)->toBeTrue();
});

test('balance history endpoint returns running balance series', function () {
    $user = paUser();
    $this->actingAs($user);
    $account = paAccount($user);

    $service = app(\Modules\PersonalAccounting\Services\PersonalTransactionService::class);
    $service->createTransaction([
        'tenant_id' => $user->tenant_id, 'user_id' => $user->id,
        'account_id' => $account->id, 'type' => 'income', 'amount' => 1000, 'date' => now()->toDateString(),
    ]);
    $service->createTransaction([
        'tenant_id' => $user->tenant_id, 'user_id' => $user->id,
        'account_id' => $account->id, 'type' => 'expense', 'amount' => 300, 'date' => now()->toDateString(),
    ]);

    $history = app(\Modules\PersonalAccounting\Services\PersonalAccountService::class)->getBalanceHistory($account);
    expect(count($history))->toBeGreaterThan(0);
    // Last point equals current balance (1000 - 300 = 700).
    expect((float) end($history)['balance'])->toBe(700.0);
});

// --- Transaction improvements (status, duplicate detection, bulk update) -----

test('transaction can be created as pending and filtered', function () {
    $user = paUser();
    $this->actingAs($user);
    $account = paAccount($user);

    $this->post(route('personal.transactions.store'), [
        'type' => 'expense', 'amount' => 100, 'account_id' => $account->id,
        'date' => now()->toDateString(), 'status' => 'pending',
    ])->assertRedirect();

    expect(PersonalTransaction::where('status', 'pending')->count())->toBe(1);
    expect(PersonalTransaction::pending()->count())->toBe(1);
});

test('duplicate detection finds a matching transaction', function () {
    $user = paUser();
    $this->actingAs($user);
    $account = paAccount($user);

    $service = app(\Modules\PersonalAccounting\Services\PersonalTransactionService::class);
    $service->createTransaction([
        'tenant_id' => $user->tenant_id, 'user_id' => $user->id, 'account_id' => $account->id,
        'type' => 'expense', 'amount' => 500, 'date' => now()->toDateString(),
    ]);

    $duplicate = $service->detectDuplicate((int) $account->id, 500, now()->toDateString(), (int) $user->tenant_id);
    expect($duplicate)->not->toBeNull();

    $none = $service->detectDuplicate((int) $account->id, 999, now()->toDateString(), (int) $user->tenant_id);
    expect($none)->toBeNull();
});

test('bulk update changes status for owned transactions', function () {
    $user = paUser();
    $this->actingAs($user);
    $account = paAccount($user);
    $t1 = PersonalTransaction::create([
        'tenant_id' => $user->tenant_id, 'user_id' => $user->id, 'account_id' => $account->id,
        'type' => 'expense', 'amount' => 50, 'date' => now()->toDateString(), 'status' => 'pending',
    ]);

    $count = app(\Modules\PersonalAccounting\Services\PersonalTransactionService::class)
        ->bulkUpdate([$t1->id], 'status', 'cleared', (int) $user->tenant_id, (int) $user->id);

    expect($count)->toBe(1);
    expect($t1->fresh()->status)->toBe('cleared');
});

// --- Budget improvements (rollover, forecast, notify threshold) --------------

test('budget progress uses effective limit including rollover', function () {
    $user = paUser();
    $this->actingAs($user);
    $category = paCategory($user, 'expense', 'Food');
    $budget = PersonalBudget::create([
        'tenant_id' => $user->tenant_id, 'user_id' => $user->id, 'category_id' => $category->id,
        'amount' => 1000, 'period' => 'monthly', 'start_date' => now()->startOfMonth()->toDateString(),
        'rollover_enabled' => true, 'rollover_amount' => 500, 'notify_at_percent' => 85,
    ]);

    $progress = app(\Modules\PersonalAccounting\Services\PersonalBudgetService::class)->getBudgetProgress($budget);
    expect((float) $progress['effective_limit'])->toBe(1500.0);
    expect($progress['notify_at_percent'])->toBe(85);
});

test('calculate rollover stores unused amount from previous period', function () {
    $user = paUser();
    $this->actingAs($user);
    $account = paAccount($user);
    $category = paCategory($user, 'expense', 'Food');

    $budget = PersonalBudget::create([
        'tenant_id' => $user->tenant_id, 'user_id' => $user->id, 'category_id' => $category->id,
        'amount' => 1000, 'period' => 'monthly', 'start_date' => now()->startOfMonth()->toDateString(),
        'rollover_enabled' => true, 'rollover_amount' => 0,
    ]);

    // Spend 300 last month -> 700 unused should roll over.
    app(\Modules\PersonalAccounting\Services\PersonalTransactionService::class)->createTransaction([
        'tenant_id' => $user->tenant_id, 'user_id' => $user->id, 'account_id' => $account->id,
        'category_id' => $category->id, 'type' => 'expense', 'amount' => 300,
        'date' => now()->subMonth()->toDateString(),
    ]);

    $rollover = app(\Modules\PersonalAccounting\Services\PersonalBudgetService::class)->calculateRollover($budget);
    expect((float) $rollover)->toBe(700.0);
    expect((float) $budget->fresh()->rollover_amount)->toBe(700.0);
});

test('spending forecast reports projected spend and overage', function () {
    $user = paUser();
    $this->actingAs($user);
    $account = paAccount($user);
    $category = paCategory($user, 'expense', 'Food');

    $budget = PersonalBudget::create([
        'tenant_id' => $user->tenant_id, 'user_id' => $user->id, 'category_id' => $category->id,
        'amount' => 1000, 'period' => 'monthly', 'start_date' => now()->startOfMonth()->toDateString(),
    ]);

    // Spend 900 in the first few days -> high daily rate -> will exceed.
    app(\Modules\PersonalAccounting\Services\PersonalTransactionService::class)->createTransaction([
        'tenant_id' => $user->tenant_id, 'user_id' => $user->id, 'account_id' => $account->id,
        'category_id' => $category->id, 'type' => 'expense', 'amount' => 900,
        'date' => now()->toDateString(),
    ]);

    $forecast = app(\Modules\PersonalAccounting\Services\PersonalBudgetService::class)->getSpendingForecast($budget);
    expect($forecast['spent_so_far'])->toBe(900.0);
    expect($forecast['days_in_period'])->toBeGreaterThan(0);
    expect(is_array($forecast))->toBeTrue();
});

// --- Savings goals linked to real accounts ----------------------------------

test('contribute to a linked-account goal creates an expense transaction', function () {
    $user = paUser();
    $this->actingAs($user);
    $account = paAccount($user);
    $account->update(['balance' => 10000]);

    $goal = \Modules\PersonalAccounting\Models\PersonalSavingsGoal::create([
        'tenant_id' => $user->tenant_id, 'user_id' => $user->id, 'name' => 'Vacation',
        'target_amount' => 50000, 'current_amount' => 0, 'account_id' => $account->id,
    ]);

    app(\Modules\PersonalAccounting\Services\PersonalSavingsGoalService::class)->contribute($goal, 4000);

    expect($goal->fresh()->current_amount)->toBe('4000.00');
    expect($account->fresh()->balance)->toBe('6000.00'); // 10000 - 4000
    expect(\Modules\PersonalAccounting\Models\PersonalTransaction::where('note', 'Savings goal: Vacation')->where('type', 'expense')->count())->toBe(1);
});

test('withdraw from a linked-account goal creates an income transaction', function () {
    $user = paUser();
    $this->actingAs($user);
    $account = paAccount($user);
    $account->update(['balance' => 6000]);

    $goal = \Modules\PersonalAccounting\Models\PersonalSavingsGoal::create([
        'tenant_id' => $user->tenant_id, 'user_id' => $user->id, 'name' => 'Vacation',
        'target_amount' => 50000, 'current_amount' => 4000, 'account_id' => $account->id,
    ]);

    app(\Modules\PersonalAccounting\Services\PersonalSavingsGoalService::class)->withdraw($goal, 1000);

    expect($account->fresh()->balance)->toBe('7000.00'); // 6000 + 1000
    expect(\Modules\PersonalAccounting\Models\PersonalTransaction::where('note', 'Savings goal: Vacation')->where('type', 'income')->count())->toBe(1);
});

test('contribution history returns the audit trail for a goal', function () {
    $user = paUser();
    $this->actingAs($user);
    $account = paAccount($user);
    $goal = \Modules\PersonalAccounting\Models\PersonalSavingsGoal::create([
        'tenant_id' => $user->tenant_id, 'user_id' => $user->id, 'name' => 'Trip',
        'target_amount' => 20000, 'current_amount' => 0, 'account_id' => $account->id,
    ]);

    $svc = app(\Modules\PersonalAccounting\Services\PersonalSavingsGoalService::class);
    $svc->contribute($goal, 500);
    $svc->contribute($goal, 1500);

    $history = $svc->getContributionHistory($goal);
    expect($history->count())->toBe(2);
    expect((float) $history->first()['amount'])->toBe(1500.0);
});

// --- Loan improvements (penalty, statement) ----------------------------------

test('late payment applies penalty rate to the loan', function () {
    $user = paUser();
    $this->actingAs($user);
    $account = paAccount($user);

    $loan = \Modules\PersonalAccounting\Models\PersonalLoan::create([
        'tenant_id' => $user->tenant_id, 'user_id' => $user->id, 'name' => 'Penalty Loan', 'direction' => 'borrowed',
        'principal_amount' => 10000, 'remaining_balance' => 10000, 'total_paid' => 0,
        'interest_rate' => 0, 'penalty_rate' => 5, 'interest_type' => 'simple', 'status' => 'active',
        'start_date' => now()->subMonth()->toDateString(), 'payment_frequency' => 'monthly',
        'payment_amount' => 1000, 'currency' => 'BDT', 'next_payment_date' => now()->subDays(5)->toDateString(),
    ]);

    $svc = app(\Modules\PersonalAccounting\Services\PersonalLoanService::class);
    $payment = $svc->recordPayment($loan, 2000, (int) $account->id, null, now()->toDateString());

    expect((float) $payment->penalty_amount)->toBe(500.0); // 5% of 10000
    expect((float) $payment->principal_part)->toBe(1500.0);
    expect($loan->fresh()->remaining_balance)->toBe('8500.00');
});

test('loan statement returns structured data', function () {
    $user = paUser();
    $this->actingAs($user);
    $loan = \Modules\PersonalAccounting\Models\PersonalLoan::create([
        'tenant_id' => $user->tenant_id, 'user_id' => $user->id, 'name' => 'Stmt Loan', 'direction' => 'lent',
        'principal_amount' => 5000, 'remaining_balance' => 5000, 'total_paid' => 0,
        'interest_rate' => 0, 'penalty_rate' => 0, 'interest_type' => 'simple', 'status' => 'active',
        'start_date' => now()->toDateString(), 'payment_frequency' => 'monthly', 'payment_amount' => 500, 'currency' => 'BDT',
    ]);

    $svc = app(\Modules\PersonalAccounting\Services\PersonalLoanService::class);
    $svc->recordPayment($loan, 500, null, 'First payment');
    $statement = $svc->generateStatement($loan);

    expect($statement['loan']['name'])->toBe('Stmt Loan');
    expect(count($statement['payments']))->toBe(1);
    expect($statement['payments'][0]['note'])->toBe('First payment');
    expect((float) $statement['loan']['remaining_balance'])->toBe(4500.0);
});

// --- Reports improvements (YoY, top spending, cash flow, email settings) ------

test('year over year comparison returns month data for two years', function () {
    $user = paUser();
    $this->actingAs($user);

    $action = app(\Modules\PersonalAccounting\Actions\GeneratePersonalReportAction::class);
    $data = $action->yearOverYearComparison((int) $user->id, (int) $user->tenant_id);

    expect($data['years'])->toHaveCount(2);
    expect($data['months'])->toHaveCount(12);
    expect($data['months'][0]['month'])->toBeString();
    expect(array_key_exists('current_income', $data['months'][0]))->toBeTrue();
});

test('top spending categories returns sorted with percent', function () {
    $user = paUser();
    $this->actingAs($user);
    $account = paAccount($user);
    $food = paCategory($user, 'expense', 'Food');
    $rent = paCategory($user, 'expense', 'Rent');

    app(\Modules\PersonalAccounting\Services\PersonalTransactionService::class)->createTransaction([
        'tenant_id' => $user->tenant_id, 'user_id' => $user->id, 'account_id' => $account->id,
        'category_id' => $food->id, 'type' => 'expense', 'amount' => 600, 'date' => now()->toDateString(),
    ]);
    app(\Modules\PersonalAccounting\Services\PersonalTransactionService::class)->createTransaction([
        'tenant_id' => $user->tenant_id, 'user_id' => $user->id, 'account_id' => $account->id,
        'category_id' => $rent->id, 'type' => 'expense', 'amount' => 400, 'date' => now()->toDateString(),
    ]);

    $top = app(\Modules\PersonalAccounting\Actions\GeneratePersonalReportAction::class)
        ->topSpendingCategories((int) $user->id, (int) $user->tenant_id, now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString());

    expect($top[0]['category'])->toBe('Food');
    expect((float) $top[0]['total'])->toBe(600.0);
    expect((float) $top[0]['percent'])->toBe(60.0);
});

test('cash flow summary structures inflows and outflows', function () {
    $user = paUser();
    $this->actingAs($user);

    $flow = app(\Modules\PersonalAccounting\Actions\GeneratePersonalReportAction::class)
        ->cashFlowSummary((int) $user->id, (int) $user->tenant_id, now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString());

    expect(array_key_exists('total_inflows', $flow))->toBeTrue();
    expect(array_key_exists('total_outflows', $flow))->toBeTrue();
    expect(array_key_exists('net_cash_flow', $flow))->toBeTrue();
});

test('report email settings can be updated', function () {
    $user = paUser();
    $this->actingAs($user);

    $this->post(route('personal.reports.email-settings'), [
        'personal_report_email_enabled' => true,
        'personal_report_email_day' => 15,
    ])->assertRedirect();

    expect($user->fresh()->personal_report_email_enabled)->toBeTrue();
    expect((int) $user->fresh()->personal_report_email_day)->toBe(15);
});

// --- Recurring transactions: end conditions + logs ---------------------------

test('recurring template auto-deactivates after max occurrences', function () {
    $user = paUser();
    $this->actingAs($user);
    $account = paAccount($user);

    $recurring = \Modules\PersonalAccounting\Models\PersonalRecurringTransaction::create([
        'tenant_id' => $user->tenant_id, 'user_id' => $user->id, 'name' => 'Gym', 'account_id' => $account->id,
        'type' => 'expense', 'amount' => 500, 'frequency' => 'monthly', 'end_type' => 'after_occurrences',
        'max_occurrences' => 2, 'occurrences_count' => 0, 'next_run_at' => now()->subDay(), 'is_active' => true,
    ]);

    $action = app(\Modules\PersonalAccounting\Actions\ProcessRecurringTransactionAction::class);
    $action->handle($recurring->fresh());
    $action->handle($recurring->fresh());

    expect((int) $recurring->fresh()->occurrences_count)->toBe(2);
    expect($recurring->fresh()->is_active)->toBeFalse();
    expect(\Modules\PersonalAccounting\Models\PersonalRecurringLog::where('recurring_id', $recurring->id)->count())->toBe(2);
});

test('recurring template deactivates when on_date passes', function () {
    $user = paUser();
    $this->actingAs($user);
    $account = paAccount($user);

    $recurring = \Modules\PersonalAccounting\Models\PersonalRecurringTransaction::create([
        'tenant_id' => $user->tenant_id, 'user_id' => $user->id, 'name' => 'Rent', 'account_id' => $account->id,
        'type' => 'expense', 'amount' => 1000, 'frequency' => 'monthly', 'end_type' => 'on_date',
        'end_date' => now()->subDay()->toDateString(), 'occurrences_count' => 0, 'next_run_at' => now()->subDay(), 'is_active' => true,
    ]);

    $action = app(\Modules\PersonalAccounting\Actions\ProcessRecurringTransactionAction::class);
    $action->handle($recurring->fresh());

    expect($recurring->fresh()->is_active)->toBeFalse();
    expect(\Modules\PersonalAccounting\Models\PersonalRecurringLog::where('recurring_id', $recurring->id)->count())->toBe(0);
});

test('recurring page renders', function () {
    $user = paUser();
    $this->actingAs($user);
    $this->get(route('personal.recurring.index'))->assertOk();
});

// --- Dashboard improvements (period, net worth, velocity, upcoming) -----------

test('dashboard passes net worth, spending velocity and upcoming recurring', function () {
    $user = paUser();
    $this->actingAs($user);
    $account = paAccount($user);

    \Modules\PersonalAccounting\Models\PersonalRecurringTransaction::create([
        'tenant_id' => $user->tenant_id, 'user_id' => $user->id, 'name' => 'Rent', 'account_id' => $account->id,
        'type' => 'expense', 'amount' => 1000, 'frequency' => 'monthly', 'next_run_at' => now()->addDays(2),
        'is_active' => true, 'occurrences_count' => 0,
    ]);

    $response = $this->get(route('personal.dashboard', ['period' => 'month']))->assertOk();

    $response->assertInertia(function (\Inertia\Testing\AssertableInertia $page) {
        $page->component('PersonalAccounting::Dashboard/Index')
            ->has('net_worth')
            ->has('spending_velocity')
            ->has('upcoming_recurring');
    });
});

test('dashboard period query changes the reported range', function () {
    $user = paUser();
    $this->actingAs($user);

    $this->get(route('personal.dashboard', ['period' => 'today']))->assertOk();
    $this->get(route('personal.dashboard', ['period' => 'week']))->assertOk();
    $this->get(route('personal.dashboard', ['period' => 'custom', 'from' => now()->startOfMonth()->toDateString(), 'to' => now()->endOfMonth()->toDateString()]))->assertOk();
});

// --- Budget notification integration (alertOverBudget fires on new expense) ---

test('creating an expense dispatches budget exceeded notification', function () {
    $user = paUser();
    $this->actingAs($user);
    $account = paAccount($user);
    $category = paCategory($user, 'expense', 'Food');

    // Budget of 1000, spend 1500 -> over budget.
    PersonalBudget::create([
        'tenant_id' => $user->tenant_id, 'user_id' => $user->id, 'category_id' => $category->id,
        'amount' => 1000, 'period' => 'monthly', 'start_date' => now()->startOfMonth()->toDateString(),
    ]);

    $this->post(route('personal.transactions.store'), [
        'type' => 'expense', 'amount' => 1500, 'account_id' => $account->id,
        'category_id' => $category->id, 'date' => now()->toDateString(),
    ])->assertRedirect();

    expect(\Illuminate\Notifications\DatabaseNotification::where('type', \Modules\PersonalAccounting\Notifications\BudgetExceededNotification::class)->count())->toBe(1);
});
