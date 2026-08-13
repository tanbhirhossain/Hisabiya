<?php

namespace Modules\CORE\Services;

use Modules\CORE\Models\CoreSetting;
use Modules\CORE\Models\TenantSubscription;

/**
 * Coordinates how a subscription becomes active, honouring the super-admin
 * free-plan activation mode (instant vs approval) and online vs manual payment.
 */
class SubscriptionActivationService
{
    public function __construct(
        private readonly SubscriptionProvisioner $provisioner,
    ) {
    }

    /**
     * Free-plan activation mode set by the CORE super admin.
     */
    public function freePlanMode(): string
    {
        return CoreSetting::get('free_plan_activation', 'instant'); // instant | approval
    }

    /**
     * Decide the initial billing state for a subscription given its plan.
     */
    public function determineInitialState(TenantSubscription $subscription, string $provider): string
    {
        // Free plan (price 0) follows the admin's activation mode.
        if ((float) $subscription->plan->price_monthly <= 0) {
            return $this->freePlanMode() === 'instant' ? 'active' : 'pending';
        }

        // Paid plans are pending until payment confirms.
        return 'pending';
    }

    /**
     * Activate a subscription (from webhook or admin approval).
     */
    public function activate(TenantSubscription $subscription): TenantSubscription
    {
        return $this->provisioner->activate($subscription);
    }

    /**
     * The dashboard the user should land on after login / subscription.
     */
    public function routeForUser(int $userId): string
    {
        $modules = $this->provisioner->activeModulesForUser($userId);

        if (count($modules) === 1) {
            $module = $modules[0];

            return match ($module) {
                'personal_accounting' => '/personal/dashboard',
                default => '/dashboard',
            };
        }

        if (count($modules) > 1) {
            return '/dashboard'; // chooser screen (could be added later)
        }

        return '/dashboard';
    }
}
