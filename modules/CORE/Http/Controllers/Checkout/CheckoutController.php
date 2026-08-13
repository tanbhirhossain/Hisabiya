<?php

namespace Modules\CORE\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Modules\CORE\Models\Payment;
use Modules\CORE\Models\SubscriptionPlan;
use Modules\CORE\Models\TenantSubscription;
use Modules\CORE\Services\CheckoutService;
use Modules\CORE\Services\PaymentService;
use Modules\CORE\Services\SubscriptionActivationService;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkout,
        private readonly PaymentService $payments,
        private readonly SubscriptionActivationService $activation,
    ) {
    }

    /**
     * Public pricing page — lists plans per module.
     */
    public function pricing(Request $request): Response
    {
        $module = $request->string('module', 'personal_accounting');

        $plans = SubscriptionPlan::query()
            ->where('module', $module)
            ->where('is_active', true)
            ->orderBy('price_monthly')
            ->get();

        return Inertia::render('CORE::Checkout/Pricing', [
            'module' => (string) $module,
            'plans' => $plans,
        ]);
    }

    /**
     * Public checkout page for a plan (account + payment method).
     */
    public function checkout(Request $request, SubscriptionPlan $plan): Response
    {
        $methodMap = [
            'sslcommerz' => ['id' => 'sslcommerz', 'label' => 'Online (bKash/Nagad/Card/Bank)', 'icon' => 'credit-card'],
            'manual_bkash' => ['id' => 'manual_bkash', 'label' => 'Manual bKash', 'icon' => 'smartphone'],
            'manual_bank' => ['id' => 'manual_bank', 'label' => 'Manual Bank Transfer', 'icon' => 'landmark'],
        ];

        $methods = collect($this->payments->enabledProviders())
            ->map(fn ($key) => $methodMap[$key] ?? null)
            ->filter()
            ->values()
            ->all();

        return Inertia::render('CORE::Checkout/Checkout', [
            'plan' => $plan,
            'payment_methods' => $methods,
        ]);
    }

    /**
     * Process the checkout form: create account + tenant + subscription, then
     * initiate the selected payment method.
     */
    public function process(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'email' => ['required', 'email', 'max:191'],
            'password' => ['required', 'string', 'min:8'],
            'name' => ['required', 'string', 'max:191'],
            'company_name' => ['nullable', 'string', 'max:191'],
            'provider' => ['required', 'string', 'in:sslcommerz,manual_bkash,manual_bank'],
        ]);

        $plan = SubscriptionPlan::findOrFail($data['plan_id']);

        $result = DB::transaction(function () use ($data, $plan): array {
            // Create (or find) user + tenant + owner membership.
            $prepared = $this->checkout->prepare($data, $plan);

            // Create a pending subscription.
            $ref = Str::uuid()->toString();
            $subscription = $this->checkout->createPendingSubscription($prepared['tenant'], $plan, $data['provider'], $ref);

            // Create a payment record.
            $this->payments->createPaymentRecord($subscription, $data['provider'], $ref);

            // Determine initial state (free + instant = active immediately).
            $initial = $this->activation->determineInitialState($subscription, $data['provider']);
            $activatedNow = false;
            if ($initial === 'active') {
                $this->activation->activate($subscription);
                $activatedNow = true;
            }

            // Log the user in so they're routed after payment.
            Auth::login($prepared['user']);

            return [
                'subscription' => $subscription->fresh(),
                'provider' => $data['provider'],
                'activated_now' => $activatedNow,
            ];
        });

        // Free plan activated instantly → go straight to the module dashboard.
        if ($result['activated_now']) {
            return redirect($this->activation->routeForUser((int) $request->user()?->id))
                ->with('success', 'Your subscription is active. Welcome!');
        }

        // Paid/manual → initiate the payment (online redirects; manual shows instructions).
        $init = $this->payments->initiate($result['subscription'], $result['provider']);

        return redirect($init['redirect_url']);
    }

    /**
     * Simulated gateway page (dev mode, no real SSLCommerz keys). The user must
     * explicitly click "Complete payment" to simulate a successful gateway
     * redirect-back. Access is never granted automatically.
     */
    public function simulate(Request $request, string $tranId): Response
    {
        $payment = Payment::where('provider_ref', $tranId)->latest()->first();

        return Inertia::render('CORE::Checkout/Simulate', [
            'tranId' => $tranId,
            'amount' => $payment?->amount ?? 0,
            'plan_name' => $payment?->subscription?->plan?->name ?? 'Subscription',
            'complete_url' => route('checkout.callback', ['provider' => 'sslcommerz', 'tran_id' => $tranId]),
        ]);
    }

    /**
     * Payment gateway callback (SSLCommerz success/fail/cancel + dev simulation).
     */
    public function callback(Request $request): RedirectResponse
    {
        $provider = $request->string('provider', 'sslcommerz');
        $ref = $request->string('tran_id');

        // Locate the payment + subscription.
        $payment = Payment::where('provider_ref', $ref)->first();

        if (! $payment) {
            return redirect()->route('home')->with('error', 'Payment record not found.');
        }

        $subscription = $payment->subscription;

        // Verify with the provider (dev returns 'paid').
        $status = $this->payments->provider((string) $provider)->verify((string) $ref);

        if ($status === 'paid') {
            $payment->forceFill(['status' => 'paid', 'paid_at' => now()])->save();
            $this->activation->activate($subscription);
        } else {
            $payment->forceFill(['status' => 'failed'])->save();
        }

        // Route the logged-in user to their module dashboard.
        $route = $this->activation->routeForUser((int) $request->user()?->id);

        return redirect($route);
    }

    /**
     * Manual payment: show instructions + collect TRX ID / screenshot.
     */
    public function manual(Request $request, string $provider): Response
    {
        $payment = Payment::where('provider_ref', $request->string('ref'))->latest()->first();

        return Inertia::render('CORE::Checkout/Manual', [
            'provider' => $provider,
            'ref' => $payment?->provider_ref ?? '',
            'pending_payment_id' => $payment?->id,
            'details' => $this->payments->provider($provider)->accountDetails(),
        ]);
    }

    /**
     * Store manual payment proof (TRX ID / screenshot) → goes to CORE approval queue.
     */
    public function manualSubmit(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'payment_id' => ['required', 'integer', 'exists:payments,id'],
            'trx_id' => ['required', 'string', 'max:100'],
            'proof' => ['nullable', 'file', 'image', 'max:4096'],
        ]);

        $payment = Payment::findOrFail($data['payment_id']);

        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('payments/proofs', 'public');
        }

        $payment->forceFill([
            'trx_id' => $data['trx_id'],
            'proof_path' => $proofPath,
            'status' => 'pending', // awaits admin approval
        ])->save();

        // Route to the module (access stays gated until approved).
        return redirect($this->activation->routeForUser((int) $request->user()?->id))
            ->with('success', 'Payment proof submitted. An admin will confirm your subscription.');
    }
}
