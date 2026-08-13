<?php

namespace Modules\CORE\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\CORE\Models\Payment;
use Modules\CORE\Models\TenantSubscription;

/**
 * SSLCommerz hosted checkout. In production this posts to the SSLCommerz
 * gateway and verifies via their API. In local/dev mode (no API keys set) it
 * falls back to a simulated success so the flow is fully testable locally.
 */
class SslCommerzProvider implements PaymentProvider
{
    private string $storeId;
    private string $storePass;
    private bool $sandbox;

    public function __construct(?array $config = null)
    {
        $config ??= app(\Modules\CORE\Services\PaymentGatewaySettingsService::class)->all()['sslcommerz'];

        $this->storeId = (string) ($config['store_id'] ?? '');
        $this->storePass = (string) ($config['store_pass'] ?? '');
        $this->sandbox = (bool) ($config['sandbox'] ?? true);
    }

    public function isManual(): bool
    {
        return false;
    }

    public function initiate(TenantSubscription $subscription, ?string $tranId = null): array
    {
        $ref = $tranId ?? Str::uuid()->toString();
        $amount = (float) $subscription->plan->price_monthly;

        // In dev (no store id), redirect to an explicit simulated gateway page so a
        // user must confirm the payment before access is granted. This prevents
        // anyone from bypassing payment when no real gateway is configured.
        if ($this->storeId === '') {
            return [
                'redirect_url' => route('checkout.simulate', ['tranId' => $ref]),
                'checkout_session_id' => $ref,
            ];
        }

        // Production: build the SSLCommerz transaction payload.
        $payload = [
            'store_id' => $this->storeId,
            'store_passwd' => $this->storePass,
            'total_amount' => $amount,
            'currency' => 'BDT',
            'tran_id' => $ref,
            'success_url' => route('checkout.callback', ['provider' => 'sslcommerz']),
            'fail_url' => route('checkout.callback', ['provider' => 'sslcommerz']),
            'cancel_url' => route('checkout.callback', ['provider' => 'sslcommerz']),
            'cus_name' => $subscription->tenant->name,
            'cus_email' => $subscription->tenant->email ?? '',
            'product_name' => $subscription->plan->name,
            'product_category' => $subscription->module,
            'product_profile' => 'general',
        ];

        $endpoint = $this->sandbox
            ? 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php'
            : 'https://secure.sslcommerz.com/gwprocess/v4/api.php';

        $response = Http::asForm()->post($endpoint, $payload)->json();

        return [
            'redirect_url' => $response['GatewayPageURL'] ?? route('checkout.callback', ['provider' => 'sslcommerz']),
            'checkout_session_id' => $ref,
        ];
    }

    public function handleCallback(array $payload): Payment
    {
        // Locate the payment by the transaction id.
        $ref = $payload['tran_id'] ?? null;

        return Payment::where('provider_ref', $ref)->firstOrFail();
    }

    public function verify(string $providerRef): string
    {
        // In dev, treat as paid.
        if ($this->storeId === '') {
            return 'paid';
        }

        // Production: query SSLCommerz transaction query API.
        $response = Http::asForm()->post('https://secure.sslcommerz.com/validator/api/validationserverAPI.php', [
            'store_id' => $this->storeId,
            'store_passwd' => $this->storePass,
            'tran_id' => $providerRef,
        ])->json();

        $status = $response['status'] ?? 'FAILED';

        return strtolower($status) === 'valid' ? 'paid' : 'failed';
    }
}
