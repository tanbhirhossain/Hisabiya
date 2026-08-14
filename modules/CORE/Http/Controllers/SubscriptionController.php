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
            'paidPayments' => Payment::query()
                ->whereIn('status', ['paid', 'approved', 'refunded'])
                ->with(['tenant:id,name,email', 'subscription:id,module'])
                ->latest()
                ->limit(15)
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

    /**
     * Refund a paid payment and revoke the subscription access.
     */
    public function refundPayment(Request $request, Payment $payment): RedirectResponse
    {
        $reason = $request->string('reason')->toString();
        $result = app(\Modules\CORE\Services\PaymentService::class)->refund($payment, $reason ?: null);

        return redirect()->route('subscriptions.index')
            ->with(
                $result['refunded'] ? 'success' : 'error',
                $result['refunded']
                    ? 'Payment refunded and subscription access revoked.'
                    : 'Refund failed at the payment provider.'
            );
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

    /**
     * Show the "create plan" form with a permission picker.
     */
    public function createPlan(): Response
    {
        return Inertia::render('CORE::Subscriptions/PlanForm', [
            'plan' => null,
            'permission_groups' => $this->permissionPickerData(),
            'modules' => $this->registeredModules(),
        ]);
    }

    /**
     * Store a new subscription plan.
     */
    public function storePlan(Request $request): RedirectResponse
    {
        $data = $this->validatePlan($request);

        SubscriptionPlan::create($data);

        return redirect()->route('subscriptions.index')
            ->with('success', "Plan {$data['name']} created.");
    }

    /**
     * Show the "edit plan" form with a permission picker.
     */
    public function editPlan(SubscriptionPlan $plan): Response
    {
        return Inertia::render('CORE::Subscriptions/PlanForm', [
            'plan' => $plan,
            'permission_groups' => $this->permissionPickerData(),
            'modules' => $this->registeredModules(),
        ]);
    }

    /**
     * Update a subscription plan.
     */
    public function updatePlan(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $data = $this->validatePlan($request);

        $plan->update($data);

        // If the plan changed its permissions, re-sync all active subscribers
        // so access matches the plan's (new) permission set immediately.
        foreach ($plan->subscriptions()->where('billing_status', 'active')->get() as $sub) {
            if ($sub->tenant) {
                $this->service->syncPermissionsToUsers($sub->tenant, $sub->module, $data['permissions'] ?? []);
            }
        }

        return redirect()->route('subscriptions.index')
            ->with('success', "Plan {$data['name']} updated.");
    }

    /**
     * Delete a subscription plan (only if no active subscriptions use it).
     */
    public function destroyPlan(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        if ($plan->subscriptions()->whereIn('status', ['active', 'canceled'])->where('billing_status', '!=', 'expired')->exists()) {
            return redirect()->route('subscriptions.index')
                ->with('error', 'Cannot delete a plan that has subscriptions.');
        }

        $plan->delete();

        return redirect()->route('subscriptions.index')
            ->with('success', 'Plan deleted.');
    }

    /**
     * Validate the plan form data.
     *
     * @return array<string, mixed>
     */
    private function validatePlan(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'slug' => ['required', 'string', 'max:191', 'alpha_dash'],
            'module' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['nullable', 'numeric', 'min:0'],
            'features' => ['nullable', 'array'],
            'features.*' => ['nullable', 'string'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['required', 'string', 'exists:permissions,name'],
            'feature_flags' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ]);

        return [
            'name' => $data['name'],
            'slug' => $data['slug'],
            'module' => $data['module'],
            'description' => $data['description'] ?? null,
            'price_monthly' => $data['price_monthly'],
            'price_yearly' => $data['price_yearly'] ?? 0,
            'features' => array_values(array_filter($data['features'] ?? [])),
            'permissions' => array_values($data['permissions'] ?? []),
            'feature_flags' => $data['feature_flags'] ?? [],
            'is_active' => (bool) ($data['is_active'] ?? true),
        ];
    }

    /**
     * All permissions grouped by module for the permission picker.
     *
     * @return array<int, array{module: string, permissions: array<int, string>}>
     */
    private function permissionPickerData(): array
    {
        $permissions = \Spatie\Permission\Models\Permission::pluck('name');

        return $permissions
            ->groupBy(fn (string $name) => str($name)->before('.')->toString())
            ->map(fn ($items, $module) => [
                'module' => (string) $module,
                'permissions' => $items->sort()->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * Registered sellable modules (for the module dropdown).
     *
     * @return array<int, array{key: string, label: string}>
     */
    private function registeredModules(): array
    {
        return collect(app(\Modules\CORE\Services\ModuleRegistry::class)->all())
            ->map(fn ($meta, $key) => ['key' => $key, 'label' => $meta['label']])
            ->values()
            ->all();
    }
}
