<?php

namespace Modules\CORE\Services;

use App\Models\User;
use Modules\CORE\Models\Tenant;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DashboardService
{
    /**
     * Monthly price (in BDT) for each plan. Used to compute MRR.
     */
    private const PLANS = [
        'free' => 0,
        'starter' => 1999,
        'pro' => 4999,
        'enterprise' => 14999,
    ];

    public function stats(): array
    {
        $tenants = Tenant::count();
        $users = User::count();
        $activeTenants = Tenant::where('status', 'active')->count();
        $revenue = $this->mrr();

        return [
            'tenants' => $tenants,
            'users' => $users,
            'roles' => Role::count(),
            'permissions' => Permission::count(),
            'activeTenants' => $activeTenants,
            'inactiveUsers' => User::where('is_active', false)->count(),
            'revenue' => $revenue,
            'revenueDelta' => $this->revenueDelta(),
            'trialTenants' => Tenant::where('status', 'trial')->count(),
            'suspendedTenants' => Tenant::where('status', 'suspended')->count(),
            'tenantConversion' => $tenants > 0 ? (int) round(($activeTenants / $tenants) * 100) : 0,
            'recentActivity' => Activity::with('causer:id,name')->latest()->limit(8)->get(),
            'recentTenants' => Tenant::latest()->limit(5)->get(['id', 'name', 'status', 'plan', 'created_at', 'email']),
        ];
    }

    public function growth(): array
    {
        // Last 14 days: new tenants + new users (for the area chart).
        return collect(range(13, 0))->map(function (int $offset): array {
            $date = now()->subDays($offset);

            return [
                'label' => $date->format('M j'),
                'tenants' => Tenant::whereDate('created_at', $date)->count(),
                'users' => User::whereDate('created_at', $date)->count(),
            ];
        })->all();
    }

    public function revenueSeries(): array
    {
        // Last 12 months of MRR, computed from tenants created up to each month end.
        return collect(range(11, 0))->map(function (int $offset): array {
            $month = now()->subMonths($offset)->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $mrr = Tenant::query()
                ->where('created_at', '<=', $end)
                ->whereIn('status', ['active', 'trial'])
                ->get()
                ->sum(fn (Tenant $tenant) => $this->planPrice($tenant->plan));

            return [
                'label' => $month->format('M'),
                'value' => (int) $mrr,
            ];
        })->all();
    }

    public function statusBreakdown(): array
    {
        $statuses = ['active', 'trial', 'suspended'];

        return collect($statuses)->map(function (string $status): array {
            return [
                'label' => $status,
                'value' => Tenant::where('status', $status)->count(),
            ];
        })->filter(fn ($item) => $item['value'] > 0)->values()->all();
    }

    public function planBreakdown(): array
    {
        $plans = ['free', 'starter', 'pro', 'enterprise'];

        return collect($plans)->map(function (string $plan): array {
            return [
                'label' => $plan,
                'value' => Tenant::where('plan', $plan)->count(),
            ];
        })->filter(fn ($item) => $item['value'] > 0)->values()->all();
    }

    public function topTenants(int $limit = 5): array
    {
        return Tenant::query()
            ->withCount('users')
            ->where('status', '!=', 'suspended')
            ->orderByDesc('users_count')
            ->limit($limit)
            ->get(['id', 'name', 'plan', 'status', 'email'])
            ->map(fn (Tenant $tenant) => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'email' => $tenant->email,
                'plan' => $tenant->plan,
                'users_count' => $tenant->users_count,
            ])
            ->all();
    }

    public function quickActions(): array
    {
        return [
            ['title' => 'Add tenant', 'route' => 'tenants.create', 'description' => 'Onboard a new organisation'],
            ['title' => 'Invite user', 'route' => 'users.create', 'description' => 'Add a platform user'],
            ['title' => 'Create role', 'route' => 'roles.create', 'description' => 'Define a new access level'],
        ];
    }

    private function mrr(): int
    {
        return (int) Tenant::query()
            ->whereIn('status', ['active', 'trial'])
            ->get()
            ->sum(fn (Tenant $tenant) => $this->planPrice($tenant->plan));
    }

    private function revenueDelta(): float
    {
        $currentMonth = Tenant::query()
            ->whereIn('status', ['active', 'trial'])
            ->where('created_at', '>=', now()->startOfMonth())
            ->get()
            ->sum(fn (Tenant $tenant) => $this->planPrice($tenant->plan));

        $lastMonth = Tenant::query()
            ->whereIn('status', ['active', 'trial'])
            ->whereBetween('created_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])
            ->get()
            ->sum(fn (Tenant $tenant) => $this->planPrice($tenant->plan));

        if ($lastMonth <= 0) {
            return $currentMonth > 0 ? 100.0 : 0.0;
        }

        return round((($currentMonth - $lastMonth) / $lastMonth) * 100, 1);
    }

    private function planPrice(?string $plan): int
    {
        return self::PLANS[$plan ?? 'free'] ?? 0;
    }
}
