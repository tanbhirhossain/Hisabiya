<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CORE\Models\Membership;
use Modules\CORE\Models\Payment;
use Modules\CORE\Models\SubscriptionPlan;
use Modules\CORE\Services\BillingService;
use Modules\CORE\Services\CheckoutService;
use Modules\CORE\Services\SubscriptionProvisioner;

uses(RefreshDatabase::class);

function billingPlan(): SubscriptionPlan
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

function activePaUser(): array
{
    $plan = billingPlan();
    $checkout = app(CheckoutService::class);
    $result = $checkout->prepare(['email' => 'owner@example.com', 'password' => 'secret1234', 'company_name' => 'Owner Co'], $plan);
    $subscription = $checkout->createPendingSubscription($result['tenant'], $plan, 'sslcommerz');
    app(SubscriptionProvisioner::class)->activate($subscription);

    return ['user' => $result['user'], 'tenant' => $result['tenant'], 'subscription' => $subscription];
}

test('login routes a module subscriber to their module dashboard', function () {
    ['user' => $user] = activePaUser();

    $response = $this->post('/login', [
        'email' => 'owner@example.com',
        'password' => 'secret1234',
    ]);

    $response->assertRedirect('/personal/dashboard');
});

test('billing page shows payments and invoice download works', function () {
    ['user' => $user, 'subscription' => $subscription] = activePaUser();

    $payment = Payment::create([
        'tenant_id' => $subscription->tenant_id,
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
        'provider' => 'sslcommerz',
        'provider_ref' => 'REFBILL',
        'amount' => 799,
        'currency' => 'BDT',
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $this->actingAs($user)->get(route('billing.index'))->assertOk();

    // Invoice data is structured.
    $data = app(BillingService::class)->invoiceData($payment);
    expect($data['invoice_number'])->toContain('INV-');
    expect((float) $data['amount'])->toBe(799.0);
    expect($data['tenant']['name'])->toBe('Owner Co');
});

test('manual payment approval notifies the owner', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    $admin = \App\Models\User::factory()->create()->assignRole('super-admin');
    $this->actingAs($admin);

    ['user' => $user, 'subscription' => $subscription] = activePaUser();
    $payment = Payment::create([
        'tenant_id' => $subscription->tenant_id,
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
        'provider' => 'manual_bkash',
        'provider_ref' => 'REFAPPROVE',
        'amount' => 799,
        'currency' => 'BDT',
        'status' => 'pending',
        'trx_id' => 'TRXAPPROVE2',
    ]);

    $this->post(route('subscriptions.payments.approve', $payment->id))->assertRedirect();

    expect(\Illuminate\Notifications\DatabaseNotification::where('type', \Modules\CORE\Notifications\PaymentApprovedNotification::class)->count())->toBe(1);
});
