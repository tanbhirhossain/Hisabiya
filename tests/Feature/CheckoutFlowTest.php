<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CORE\Models\Membership;
use Modules\CORE\Models\Payment;
use Modules\CORE\Models\SubscriptionPlan;
use Modules\CORE\Models\TenantSubscription;
use Modules\CORE\Services\CheckoutService;
use Modules\CORE\Services\SubscriptionActivationService;

uses(RefreshDatabase::class);

function checkoutFreePlan(): SubscriptionPlan
{
    \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'personal-accounting.view', 'guard_name' => 'web']);

    return SubscriptionPlan::firstOrCreate(
        ['slug' => 'personal-accounting-free'],
        [
            'module' => 'personal_accounting',
            'name' => 'Personal Accounting Free',
            'price_monthly' => 0,
            'price_yearly' => 0,
            'permissions' => ['personal-accounting.view'],
            'feature_flags' => ['module_users' => false],
            'is_active' => true,
        ],
    );
}

test('pricing page renders for visitors', function () {
    $this->get(route('pricing'))->assertOk();
});

test('checkout page renders for a plan', function () {
    $plan = checkoutFreePlan();
    $this->get(route('checkout', $plan->id))->assertOk();
});

test('free plan activates instantly by default', function () {
    \Modules\CORE\Models\CoreSetting::set('free_plan_activation', 'instant');
    $plan = checkoutFreePlan();

    $this->post(route('checkout.process'), [
        'plan_id' => $plan->id,
        'email' => 'free@example.com',
        'password' => 'secret1234',
        'name' => 'Free User',
        'company_name' => 'Free Co',
        'provider' => 'sslcommerz',
    ])->assertRedirect('/personal/dashboard');

    expect(Membership::where('module', 'personal_accounting')->where('role', 'owner')->count())->toBe(1);
    $sub = TenantSubscription::where('module', 'personal_accounting')->first();
    expect($sub->billing_status)->toBe('active');
});

test('free plan approval mode keeps subscription pending', function () {
    \Modules\CORE\Models\CoreSetting::set('free_plan_activation', 'approval');
    $plan = checkoutFreePlan();

    $this->post(route('checkout.process'), [
        'plan_id' => $plan->id,
        'email' => 'free2@example.com',
        'password' => 'secret1234',
        'name' => 'Free User 2',
        'provider' => 'sslcommerz',
    ])->assertRedirectContains('/checkout/simulate/');

    $sub = TenantSubscription::where('module', 'personal_accounting')->latest()->first();
    expect($sub->billing_status)->toBe('pending');
});

test('paid plan stays pending until callback confirms payment', function () {
    \Modules\CORE\Models\CoreSetting::set('free_plan_activation', 'instant');
    $plan = SubscriptionPlan::firstOrCreate(
        ['slug' => 'personal-accounting-pro'],
        [
            'module' => 'personal_accounting',
            'name' => 'Personal Accounting Pro',
            'price_monthly' => 799,
            'price_yearly' => 7990,
            'permissions' => ['personal-accounting.view', 'personal-accounting.acl'],
            'is_active' => true,
        ],
    );

    $this->post(route('checkout.process'), [
        'plan_id' => $plan->id,
        'email' => 'pro@example.com',
        'password' => 'secret1234',
        'name' => 'Pro User',
        'provider' => 'sslcommerz',
    ])->assertRedirectContains('/checkout/simulate/');

    $sub = TenantSubscription::where('module', 'personal_accounting')->latest()->first();
    expect($sub->billing_status)->toBe('pending');

    // Simulate the dev callback (SSLCommerz provider returns 'paid' in dev).
    $payment = Payment::where('subscription_id', $sub->id)->first();
    $this->get(route('checkout.callback', ['provider' => 'sslcommerz', 'tran_id' => $payment->provider_ref]))
        ->assertRedirect('/personal/dashboard');

    expect($sub->fresh()->billing_status)->toBe('active');
});

