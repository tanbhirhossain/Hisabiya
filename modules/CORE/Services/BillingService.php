<?php

namespace Modules\CORE\Services;

use Modules\CORE\Models\Payment;
use Modules\CORE\Models\TenantSubscription;

/**
 * Generates invoice data and downloadable PDFs for paid subscriptions.
 */
class BillingService
{
    /**
     * Build the invoice data structure for a payment.
     *
     * @return array<string, mixed>
     */
    public function invoiceData(Payment $payment): array
    {
        $payment->load(['tenant', 'subscription.plan']);

        return [
            'invoice_number' => 'INV-'.$payment->id.'-'.now()->format('Ymd'),
            'date' => ($payment->paid_at ?? $payment->created_at)->format('d M Y'),
            'tenant' => [
                'name' => $payment->tenant?->name,
                'email' => $payment->tenant?->email,
            ],
            'provider' => $payment->provider,
            'trx_id' => $payment->trx_id ?? $payment->provider_ref,
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
            'plan_name' => $payment->subscription?->plan?->name,
            'module' => $payment->subscription?->module,
        ];
    }

    /**
     * Render and return the invoice as a PDF download.
     */
    public function invoicePdf(Payment $payment)
    {
        $data = $this->invoiceData($payment);

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('core::invoice', $data)->setPaper('a4');
    }
}
