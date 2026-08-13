<?php

namespace Modules\CORE\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\CORE\Models\SubscriptionPlan;
use Modules\CORE\Models\Tenant;
use Modules\CORE\Models\TenantSubscription;
use Modules\CORE\Models\Payment;
use Modules\CORE\Services\SubscriptionService;

class SubscriptionController extends Controller
{
    public function __construct(private readonly SubscriptionService $service)
    {
    }

    public function index(Request $request): Response
    {
        return Inertia::render('CORE::Subscriptions/Index', [
            'plans' => SubscriptionPlan::query()->withCount('subscriptions')->orderBy('module')->orderBy('price_monthly')->get(),
            'subscriptions' => TenantSubscription::query()
                ->with(['tenant:id,name,email', 'plan:id,name,slug,module'])
                ->latest()
                ->get(),
            'tenants' => Tenant::query()->select('id', 'name')->orderBy('name')->get(),
            'pendingPayments' => Payment::query()
                ->where('status', 'pending')
                ->with(['tenant:id,name,email', 'subscription:id,module'])
                ->latest()
                ->get(),
        ]);
    }

    /**
     * Approve a pending manual payment.
     */
    public function approvePayment(Request $request, Payment $payment): RedirectResponse
    {
        $this->service->approveManualPayment($payment, (int) $request->user()->id);

        return redirect()->route('subscriptions.index')
            ->with('success', 'Payment approved and subscription activated.');
    }

    /**
     * Reject a pending manual payment.
     */
    public function rejectPayment(Request $request, Payment $payment): RedirectResponse
    {
        $this->service->rejectManualPayment($payment);

        return redirect()->route('subscriptions.index')
            ->with('success', 'Payment rejected.');
    }

    public function assign(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'module' => ['required', 'string'],
        ]);

        $tenant = Tenant::findOrFail($data['tenant_id']);
        $plan = SubscriptionPlan::findOrFail($data['plan_id']);

        $this->service->subscribe($tenant, $plan, $data['module']);

        return redirect()->route('subscriptions.index')
            ->with('success', "{$tenant->name} subscribed to {$plan->name}.");
    }

    public function cancel(Request $request, TenantSubscription $subscription): RedirectResponse
    {
        $subscription->update(['status' => 'canceled', 'billing_status' => 'canceled', 'auto_renew' => false]);

        // Revoke the module permissions for the tenant's users.
        $this->service->syncPermissionsToUsers($subscription->tenant, $subscription->module, []);

        return redirect()->route('subscriptions.index')
            ->with('success', 'Subscription canceled.');
    }

    /**
     * Downgrade a subscription to a lower plan.
     */
    public function downgrade(Request $request, TenantSubscription $subscription): RedirectResponse
    {
        $data = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
        ]);

        $newPlan = SubscriptionPlan::findOrFail($data['plan_id']);

        $this->service->downgrade($subscription, $newPlan);

        return redirect()->route('subscriptions.index')
            ->with('success', "Downgraded to {$newPlan->name}.");
    }
}
