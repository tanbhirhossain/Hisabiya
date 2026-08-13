<?php

namespace Modules\CORE\Services;

use Modules\CORE\Models\Payment;
use Modules\CORE\Models\TenantSubscription;
use Modules\CORE\Payments\ManualProvider;
use Modules\CORE\Payments\PaymentProvider;
use Modules\CORE\Payments\SslCommerzProvider;

/**
 * Resolves the correct payment provider and orchestrates payment lifecycle.
 */
class PaymentService
{
    /**
     * Map a provider key to its PaymentProvider instance.
     */
    public function provider(string $provider): PaymentProvider
    {
        return match ($provider) {
            'sslcommerz' => new SslCommerzProvider(),
            'manual_bkash' => new ManualProvider('manual_bkash'),
            'manual_bank' => new ManualProvider('manual_bank'),
            default => throw new \InvalidArgumentException("Unsupported payment provider: {$provider}"),
        };
    }

    /**
     * List the enabled payment providers for the checkout UI.
     *
     * @return array<int, string>
     */
    public function enabledProviders(): array
    {
        $settings = app(\Modules\CORE\Services\PaymentGatewaySettingsService::class)->all();

        $enabled = [];
        foreach (['sslcommerz', 'manual_bkash', 'manual_bank'] as $key) {
            if ($settings[$key]['enabled'] ?? false) {
                $enabled[] = $key;
            }
        }

        // Fall back to all providers when nothing is configured yet.
        return $enabled ?: ['sslcommerz', 'manual_bkash', 'manual_bank'];
    }

    /**
     * Initiate a hosted checkout for a subscription with the given provider.
     * Uses the payment record's provider_ref as the transaction id so the
     * gateway redirect maps back to the correct payment.
     */
    public function initiate(TenantSubscription $subscription, string $provider): array
    {
        $p = $this->provider($provider);

        $tranId = Payment::where('subscription_id', $subscription->id)
            ->where('provider', $provider)
            ->latest()
            ->value('provider_ref');

        return $p->initiate($subscription, $tranId);
    }

    /**
     * Create a Payment record for a subscription + provider.
     */
    public function createPaymentRecord(TenantSubscription $subscription, string $provider, string $ref): Payment
    {
        return Payment::create([
            'tenant_id' => $subscription->tenant_id,
            'user_id' => auth()->id() ?? $subscription->tenant->users()->first()?->id,
            'subscription_id' => $subscription->id,
            'provider' => $provider,
            'provider_ref' => $ref,
            'amount' => $subscription->plan->price_monthly,
            'currency' => 'BDT',
            'status' => 'pending',
        ]);
    }
}
