<?php

namespace Modules\CORE\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\CORE\Models\Membership;
use Modules\CORE\Models\Tenant;

/**
 * Manages module-scoped memberships. The module Owner can add members directly
 * (creating their login credentials) and assign module roles, without requiring
 * the invitee to pre-register. All operations are tenant + module scoped.
 */
class ModuleMembershipService
{
    /**
     * List active members for a tenant + module.
     */
    public function members(int $tenantId, string $module)
    {
        return Membership::query()
            ->where('tenant_id', $tenantId)
            ->where('module', $module)
            ->with('user:id,name,email,company_name,is_active')
            ->orderBy('role')
            ->orderBy('user_id')
            ->get();
    }

    /**
     * Add a member to a module within a tenant, creating their login if needed.
     */
    public function addMember(Tenant $tenant, string $module, array $data): Membership
    {
        return DB::transaction(function () use ($tenant, $module, $data): Membership {
            $existing = User::where('email', $data['email'])->first();

            $user = $existing ?? User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'is_active' => true,
                'tenant_id' => $tenant->id,
            ]);

            // If the user already existed, update their password + tenant if provided.
            if ($existing && isset($data['password']) && $data['password'] !== '') {
                $user->forceFill(['password' => Hash::make($data['password'])])->save();
            }
            if ($existing && ! $user->tenant_id) {
                $user->forceFill(['tenant_id' => $tenant->id])->save();
            }

            $membership = Membership::updateOrCreate(
                ['user_id' => $user->id, 'tenant_id' => $tenant->id, 'module' => $module],
                ['role' => $data['role'] ?? 'viewer', 'is_active' => true],
            );

            // Sync the module's plan permissions onto the new member.
            $subscription = \Modules\CORE\Models\TenantSubscription::where('tenant_id', $tenant->id)
                ->where('module', $module)
                ->where('billing_status', 'active')
                ->first();

            if ($subscription) {
                $permissions = $subscription->plan->permissions ?? [];
                if ($membership->isOwner()) {
                    $permissions[] = str_replace('_', '-', $module).'.acl';
                }
                $existingPerms = \Spatie\Permission\Models\Permission::whereIn('name', array_unique($permissions))->pluck('name');
                $user->givePermissionTo($existingPerms);
            }

            return $membership->load('user:id,name,email');
        });
    }

    /**
     * Update a member's module role / active state.
     */
    public function updateMember(Membership $membership, array $data): Membership
    {
        $role = $data['role'] ?? $membership->role;
        $isActive = $data['is_active'] ?? $membership->is_active;

        if ($membership->isOwner() && $role !== 'owner') {
            throw ValidationException::withMessages(['role' => 'The owner role cannot be changed.']);
        }

        $membership->forceFill([
            'role' => $role,
            'is_active' => (bool) $isActive,
        ])->save();

        // If deactivated, revoke the module permissions from the user.
        if (! $isActive) {
            $prefix = str_replace('_', '-', $membership->module);
            $ids = \Spatie\Permission\Models\Permission::where('name', 'like', "{$prefix}.%")->pluck('id');
            $membership->user->permissions()->detach($ids);
        }

        return $membership->fresh()->load('user:id,name,email');
    }

    /**
     * Remove a member from a module.
     */
    public function removeMember(Membership $membership): void
    {
        if ($membership->isOwner()) {
            throw ValidationException::withMessages(['member' => 'The owner cannot be removed.']);
        }

        $prefix = str_replace('_', '-', $membership->module);
        $ids = \Spatie\Permission\Models\Permission::where('name', 'like', "{$prefix}.%")->pluck('id');
        $membership->user->permissions()->detach($ids);

        $membership->delete();
    }
}
