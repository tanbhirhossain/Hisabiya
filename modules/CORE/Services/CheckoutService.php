<?php

namespace Modules\CORE\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\CORE\Models\Membership;
use Modules\CORE\Models\SubscriptionPlan;
use Modules\CORE\Models\Tenant;
use Modules\CORE\Models\TenantSubscription;

/**
 * Creates the identity + tenant + owner membership for a new subscription in one
 * atomic, idempotent operation. Called from the public checkout flow.
 *
 * Rules:
 *  - A user is keyed by email (no pre-registration needed).
 *  - Each new registrant gets their own tenant (auto-named from company/name).
 *  - The registrant becomes the module's Owner membership.
 *  - A subscription is created in `pending` billing state; provisioning happens
 *    after payment confirmation.
 */
class CheckoutService
{
    public function __construct(private readonly SubscriptionService $subscriptions)
    {
    }

    /**
     * Create (or find) the user + tenant + owner membership for a checkout.
     *
     * @return array{user: User, tenant: Tenant, membership: Membership}
     */
    public function prepare(array $data, SubscriptionPlan $plan): array
    {
        return DB::transaction(function () use ($data, $plan): array {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'] ?? Str::before($data['email'], '@'),
                    'company_name' => $data['company_name'] ?? null,
                    'password' => Hash::make($data['password']),
                    'is_active' => true,
                ],
            );

            // If the user already has a tenant, reuse it; otherwise create one.
            $tenant = $user->tenant_id
                ? Tenant::find($user->tenant_id)
                : $this->createTenant($user, $data['company_name'] ?? null);

            if (! $user->tenant_id) {
                $user->forceFill(['tenant_id' => $tenant->id])->save();
            }

            // Owner membership for this module.
            $membership = Membership::updateOrCreate(
                ['user_id' => $user->id, 'tenant_id' => $tenant->id, 'module' => $plan->module],
                ['role' => 'owner', 'is_active' => true],
            );

            return ['user' => $user, 'tenant' => $tenant, 'membership' => $membership];
        });
    }

    /**
     * Idempotently create a subscription in `pending` billing state.
     */
    public function createPendingSubscription(Tenant $tenant, SubscriptionPlan $plan, string $provider, ?string $checkoutSessionId = null): TenantSubscription
    {
        $subscription = TenantSubscription::updateOrCreate(
            ['tenant_id' => $tenant->id, 'module' => $plan->module],
            [
                'plan_id' => $plan->id,
                'status' => 'active',          // entitlement only after billing is active
                'billing_status' => 'pending',
                'provider' => $provider,
                'checkout_session_id' => $checkoutSessionId,
                'starts_at' => now(),
                'ends_at' => now()->addYear(),
                'auto_renew' => true,
            ],
        );

        return $subscription;
    }

    private function createTenant(User $user, ?string $companyName): Tenant
    {
        $base = $companyName ?: ($user->name ?: Str::before($user->email, '@'));
        $slug = Str::slug($base) ?: 'tenant-'.Str::lower(Str::random(6));
        $slug = $this->uniqueSlug($slug);

        return Tenant::create([
            'name' => $base,
            'slug' => $slug,
            'email' => $user->email,
            'currency' => 'BDT',
            'timezone' => 'Asia/Dhaka',
            'status' => 'active',
            'plan' => 'free',
        ]);
    }

    private function uniqueSlug(string $slug): string
    {
        $base = $slug;
        $i = 1;

        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}
