<?php

namespace Modules\CORE\Payments;

use Illuminate\Support\Str;
use Modules\CORE\Models\Payment;
use Modules\CORE\Models\TenantSubscription;

/**
 * Manual bKash / Bank payment. The customer is shown the account details and
 * submits a TRX ID / screenshot; a CORE admin approves before the subscription
 * is activated. Always used for offline/approved flows.
 */
class ManualProvider implements PaymentProvider
{
    public function __construct(private readonly string $method)
    {
    }

    public function isManual(): bool
    {
        return true;
    }

    public function initiate(TenantSubscription $subscription, ?string $tranId = null): array
    {
        $ref = $tranId ?? Str::upper(Str::random(10));

        return [
            'redirect_url' => route('checkout.manual', ['provider' => $this->method, 'ref' => $ref]),
            'checkout_session_id' => $ref,
        ];
    }

    public function handleCallback(array $payload): Payment
    {
        return Payment::where('id', $payload['payment_id'] ?? 0)->firstOrFail();
    }

    public function verify(string $providerRef): string
    {
        // Manual payments are 'pending' until an admin approves them.
        return 'pending';
    }

    public function method(): string
    {
        return $this->method;
    }

    /**
     * Manual payments have no online gateway to refund against; a refund is
     * recorded offline (money returned by bank/bKash manually). Always returns
     * true so the caller marks the payment refunded.
     */
    public function refund(Payment $payment, ?string $reason = null): bool
    {
        return true;
    }

    public function accountDetails(): array
    {
        $settings = app(\Modules\CORE\Services\PaymentGatewaySettingsService::class)->all();

        return match ($this->method) {
            'manual_bkash' => [
                'name' => 'bKash (Personal)',
                'number' => $settings['manual_bkash']['number'] ?: '01700-000000',
                'instructions' => $settings['manual_bkash']['instructions'],
            ],
            default => [
                'name' => $settings['manual_bank']['bank_name'] ?: 'Bank Transfer',
                'number' => $settings['manual_bank']['account_name']
                    ? $settings['manual_bank']['account_name'].' · '.$settings['manual_bank']['account_number']
                    : 'Account: '.($settings['manual_bank']['account_number'] ?: '0000-0000-0000'),
                'instructions' => $settings['manual_bank']['instructions'],
            ],
        };
    }
}
