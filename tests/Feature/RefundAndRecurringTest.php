<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CORE\Models\Payment;
use Modules\CORE\Models\SubscriptionPlan;
use Modules\CORE\Models\TenantSubscription;
use Modules\CORE\Services\CheckoutService;
use Modules\CORE\Services\PaymentService;
use Modules\CORE\Services\RecurringBillingService;

uses(RefreshDatabase::class);

function rrPlan(): SubscriptionPlan
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

function rrPaidState(): array
{
    $plan = rrPlan();
    $checkout = app(CheckoutService::class);
    $result = $checkout->prepare(['email' => 'rr@example.com', 'password' => 'secret1234', 'company_name' => 'RR Co'], $plan);
    $subscription = $checkout->createPendingSubscription($result['tenant'], $plan, 'sslcommerz');
    app(\Modules\CORE\Services\SubscriptionProvisioner::class)->activate($subscription);

    $payment = Payment::create([
        'tenant_id' => $subscription->tenant_id,
        'user_id' => $result['user']->id,
        'subscription_id' => $subscription->id,
        'provider' => 'sslcommerz',
        'provider_ref' => 'REFPAID',
        'amount' => 799,
        'currency' => 'BDT',
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    return ['subscription' => $subscription->fresh(), 'payment' => $payment, 'tenant' => $result['tenant']];
}

// ---------- Refund ----------

test('refund marks a payment refunded and revokes subscription access', function () {
    ['subscription' => $subscription, 'payment' => $payment] = rrPaidState();

    $result = app(PaymentService::class)->refund($payment, 'Customer request');

    expect($result['refunded'])->toBeTrue();
    expect($payment->fresh()->status)->toBe('refunded');
    expect($subscription->fresh()->status)->toBe('canceled');
    expect($subscription->fresh()->billing_status)->toBe('canceled');
    expect($subscription->fresh()->auto_renew)->toBeFalse();

    // Module permissions revoked from the owner.
    expect($subscription->tenant->users()->first()->hasDirectPermission('personal-accounting.view'))->toBeFalse();
});

test('manual provider refund succeeds offline', function () {
    ['subscription' => $subscription, 'payment' => $payment] = rrPaidState();
    $payment->forceFill(['provider' => 'manual_bkash'])->save();

    $result = app(PaymentService::class)->refund($payment);

    expect($result['refunded'])->toBeTrue();
    expect($payment->fresh()->status)->toBe('refunded');
});

// ---------- Recurring billing ----------

test('renewDue issues a renewal invoice and flags a due subscription past-due', function () {
    ['subscription' => $subscription] = rrPaidState();

    // Force the term to have ended.
    $subscription->forceFill([
        'ends_at' => now()->subDay(),
        'billing_status' => 'active',
        'auto_renew' => true,
    ])->save();

    $count = app(RecurringBillingService::class)->renewDue();

    expect($count)->toBe(1);
    $sub = $subscription->fresh();
    expect($sub->billing_status)->toBe('past_due');
    expect($sub->grace_ends_at)->not->toBeNull();

    $renewal = Payment::where('subscription_id', $subscription->id)->where('is_renewal', true)->latest()->first();
    expect($renewal)->not->toBeNull();
    expect($renewal->status)->toBe('pending');
    expect((float) $renewal->amount)->toBe(799.0);
    expect($renewal->period_start)->not->toBeNull();
    expect($renewal->period_end)->not->toBeNull();
});

test('expire revokes access for a past-due subscription whose grace lapsed', function () {
    ['subscription' => $subscription] = rrPaidState();

    // Mark as past-due with a lapsed grace window.
    $subscription->forceFill([
        'status' => 'active',
        'billing_status' => 'past_due',
        'grace_ends_at' => now()->subDay(),
    ])->save();

    $count = app(RecurringBillingService::class)->expire();

    expect($count)->toBe(1);
    $sub = $subscription->fresh();
    expect($sub->status)->toBe('expired');
    expect($sub->billing_status)->toBe('expired');
    expect($sub->auto_renew)->toBeFalse();

    // Module permissions revoked.
    expect($subscription->tenant->users()->first()->hasDirectPermission('personal-accounting.view'))->toBeFalse();
});

test('renewal is idempotent — already past-due subscriptions are not re-invoiced', function () {
    ['subscription' => $subscription] = rrPaidState();
    $subscription->forceFill([
        'ends_at' => now()->subDay(),
        'billing_status' => 'active',
        'auto_renew' => true,
    ])->save();

    app(RecurringBillingService::class)->renewDue();
    $second = app(RecurringBillingService::class)->renewDue();

    // The second run finds nothing due (already past_due).
    expect($second)->toBe(0);
    expect(Payment::where('subscription_id', $subscription->id)->where('is_renewal', true)->count())->toBe(1);
});