test('manual payment records proof and stays pending', function () {
    \Modules\CORE\Models\CoreSetting::set('free_plan_activation', 'instant');
    $plan = SubscriptionPlan::firstOrCreate(
        ['slug' => 'personal-accounting-lite'],
        [
            'module' => 'personal_accounting',
            'name' => 'Personal Accounting Lite',
            'price_monthly' => 399,
            'price_yearly' => 3990,
            'permissions' => ['personal-accounting.view'],
            'is_active' => true,
        ],
    );

    $this->post(route('checkout.process'), [
        'plan_id' => $plan->id,
        'email' => 'bkash@example.com',
        'password' => 'secret1234',
        'name' => 'bKash User',
        'provider' => 'manual_bkash',
    ])->assertRedirect();

    $sub = TenantSubscription::where('module', 'personal_accounting')->latest()->first();
    $payment = Payment::where('subscription_id', $sub->id)->first();

    $this->post(route('checkout.manual.submit'), [
        'payment_id' => $payment->id,
        'trx_id' => 'TESTTRX123',
    ])->assertRedirect();

    expect($payment->fresh()->status)->toBe('pending');
    expect($payment->fresh()->trx_id)->toBe('TESTTRX123');
    expect($sub->fresh()->billing_status)->toBe('pending');
});

// --- Phase 3: admin approval + lifecycle -------------------------------------

test('admin approves a manual payment and activates the subscription', function () {
    // Set up a super-admin so gate bypasses work.
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    $admin = \App\Models\User::factory()->create()->assignRole('super-admin');
    $this->actingAs($admin);

    \Modules\CORE\Models\CoreSetting::set('free_plan_activation', 'instant');
    $plan = SubscriptionPlan::firstOrCreate(
        ['slug' => 'personal-accounting-lite'],
        ['module' => 'personal_accounting', 'name' => 'PA Lite', 'price_monthly' => 399, 'permissions' => ['personal-accounting.view'], 'is_active' => true],
    );

    // Create a buyer + pending subscription + manual payment via the checkout service.
    $checkout = app(\Modules\CORE\Services\CheckoutService::class);
    $result = $checkout->prepare(['email' => 'approve@example.com', 'password' => 'secret1234', 'company_name' => 'Approve Co'], $plan);
    $subscription = $checkout->createPendingSubscription($result['tenant'], $plan, 'manual_bkash');
    $payment = app(\Modules\CORE\Services\PaymentService::class)->createPaymentRecord($subscription, 'manual_bkash', 'REF123');
    $payment->update(['trx_id' => 'TRXAPPROVE']);

    $this->post(route('subscriptions.payments.approve', $payment->id))->assertRedirect();

    expect($payment->fresh()->status)->toBe('approved');
    expect($subscription->fresh()->billing_status)->toBe('active');
});

test('admin cancels a subscription and revokes module permissions', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'personal-accounting.view', 'guard_name' => 'web']);
    $admin = \App\Models\User::factory()->create()->assignRole('super-admin');
    $this->actingAs($admin);

    $plan = SubscriptionPlan::firstOrCreate(
        ['slug' => 'personal-accounting-free'],
        ['module' => 'personal_accounting', 'name' => 'PA Free', 'price_monthly' => 0, 'permissions' => ['personal-accounting.view'], 'is_active' => true],
    );
    $checkout = app(\Modules\CORE\Services\CheckoutService::class);
    $result = $checkout->prepare(['email' => 'cancel@example.com', 'password' => 'secret1234', 'company_name' => 'Cancel Co'], $plan);
    $subscription = $checkout->createPendingSubscription($result['tenant'], $plan, 'sslcommerz');
    app(\Modules\CORE\Services\SubscriptionProvisioner::class)->activate($subscription);

    $this->post(route('subscriptions.cancel', $subscription->id))->assertRedirect();

    expect($subscription->fresh()->billing_status)->toBe('canceled');
    expect($result['user']->hasPermissionTo('personal-accounting.view'))->toBeFalse();
});
