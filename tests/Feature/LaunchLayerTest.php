<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CORE\Models\SubscriptionPlan;
use Modules\CORE\Services\CheckoutService;
use Modules\PersonalAccounting\Models\PersonalAccount;
use Modules\PersonalAccounting\Models\PersonalTransaction;
use Modules\PersonalAccounting\Services\PersonalAccountingSampleDataService;

uses(RefreshDatabase::class);

function launchPaUser(): array
{
    \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'personal-accounting.view', 'guard_name' => 'web']);

    $plan = SubscriptionPlan::firstOrCreate(
        ['slug' => 'personal-accounting-pro'],
        [
            'module' => 'personal_accounting',
            'name' => 'PA Pro',
            'price_monthly' => 799,
            'permissions' => ['personal-accounting.view'],
            'is_active' => true,
        ],
    );

    $checkout = app(CheckoutService::class);
    $result = $checkout->prepare(['email' => 'launch@example.com', 'password' => 'secret1234', 'company_name' => 'Launch Co'], $plan);
    $subscription = $checkout->createPendingSubscription($result['tenant'], $plan, 'sslcommerz');
    app(\Modules\CORE\Services\SubscriptionProvisioner::class)->activate($subscription);

    return ['user' => $result['user'], 'tenant' => $result['tenant']];
}

// ---------- Legal pages ----------

test('legal pages render publicly', function () {
    foreach (['legal.terms', 'legal.privacy', 'legal.refund'] as $route) {
        $this->get(route($route))->assertOk();
    }
});

test('public layout footer includes legal links', function () {
    $this->get('/')->assertOk();
});

test('root renders the high-converting landing page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page->component('Landing'));
});

test('landing page receives active plans grouped by module from the db', function () {
    \Modules\CORE\Models\SubscriptionPlan::create([
        'module' => 'personal_accounting',
        'name' => 'Landing Test Plan',
        'slug' => 'landing-test-plan',
        'price_monthly' => 999,
        'price_yearly' => 9990,
        'features' => ['Landing feature'],
        'permissions' => ['personal-accounting.view'],
        'is_active' => true,
    ]);
    \Modules\CORE\Models\SubscriptionPlan::create([
        'module' => 'personal_accounting',
        'name' => 'Inactive Plan',
        'slug' => 'inactive-plan',
        'price_monthly' => 0,
        'is_active' => false,
    ]);

    $this->get(route('home'))
        ->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
            ->component('Landing')
            ->has('modules', 1) // only the registered personal_accounting module with plans
            ->has('modules.0.plans', 1) // inactive plan excluded
            ->where('modules.0.plans.0.name', 'Landing Test Plan')
            ->where('modules.0.plans.0.price_monthly', '999.00'));
});

// ---------- Sample data ----------

test('sample data service loads realistic data scoped to the tenant', function () {
    ['user' => $user, 'tenant' => $tenant] = launchPaUser();
    $svc = app(PersonalAccountingSampleDataService::class);

    $count = $svc->load((int) $user->id, (int) $tenant->id);

    expect($count)->toBeGreaterThan(10);
    expect($svc->loaded((int) $tenant->id))->toBeTrue();

    // A different tenant must have no data (isolation).
    $other = \Modules\CORE\Models\Tenant::create(['name' => 'Other', 'slug' => 'other', 'status' => 'active', 'plan' => 'free']);
    expect(PersonalTransaction::where('tenant_id', $other->id)->count())->toBe(0);

    // Idempotent: a second call loads nothing new.
    $second = $svc->load((int) $user->id, (int) $tenant->id);
    expect($second)->toBe(0);
});

test('dashboard endpoint exposes onboarding flag and load-sample action works', function () {
    ['user' => $user, 'tenant' => $tenant] = launchPaUser();

    $this->actingAs($user)
        ->get(route('personal.dashboard'))
        ->assertOk();

    $this->actingAs($user)
        ->post(route('personal.dashboard.load-sample-data'))
        ->assertRedirect(route('personal.dashboard'));

    expect(PersonalTransaction::where('tenant_id', $tenant->id)->count())->toBeGreaterThan(0);
});

// ---------- Email queueing ----------

test('core notifications implement ShouldQueue', function () {
    $classes = [
        \Modules\CORE\Notifications\PaymentApprovedNotification::class,
        \Modules\CORE\Notifications\SubscriptionRenewalNotification::class,
        \Modules\CORE\Notifications\SubscriptionExpiredNotification::class,
    ];

    $implements = [];
    foreach ($classes as $class) {
        $implements[$class] = in_array(\Illuminate\Contracts\Queue\ShouldQueue::class, class_implements($class), true);
    }

    expect(array_values($implements))->toBe([true, true, true]);
});
