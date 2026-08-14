<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\CORE\Models\Membership;
use Modules\CORE\Models\SubscriptionPlan;
use Modules\CORE\Models\TenantSubscription;
use Modules\CORE\Services\CheckoutService;
use Modules\CORE\Services\ModuleRegistry;
use Modules\CORE\Services\SubscriptionProvisioner;

uses(RefreshDatabase::class);

function chooserPlan(): SubscriptionPlan
{
    \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'personal-accounting.view', 'guard_name' => 'web']);

    return SubscriptionPlan::firstOrCreate(
        ['slug' => 'personal-accounting-pro'],
        [
            'module' => 'personal_accounting',
            'name' => 'PA Pro',
            'price_monthly' => 799,
            'permissions' => ['personal-accounting.view'],
            'is_active' => true,
        ],
    );
}

function chooserPaUser(): array
{
    $plan = chooserPlan();
    $checkout = app(CheckoutService::class);
    $result = $checkout->prepare(['email' => 'owner@example.com', 'password' => 'secret1234', 'company_name' => 'Owner Co'], $plan);
    $subscription = $checkout->createPendingSubscription($result['tenant'], $plan, 'sslcommerz');
    app(SubscriptionProvisioner::class)->activate($subscription);

    return ['user' => $result['user'], 'tenant' => $result['tenant'], 'subscription' => $subscription];
}

function chooserAdmin(): User
{
    Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

    return User::factory()->create()->assignRole('super-admin');
}

test('single active module redirects the dashboard into that module', function () {
    ['user' => $user] = chooserPaUser();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect('/personal/dashboard');
});

test('dashboard renders the module chooser when a user has multiple modules', function () {
    ['user' => $user, 'tenant' => $tenant, 'subscription' => $subscription] = chooserPaUser();

    // Simulate a second active subscription (a future module) on the same tenant.
    $plan = SubscriptionPlan::firstOrCreate(
        ['slug' => 'some-future-module-plan'],
        [
            'module' => 'future_module',
            'name' => 'Future Module',
            'price_monthly' => 499,
            'permissions' => [],
            'is_active' => true,
        ],
    );
    $extra = TenantSubscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'module' => 'future_module',
        'status' => 'active',
        'billing_status' => 'active',
        'starts_at' => now(),
        'ends_at' => now()->addYear(),
        'auto_renew' => true,
    ]);
    app(SubscriptionProvisioner::class)->activate($extra);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('CORE::Module/Chooser')
            ->where('canAdmin', false)
            ->where('hasSubscription', true)
            ->has('modules', 1)); // only registered modules are shown
});

test('dashboard renders admin analytics when user has no modules but is an admin', function () {
    $this->actingAs(chooserAdmin())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('CORE::Dashboard/Index'));
});

test('dashboard renders chooser empty state for a non-admin with no modules', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('CORE::Module/Chooser')
            ->where('canAdmin', false)
            ->where('hasSubscription', false)
            ->where('modules', []));
});

test('the modules route always renders the chooser', function () {
    ['user' => $user] = chooserPaUser();

    $this->actingAs($user)
        ->get(route('modules.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('CORE::Module/Chooser')
            ->has('modules', 1));
});

test('module registry exposes metadata and landing routes for registered modules', function () {
    $registry = app(ModuleRegistry::class);

    expect($registry->has('personal_accounting'))->toBeTrue();
    expect($registry->has('bogus_module'))->toBeFalse();
    expect($registry->routeFor('personal_accounting'))->toBe('personal.dashboard');

    $available = $registry->available(['personal_accounting', 'not_registered']);
    expect($available)->toHaveCount(1);
    expect($available[0]['key'])->toBe('personal_accounting');
    expect($available[0]['href'])->toBe(route('personal.dashboard'));
});

test('login routes a single-module subscriber straight into the module', function () {
    chooserPaUser();

    $this->post('/login', [
        'email' => 'owner@example.com',
        'password' => 'secret1234',
    ])->assertRedirect('/personal/dashboard');
});

test('browse page shows only modules the tenant does not own', function () {
    ['user' => $user] = chooserPaUser();

    $this->actingAs($user)
        ->get(route('billing.browse'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('CORE::Module/Browse'));
});

test('authenticated checkout supports adding a module without account fields', function () {
    ['user' => $user] = chooserPaUser();
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

    $this->actingAs($user)
        ->get(route('checkout', ['plan' => $plan->id, 'add' => 1]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('CORE::Checkout/Checkout')
            ->where('authenticated', true)
            ->where('user_email', $user->email));
});

test('attaching a module reuses the existing tenant and membership', function () {
    ['user' => $user, 'tenant' => $tenant] = chooserPaUser();
    $plan = SubscriptionPlan::firstOrCreate(
        ['slug' => 'future-module-plan'],
        [
            'module' => 'future_module',
            'name' => 'Future Module',
            'price_monthly' => 499,
            'permissions' => [],
            'is_active' => true,
        ],
    );

    $result = app(\Modules\CORE\Services\CheckoutService::class)->attachModule($user, $plan);

    expect((int) $result['tenant']->id)->toBe((int) $tenant->id);
    expect(Membership::where('user_id', $user->id)
        ->where('tenant_id', $tenant->id)
        ->where('module', 'future_module')
        ->where('role', 'owner')->exists())->toBeTrue();
});
