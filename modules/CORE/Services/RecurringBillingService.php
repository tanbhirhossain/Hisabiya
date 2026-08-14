<?php

namespace Modules\CORE\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\CORE\Models\Membership;
use Modules\CORE\Models\Payment;
use Modules\CORE\Models\TenantSubscription;
use Modules\CORE\Notifications\SubscriptionExpiredNotification;
use Modules\CORE\Notifications\SubscriptionRenewalNotification;

/**
 * Drives the recurring billing lifecycle:
 *  - When a subscription term ends and auto-renew is on, we generate a renewal
 *    invoice (a pending payment for the next period) and put the subscription
 *    into `past_due` with a grace window so the owner can pay to continue.
 *  - If the renewal isn't paid within the grace window (or auto-renew is off),
 *    the subscription is expired and the module access is revoked.
 *
 * Scheduled daily by the Laravel scheduler (see routes/console.php).
 */
class RecurringBillingService
{
    /** Grace period (days) a subscriber keeps access after a term ends. */
    public const GRACE_DAYS = 7;

    public function __construct(
        private readonly PaymentService $payments,
        private readonly SubscriptionService $subscriptions,
    ) {
    }

    /**
     * Subscriptions whose active term has ended and which are eligible for a
     * renewal invoice (auto-renew on, not already past-due).
     *
     * @return \Illuminate\Support\Collection<int, TenantSubscription>
     */
    public function subscriptionsDueForRenewal()
    {
        return TenantSubscription::query()
            ->where('status', 'active')
            ->where('auto_renew', true)
            ->where('billing_status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->with('tenant')
            ->get();
    }

    /**
     * Generate renewal invoices for every due subscription and move them to
     * `past_due` within their grace window. Returns the number renewed/flagged.
     */
    public function renewDue(): int
    {
        $count = 0;

        foreach ($this->subscriptionsDueForRenewal() as $subscription) {
            $this->issueRenewalInvoice($subscription);
            $count++;
        }

        return $count;
    }

    /**
     * Create a pending renewal payment for the next period, move the
     * subscription to `past_due`, open the grace window and notify the owner.
     */
    public function issueRenewalInvoice(TenantSubscription $subscription): Payment
    {
        $periodLength = $this->periodLength($subscription);
        $nextStart = $subscription->ends_at ? Carbon::parse($subscription->ends_at) : now();
        $nextEnd = $nextStart->copy()->add($periodLength['unit'], $periodLength['count']);

        $ref = Str::uuid()->toString();
        $payment = $this->payments->createPaymentRecord($subscription, (string) ($subscription->provider ?: 'sslcommerz'), $ref);
        $payment->forceFill([
            'period_start' => $nextStart,
            'period_end' => $nextEnd,
            'is_renewal' => true,
            'notes' => 'Renewal for '.$subscription->plan?->name,
        ])->save();

        $subscription->forceFill([
            'billing_status' => 'past_due',
            'grace_ends_at' => $nextStart->copy()->addDays(self::GRACE_DAYS),
            'last_renewed_at' => now(),
        ])->save();

        $this->notifyOwner($subscription, new SubscriptionRenewalNotification(
            (string) ($subscription->plan?->name ?? 'subscription'),
            (float) $payment->amount,
            $payment->id,
        ));

        return $payment;
    }

    /**
     * Expire subscriptions whose grace window has lapsed (renewal unpaid) or
     * which are not auto-renewing past their term end. Revokes module access.
     * Returns the number expired.
     */
    public function expire(): int
    {
        $count = 0;

        // Past-due subscriptions whose grace window has closed.
        $pastDue = TenantSubscription::query()
            ->where('status', 'active')
            ->where('billing_status', 'past_due')
            ->whereNotNull('grace_ends_at')
            ->where('grace_ends_at', '<', now())
            ->get();

        foreach ($pastDue as $subscription) {
            $this->expireSubscription($subscription, 'Renewal payment not received within the grace period.');
            $count++;
        }

        // Non-auto-renew subscriptions past their term end.
        $nonRenewing = TenantSubscription::query()
            ->where('status', 'active')
            ->where('auto_renew', false)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->get();

        foreach ($nonRenewing as $subscription) {
            $this->expireSubscription($subscription, 'Subscription term ended.');
            $count++;
        }

        return $count;
    }

    /**
     * Expire a single subscription and revoke the module permissions.
     */
    public function expireSubscription(TenantSubscription $subscription, string $reason): void
    {
        $subscription->forceFill([
            'status' => 'expired',
            'billing_status' => 'expired',
            'auto_renew' => false,
        ])->save();

        $this->subscriptions->syncPermissionsToUsers($subscription->tenant, $subscription->module, []);

        $this->notifyOwner($subscription, new SubscriptionExpiredNotification(
            (string) ($subscription->plan?->name ?? 'subscription'),
            $reason,
        ));

        activity('subscription')
            ->performedOn($subscription)
            ->event('expired')
            ->log("Subscription expired: {$reason}");
    }

    /**
     * Determine the billing period length from the subscription's existing
     * term (defaults to a full year if unset).
     *
     * @return array{count: int, unit: string}
     */
    private function periodLength(TenantSubscription $subscription): array
    {
        if ($subscription->starts_at && $subscription->ends_at) {
            $diff = Carbon::parse($subscription->starts_at)->diff(Carbon::parse($subscription->ends_at));
            $totalDays = ($diff->y * 365) + ($diff->m * 30) + $diff->d;

            if ($totalDays >= 360) {
                return ['count' => 1, 'unit' => 'year'];
            }
            if ($totalDays >= 25) {
                return ['count' => 1, 'unit' => 'month'];
            }
        }

        return ['count' => 1, 'unit' => 'year'];
    }

    /**
     * Notify the module's owner for a subscription.
     */
    private function notifyOwner(TenantSubscription $subscription, object $notification): void
    {
        $owner = Membership::where('tenant_id', $subscription->tenant_id)
            ->where('module', $subscription->module)
            ->where('role', 'owner')
            ->where('is_active', true)
            ->first();

        $owner?->user?->notify($notification);
    }
}
