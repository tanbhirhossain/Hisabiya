<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CORE\Models\Membership;
use Modules\CORE\Models\SubscriptionPlan;
use Modules\CORE\Services\CheckoutService;
use Modules\CORE\Services\SubscriptionProvisioner;
use Modules\CORE\Services\SubscriptionService;

uses(RefreshDatabase::class);

function checkoutPlan(): SubscriptionPlan
{
    // Ensure the plan + permission records exist.
    $permission = Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'personal-accounting.view', 'guard_name' => 'web']);
    Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'personal-accounting.acl', 'guard_name' => 'web']);

    return SubscriptionPlan::firstOrCreate(
        ['slug' => 'personal-accounting-pro'],
        [
            'module' => 'personal_accounting',
            'name' => 'Personal Accounting Pro',
            'price_monthly' => 799,
            'price_yearly' => 7990,
            'permissions' => ['personal-accounting.view'],
            'feature_flags' => ['acl_management' => true, 'module_users' => true],
            'is_active' => true,
        ],
    );
}

test('checkout service creates user, tenant, and owner membership atomically', function () {
    $plan = checkoutPlan();

    $result = app(CheckoutService::class)->prepare([
        'email' => 'buyer@example.com',
        'password' => 'secret1234',
        'name' => 'Buyer',
        'company_name' => 'Acme Ltd',
    ], $plan);

    expect($result['user']->email)->toBe('buyer@example.com');
    expect($result['tenant']->name)->toBe('Acme Ltd');
    expect($result['membership']->role)->toBe('owner');
    expect($result['membership']->module)->toBe('personal_accounting');
    expect(User::count())->toBe(1);
    expect(Membership::count())->toBe(1);
});

test('checkout service reuses existing tenant on second call', function () {
    $plan = checkoutPlan();
    $checkout = app(CheckoutService::class);

    $first = $checkout->prepare(['email' => 'buyer@example.com', 'password' => 'secret1234', 'company_name' => 'Acme Ltd'], $plan);
    $second = $checkout->prepare(['email' => 'buyer@example.com', 'password' => 'secret1234', 'company_name' => 'Acme Ltd'], $plan);

    expect($second['tenant']->id)->toBe($first['tenant']->id);
    expect(Membership::count())->toBe(1); // no duplicate membership
});

test('different emails create different tenants', function () {
    $plan = checkoutPlan();
    $checkout = app(CheckoutService::class);

    $a = $checkout->prepare(['email' => 'a@example.com', 'password' => 'secret1234', 'company_name' => 'Acme A'], $plan);
    $b = $checkout->prepare(['email' => 'b@example.com', 'password' => 'secret1234', 'company_name' => 'Acme B'], $plan);

    expect($a['tenant']->id)->not->toBe($b['tenant']->id);
    expect(Membership::count())->toBe(2);
});

test('provisioner activates a pending subscription and grants owner acl', function () {
    $plan = checkoutPlan();
    $checkout = app(CheckoutService::class);
    $result = $checkout->prepare(['email' => 'buyer@example.com', 'password' => 'secret1234', 'company_name' => 'Acme Ltd'], $plan);

    $subscription = $checkout->createPendingSubscription($result['tenant'], $plan, 'manual_bkash');

    app(SubscriptionProvisioner::class)->activate($subscription);

    expect($subscription->fresh()->billing_status)->toBe('active');
    expect($result['user']->hasPermissionTo('personal-accounting.view'))->toBeTrue();
    expect($result['user']->hasPermissionTo('personal-accounting.acl'))->toBeTrue();
});

test('active modules router returns the module the user subscribed to', function () {
    $plan = checkoutPlan();
    $checkout = app(CheckoutService::class);
    $result = $checkout->prepare(['email' => 'buyer@example.com', 'password' => 'secret1234', 'company_name' => 'Acme Ltd'], $plan);

    $subscription = $checkout->createPendingSubscription($result['tenant'], $plan, 'sslcommerz');
    app(SubscriptionProvisioner::class)->activate($subscription);

    $modules = app(SubscriptionProvisioner::class)->activeModulesForUser($result['user']->id);
    expect($modules)->toContain('personal_accounting');
});
