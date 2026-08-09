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
        // Remove the module's own permissions first, then re-apply what the plan grants.
        $modulePermissions = Permission::where('name', 'like', "{$module}.%")->pluck('id');

        $tenant->users()->each(function (User $user) use ($modulePermissions, $permissions): void {
            $user->syncPermissions($modulePermissions->intersect(
                $user->permissions()->pluck('id')
            ));

            if (! empty($permissions)) {
                $names = Permission::whereIn('name', $permissions)->pluck('name');
                $user->givePermissionTo($names);
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
}
