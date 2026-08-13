<?php

namespace Modules\CORE\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\CORE\Models\Payment;
use Modules\CORE\Services\BillingService;

/**
 * Billing / invoice records for the authenticated user's tenant.
 */
class BillingController extends Controller
{
    public function __construct(private readonly BillingService $billing)
    {
    }

    public function index(Request $request): Response
    {
        $user = $request->user();

        $payments = Payment::query()
            ->where('tenant_id', $user->tenant_id)
            ->with('subscription.plan')
            ->latest()
            ->paginate(15);

        return Inertia::render('CORE::Checkout/Billing', [
            'payments' => $payments,
        ]);
    }

    public function download(Request $request, Payment $payment)
    {
        abort_unless($payment->tenant_id === (int) $request->user()->tenant_id, 403);

        return $this->billing->invoicePdf($payment)->download("invoice-{$payment->id}.pdf");
    }
}
