<?php

namespace Modules\CORE\Services;

use App\Models\User;
use Modules\CORE\Models\SubscriptionPlan;
use Modules\CORE\Models\Tenant;
use Modules\CORE\Models\TenantSubscription;
use Spatie\Permission\Models\Permission;

/**
 * Manages module subscriptions from the CORE module: maps plans to permissions,
 * resolves a tenant's effective permission set, and syncs those permissions onto
 * the tenant's users so the ACL controls module access.
 */
class SubscriptionService
{
    /**
     * The permissions a tenant should have for a module based on its active plan.
     */
    public function resolvePermissions(int $tenantId, string $module): array
    {
        $subscription = TenantSubscription::query()
            ->where('tenant_id', $tenantId)
            ->where('module', $module)
            ->where('status', 'active')
            ->with('plan')
            ->first();

        if (! $subscription?->isActive() || ! $subscription->plan) {
            return [];
        }

        return $subscription->plan->permissions ?? [];
    }

    /**
     * Assign a tenant to a module plan and sync the plan's permissions to the
     * tenant's users (so CORE can "adjust permissions" for a subscription).
     */
    public function subscribe(Tenant $tenant, SubscriptionPlan $plan, string $module, ?array $overrides = null): TenantSubscription
    {
        $permissions = $overrides ?? $plan->permissions ?? [];

        // Ensure the permissions exist before assigning.
        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $subscription = TenantSubscription::updateOrCreate(
            ['tenant_id' => $tenant->id, 'module' => $module],
            [
                'plan_id' => $plan->id,
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => now()->addYear(),
                'auto_renew' => true,
            ],
        );

        $this->syncPermissionsToUsers($tenant, $module, $permissions);

        return $subscription->load('plan');
    }

    /**
     * Grant the resolved module permissions to every user in the tenant.
     */
    public function syncPermissionsToUsers(Tenant $tenant, string $module, array $permissions): void
    {
        // The module key uses underscores (e.g. personal_accounting) but permission
        // names use hyphens (e.g. personal-accounting.view). Normalise the prefix.
        $prefix = str_replace('_', '-', $module);
        $modulePermissionIds = Permission::where('name', 'like', "{$prefix}.%")->pluck('id');

        $tenant->users()->each(function (User $user) use ($modulePermissionIds, $permissions): void {
            // First remove all of this module's direct permissions from the user.
            $user->permissions()->detach($modulePermissionIds);

            // Then re-apply exactly what the plan grants.
            if (! empty($permissions)) {
                $names = Permission::whereIn('name', $permissions)->pluck('name');
                if ($names->isNotEmpty()) {
                    $user->givePermissionTo($names);
                }
            }
        });
    }

    /**
     * Whether a tenant has an active subscription granting a given permission.
     */
    public function tenantHasPermission(int $tenantId, string $module, string $permission): bool
    {
        return in_array($permission, $this->resolvePermissions($tenantId, $module), true);
    }

    public function plansForModule(string $module)
    {
        return SubscriptionPlan::query()
            ->where('module', $module)
            ->where('is_active', true)
            ->orderBy('price_monthly')
            ->get();
    }

    /**
     * Approve a manual payment (from the CORE admin queue) and activate its subscription.
     */
    public function approveManualPayment(\Modules\CORE\Models\Payment $payment, ?int $approverId = null): void
    {
        $payment->forceFill([
            'status' => 'approved',
            'paid_at' => $payment->paid_at ?? now(),
        ])->save();

        if ($payment->subscription) {
            $this->activate($payment->subscription);

            // Notify the tenant's owner.
            $planName = $payment->subscription->plan?->name ?? 'subscription';
            $owner = \Modules\CORE\Models\Membership::where('tenant_id', $payment->tenant_id)
                ->where('module', $payment->subscription->module)
                ->where('role', 'owner')
                ->first();
            $owner?->user->notify(new \Modules\CORE\Notifications\PaymentApprovedNotification(
                $planName,
                (float) $payment->amount,
            ));
        }

        activity('payment')
            ->performedOn($payment)
            ->event('approved')
            ->log("Manual payment for {$payment->provider} approved");
    }

    /**
     * Reject a manual payment.
     */
    public function rejectManualPayment(\Modules\CORE\Models\Payment $payment): void
    {
        $payment->forceFill(['status' => 'failed'])->save();

        activity('payment')
            ->performedOn($payment)
            ->event('rejected')
            ->log('Manual payment rejected');
    }

    /**
     * Activate a subscription directly (used by admin approvals / manual payments).
     */
    public function activate(TenantSubscription $subscription): TenantSubscription
    {
        return app(\Modules\CORE\Services\SubscriptionProvisioner::class)->activate($subscription);
    }

    /**
     * Downgrade a subscription to another plan (removes old permissions, grants new).
     */
    public function downgrade(TenantSubscription $subscription, SubscriptionPlan $newPlan): TenantSubscription
    {
        $subscription->forceFill(['plan_id' => $newPlan->id])->save();

        $this->syncPermissionsToUsers($subscription->tenant, $subscription->module, $newPlan->permissions ?? []);

        activity('subscription')
            ->performedOn($subscription)
            ->event('downgraded')
            ->log("Subscription downgraded to {$newPlan->name}");

        return $subscription->fresh()->load('plan');
    }
}
