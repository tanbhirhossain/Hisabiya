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
use Modules\CORE\Services\ModuleRegistry;
use Modules\CORE\Services\PaymentService;
use Modules\CORE\Services\SubscriptionActivationService;
use Modules\CORE\Services\SubscriptionProvisioner;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkout,
        private readonly PaymentService $payments,
        private readonly SubscriptionActivationService $activation,
        private readonly ModuleRegistry $registry,
        private readonly SubscriptionProvisioner $provisioner,
    ) {
    }

    /**
     * Public pricing page — lists active plans grouped by module, with a module
     * switcher. Plans come straight from the DB so admin edits show up live.
     */
    public function pricing(Request $request): Response
    {
        $activePlans = SubscriptionPlan::query()
            ->where('is_active', true)
            ->orderBy('price_monthly')
            ->get();

        $modules = collect($this->registry->all())
            ->map(function (array $meta, string $key) use ($activePlans): array {
                return array_merge($meta, [
                    'key' => $key,
                    'plans' => $activePlans->where('module', $key)->values()->all(),
                ]);
            })
            ->filter(fn (array $m): bool => count($m['plans']) > 0)
            ->values()
            ->all();

        $selected = (string) $request->string('module', $modules[0]['key'] ?? 'personal_accounting');

        return Inertia::render('CORE::Checkout/Pricing', [
            'modules' => $modules,
            'module' => $selected,
        ]);
    }

    /**
     * Browse every sellable module the current tenant does NOT already have,
     * along with that module's active plans — used to add a second (or more)
     * module subscription from within an authenticated account.
     */
    public function browse(Request $request): Response
    {
        $owned = collect($this->provisioner->activeModulesForUser((int) $request->user()->id));

        $modules = collect($this->registry->all())
            ->reject(fn (array $meta) => $owned->contains($meta['key']))
            ->values()
            ->map(function (array $meta): array {
                $plans = SubscriptionPlan::query()
                    ->where('module', $meta['key'])
                    ->where('is_active', true)
                    ->orderBy('price_monthly')
                    ->get();

                return array_merge($meta, [
                    'plans' => $plans,
                    'has_subscription' => $this->provisioner->tenantHasModule((int) request()->user()->tenant_id, $meta['key']),
                ]);
            })
            ->values()
            ->all();

        return Inertia::render('CORE::Module/Browse', [
            'modules' => $modules,
        ]);
    }

    /**
     * Public checkout page for a plan (account + payment method). When the
     * `add` flag is set for an authenticated user, the account section is
     * skipped because they already have an account — they are adding a module.
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

        $authenticated = $request->boolean('add') && $request->user() !== null;

        return Inertia::render('CORE::Checkout/Checkout', [
            'plan' => $plan,
            'payment_methods' => $methods,
            'authenticated' => $authenticated,
            'user_email' => $authenticated ? $request->user()->email : null,
        ]);
    }

    /**
     * Process the checkout form: create account + tenant + subscription, then
     * initiate the selected payment method.
     */
    public function process(Request $request): RedirectResponse
    {
        // An already-authenticated user adding a module reuses their existing
        // account/tenant, so email/password/name are not required.
        $authenticated = $request->user() !== null;

        $data = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'email' => $authenticated ? ['nullable', 'email', 'max:191'] : ['required', 'email', 'max:191'],
            'password' => $authenticated ? ['nullable', 'string'] : ['required', 'string', 'min:8'],
            'name' => $authenticated ? ['nullable', 'string', 'max:191'] : ['required', 'string', 'max:191'],
            'company_name' => ['nullable', 'string', 'max:191'],
            'provider' => ['required', 'string', 'in:sslcommerz,manual_bkash,manual_bank'],
        ]);

        $plan = SubscriptionPlan::findOrFail($data['plan_id']);

        $result = DB::transaction(function () use ($data, $plan, $authenticated): array {
            // Reuse the logged-in identity/tenant when adding a module, else
            // create (or find) the account + tenant + owner membership.
            $user = $authenticated ? $request->user() : null;
            $prepared = $user
                ? $this->checkout->attachModule($user, $plan)
                : $this->checkout->prepare($data, $plan);

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
            if (! $authenticated) {
                Auth::login($prepared['user']);
            }

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
     * SSLCommerz IPN (Instant Payment Notification) webhook. SSLCommerz posts
     * server-to-server here after a payment is processed, separately from the
     * customer's browser callback. We validate the signature, resolve the
     * status, update the payment and auto-activate the subscription when paid.
     *
     * This endpoint is deliberately idempotent and always answers 200 so the
     * gateway stops retrying; the callback flow stays as the user-facing path.
     */
    public function ipn(Request $request): Response|\Symfony\Component\HttpFoundation\Response
    {
        $payload = $request->all();
        $ref = $payload['tran_id'] ?? null;

        // No transaction id → acknowledge and drop.
        if (! $ref) {
            return response('OK', 200);
        }

        $payment = Payment::where('provider_ref', $ref)->latest()->first();

        if (! $payment) {
            // Unknown transaction — log but acknowledge to stop retries.
            logger()->warning('SSLCommerz IPN for unknown transaction.', ['tran_id' => $ref]);

            return response('OK', 200);
        }

        $provider = $this->payments->provider('sslcommerz');

        // Reject forged callbacks when a signature is expected.
        if (! $provider->validateSignature($payload)) {
            logger()->warning('SSLCommerz IPN signature mismatch.', ['tran_id' => $ref]);

            return response('Forbidden', 403);
        }

        $status = $provider->resolveStatus((string) ($payload['status'] ?? ''));

        // Idempotent: if already resolved to the same state, don't re-process.
        if ($status === 'paid' && $payment->isPaid()) {
            return response('OK', 200);
        }

        if ($status === 'paid') {
            $payment->forceFill([
                'status' => 'paid',
                'trx_id' => $payment->trx_id ?: ($payload['trx_id'] ?? null),
                'paid_at' => $payment->paid_at ?? now(),
            ])->save();

            $this->activation->activate($payment->subscription);

            return response('OK', 200);
        }

        // failed / cancelled
        $payment->forceFill(['status' => $status === 'cancelled' ? 'failed' : $status])->save();

        return response('OK', 200);
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
