<?php

namespace Modules\CORE\Services;

use Modules\CORE\Models\CoreSetting;

/**
 * Reads and writes payment gateway settings (stored in core_settings) so the
 * CORE super admin can configure SSLCommerz and manual bKash/Bank from the UI
 * without touching config files.
 */
class PaymentGatewaySettingsService
{
    public const PREFIX = 'payment_gateway_';

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return [
            'sslcommerz' => [
                'enabled' => (bool) $this->get('sslcommerz', 'enabled', false),
                'sandbox' => (bool) $this->get('sslcommerz', 'sandbox', true),
                'store_id' => (string) $this->get('sslcommerz', 'store_id', ''),
                'store_pass' => (string) $this->get('sslcommerz', 'store_pass', ''),
            ],
            'manual_bkash' => [
                'enabled' => (bool) $this->get('manual_bkash', 'enabled', false),
                'number' => (string) $this->get('manual_bkash', 'number', ''),
                'instructions' => (string) $this->get('manual_bkash', 'instructions', 'Send the amount to the bKash number, then enter your TRX ID.'),
            ],
            'manual_bank' => [
                'enabled' => (bool) $this->get('manual_bank', 'enabled', false),
                'account_name' => (string) $this->get('manual_bank', 'account_name', ''),
                'account_number' => (string) $this->get('manual_bank', 'account_number', ''),
                'bank_name' => (string) $this->get('manual_bank', 'bank_name', ''),
                'instructions' => (string) $this->get('manual_bank', 'instructions', 'Transfer the amount to the bank account, then enter the reference.'),
            ],
        ];
    }

    /**
     * Save settings from validated request data.
     *
     * @param  array<string, mixed>  $data
     */
    public function save(array $data): void
    {
        $providers = ['sslcommerz', 'manual_bkash', 'manual_bank'];

        foreach ($providers as $provider) {
            $fields = $data[$provider] ?? [];
            foreach ($fields as $key => $value) {
                $this->set($provider, (string) $key, $value);
            }
        }
    }

    /**
     * Read a single gateway setting.
     */
    public function get(string $provider, string $key, mixed $default = null): mixed
    {
        return CoreSetting::get(self::PREFIX.$provider.'.'.$key, $default);
    }

    public function set(string $provider, string $key, mixed $value): void
    {
        CoreSetting::set(self::PREFIX.$provider.'.'.$key, $value);
    }

    /**
     * Whether SSLCommerz has real credentials configured (not dev/simulation).
     */
    public function sslcommerzConfigured(): bool
    {
        $config = $this->all()['sslcommerz'];

        return $config['enabled'] && $config['store_id'] !== '' && $config['store_pass'] !== '';
    }
}
