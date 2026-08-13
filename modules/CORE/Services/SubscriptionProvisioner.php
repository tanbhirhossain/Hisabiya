<?php

namespace Modules\CORE\Services;

use Modules\CORE\Models\Membership;
use Modules\CORE\Models\Tenant;
use Modules\CORE\Models\TenantSubscription;

/**
 * Activates a subscription and provisions the module access for its users.
 * Called by the payment webhook / admin approval — idempotent so it can run
 * safely more than once.
 */
class SubscriptionProvisioner
{
    public function __construct(private readonly SubscriptionService $subscriptions)
    {
    }

    /**
     * Activate a pending subscription and grant its permissions.
     */
    public function activate(TenantSubscription $subscription): TenantSubscription
    {
        if ($subscription->billing_status === 'active') {
            return $subscription;
        }

        $subscription->forceFill([
            'status' => 'active',
            'billing_status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addYear(),
            'auto_renew' => true,
        ])->save();

        $permissions = $subscription->plan->permissions ?? [];

        // Grant the plan's permissions to every module membership user.
        $this->provision($subscription, $permissions);

        activity('subscription')
            ->performedOn($subscription)
            ->event('activated')
            ->log("Subscription to {$subscription->plan->name} activated");

        return $subscription->fresh()->load('plan');
    }

    /**
     * Ensure all module members carry the plan's permissions, and owners also
     * get the module-ACL permission so they can manage module users.
     */
    public function provision(TenantSubscription $subscription, array $permissions): void
    {
        $moduleKey = str_replace('_', '-', $subscription->module);

        Membership::where('tenant_id', $subscription->tenant_id)
            ->where('module', $subscription->module)
            ->where('is_active', true)
            ->each(function (Membership $membership) use ($permissions, $moduleKey): void {
                $user = $membership->user;
                $names = $permissions;

                if ($membership->isOwner()) {
                    $names[] = "{$moduleKey}.acl";
                }

                $existing = \Spatie\Permission\Models\Permission::whereIn('name', array_unique($names))->pluck('name');
                $user->givePermissionTo($existing);
            });
    }

    /**
     * Route helper: which module(s) the user has active access to.
     *
     * @return array<int, string> module names
     */
    public function activeModulesForUser(int $userId): array
    {
        $tenantIds = Membership::where('user_id', $userId)->where('is_active', true)->pluck('tenant_id');

        if ($tenantIds->isEmpty()) {
            return [];
        }

        return TenantSubscription::whereIn('tenant_id', $tenantIds)
            ->where('status', 'active')
            ->where('billing_status', 'active')
            ->distinct()
            ->pluck('module')
            ->values()
            ->all();
    }
}
