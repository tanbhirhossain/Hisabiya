<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CORE\Models\Payment;
use Modules\CORE\Models\SubscriptionPlan;
use Modules\CORE\Models\TenantSubscription;
use Modules\CORE\Payments\SslCommerzProvider;
use Modules\CORE\Services\CheckoutService;

uses(RefreshDatabase::class);

function ipnProvider(bool $sandbox = true, string $storeId = 'teststore', string $storePass = 'testpass'): void
{
    \Modules\CORE\Models\CoreSetting::set('payment_gateway_sslcommerz.enabled', true);
    \Modules\CORE\Models\CoreSetting::set('payment_gateway_sslcommerz.sandbox', $sandbox);
    \Modules\CORE\Models\CoreSetting::set('payment_gateway_sslcommerz.store_id', $storeId);
    \Modules\CORE\Models\CoreSetting::set('payment_gateway_sslcommerz.store_pass', $storePass);
}

function ipnPendingSubscription(): array
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
    $result = $checkout->prepare(['email' => 'ipn@example.com', 'password' => 'secret1234', 'company_name' => 'IPN Co'], $plan);
    $subscription = $checkout->createPendingSubscription($result['tenant'], $plan, 'sslcommerz');
    $payment = Payment::create([
        'tenant_id' => $subscription->tenant_id,
        'user_id' => $result['user']->id,
        'subscription_id' => $subscription->id,
        'provider' => 'sslcommerz',
        'provider_ref' => 'REFIPN123',
        'amount' => 799,
        'currency' => 'BDT',
        'status' => 'pending',
    ]);

    return ['subscription' => $subscription, 'payment' => $payment];
}

test('sslcommerz signature validation accepts a valid signed payload', function () {
    ipnProvider();

    $storePass = 'testpass';
    // Build the fields the gateway says it hashed.
    $fields = ['tran_id' => 'REFIPN123', 'status' => 'VALID', 'amount' => '799'];
    $verifyKey = implode(',', array_keys($fields));

    $concatenated = '';
    foreach (array_keys($fields) as $key) {
        $concatenated .= $fields[$key];
    }
    $verifySign = hash_hmac('md5', $concatenated, $storePass, false);

    $payload = array_merge($fields, ['verify_key' => $verifyKey, 'verify_sign' => $verifySign]);

    $provider = new SslCommerzProvider();
    expect($provider->validateSignature($payload))->toBeTrue();
});

test('sslcommerz signature validation rejects a tampered payload', function () {
    ipnProvider();

    $fields = ['tran_id' => 'REFIPN123', 'status' => 'VALID', 'amount' => '799'];
    $verifyKey = implode(',', array_keys($fields));
    $concatenated = '';
    foreach (array_keys($fields) as $key) {
        $concatenated .= $fields[$key];
    }
    $verifySign = hash_hmac('md5', $concatenated, 'testpass', false);

    // Tamper with the amount after signing.
    $payload = array_merge($fields, ['verify_key' => $verifyKey, 'verify_sign' => $verifySign]);
    $payload['amount'] = '9999999';

    $provider = new SslCommerzProvider();
    expect($provider->validateSignature($payload))->toBeFalse();
});

test('status resolution maps sslcommerz states', function () {
    $provider = new SslCommerzProvider();

    expect($provider->resolveStatus('VALID'))->toBe('paid');
    expect($provider->resolveStatus('VALIDATED'))->toBe('paid');
    expect($provider->resolveStatus('CANCELLED'))->toBe('cancelled');
    expect($provider->resolveStatus('FAILED'))->toBe('failed');
    expect($provider->resolveStatus('BOGUS'))->toBe('failed');
});

test('ipn webhook auto-activates a subscription on a valid signed payment', function () {
    ipnProvider();
    ['subscription' => $subscription, 'payment' => $payment] = ipnPendingSubscription();

    $fields = ['tran_id' => 'REFIPN123', 'status' => 'VALID', 'amount' => '799'];
    $verifyKey = implode(',', array_keys($fields));
    $concatenated = '';
    foreach (array_keys($fields) as $key) {
        $concatenated .= $fields[$key];
    }
    $verifySign = hash_hmac('md5', $concatenated, 'testpass', false);

    $this->post(route('checkout.ipn'), array_merge($fields, [
        'verify_key' => $verifyKey,
        'verify_sign' => $verifySign,
    ]))->assertOk();

    expect($payment->fresh()->status)->toBe('paid');
    expect($subscription->fresh()->billing_status)->toBe('active');
});

test('ipn webhook rejects a forged signature and does not activate', function () {
    ipnProvider();
    ['subscription' => $subscription, 'payment' => $payment] = ipnPendingSubscription();

    // Wrong signature.
    $fields = ['tran_id' => 'REFIPN123', 'status' => 'VALID', 'amount' => '799'];
    $verifyKey = implode(',', array_keys($fields));

    $this->post(route('checkout.ipn'), array_merge($fields, [
        'verify_key' => $verifyKey,
        'verify_sign' => 'forged-signature',
    ]))->assertForbidden();

    expect($payment->fresh()->status)->toBe('pending');
    expect($subscription->fresh()->billing_status)->toBe('pending');
});

test('ipn webhook is idempotent for an already-paid payment', function () {
    ipnProvider();
    ['subscription' => $subscription, 'payment' => $payment] = ipnPendingSubscription();

    $payment->forceFill(['status' => 'paid', 'paid_at' => now()])->save();
    $subscription->forceFill(['billing_status' => 'active', 'status' => 'active'])->save();

    $fields = ['tran_id' => 'REFIPN123', 'status' => 'VALID', 'amount' => '799'];
    $verifyKey = implode(',', array_keys($fields));
    $concatenated = '';
    foreach (array_keys($fields) as $key) {
        $concatenated .= $fields[$key];
    }
    $verifySign = hash_hmac('md5', $concatenated, 'testpass', false);

    $this->post(route('checkout.ipn'), array_merge($fields, [
        'verify_key' => $verifyKey,
        'verify_sign' => $verifySign,
    ]))->assertOk();

    expect($payment->fresh()->status)->toBe('paid');
});
