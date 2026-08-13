<?php

namespace Modules\CORE\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\CORE\Services\PaymentGatewaySettingsService;

/**
 * CORE super-admin payment gateway settings.
 */
class PaymentGatewayController extends Controller
{
    public function __construct(private readonly PaymentGatewaySettingsService $settings)
    {
    }

    public function index(Request $request): Response
    {
        return Inertia::render('CORE::Settings/PaymentGateways', [
            'gateways' => $this->settings->all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sslcommerz.enabled' => ['sometimes', 'boolean'],
            'sslcommerz.sandbox' => ['sometimes', 'boolean'],
            'sslcommerz.store_id' => ['nullable', 'string', 'max:191'],
            'sslcommerz.store_pass' => ['nullable', 'string', 'max:191'],
            'manual_bkash.enabled' => ['sometimes', 'boolean'],
            'manual_bkash.number' => ['nullable', 'string', 'max:191'],
            'manual_bkash.instructions' => ['nullable', 'string', 'max:500'],
            'manual_bank.enabled' => ['sometimes', 'boolean'],
            'manual_bank.account_name' => ['nullable', 'string', 'max:191'],
            'manual_bank.account_number' => ['nullable', 'string', 'max:191'],
            'manual_bank.bank_name' => ['nullable', 'string', 'max:191'],
            'manual_bank.instructions' => ['nullable', 'string', 'max:500'],
        ]);

        $this->settings->save($data);

        return redirect()->route('settings.payment-gateways')
            ->with('success', 'Payment gateway settings saved.');
    }
}
