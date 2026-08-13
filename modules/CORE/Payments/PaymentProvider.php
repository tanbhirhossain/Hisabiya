<?php

namespace Modules\CORE\Payments;

use Modules\CORE\Models\Payment;
use Modules\CORE\Models\TenantSubscription;

/**
 * Contract for a payment provider. Implementations handle hosted checkout,
 * webhook verification and payment status resolution. This lets us plug in
 * SSLCommerz, Stripe, manual bKash/Bank, etc. behind one interface.
 */
interface PaymentProvider
{
    /**
     * Initiate a hosted checkout for a subscription.
     *
     * @param  string|null  $tranId  optional pre-existing transaction id (matches the payment record)
     * @return array{redirect_url: string, checkout_session_id: string}
     */
    public function initiate(TenantSubscription $subscription, ?string $tranId = null): array;

    /**
     * Verify an incoming webhook/callback and return the resulting payment.
     */
    public function handleCallback(array $payload): Payment;

    /**
     * Confirm a payment status from the provider by its reference.
     */
    public function verify(string $providerRef): string;

    /**
     * Whether this provider uses manual (offline) confirmation.
     */
    public function isManual(): bool;
}
