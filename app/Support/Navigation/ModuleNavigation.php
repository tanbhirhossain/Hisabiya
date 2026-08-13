<?php

namespace App\Support\Navigation;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class ModuleNavigation
{
    /** @return array<string, mixed> */
    public function build(Request $request): array
    {
        if (! $request->user()) {
            return [
                'groups' => [],
                'moduleBoundaries' => $this->moduleBoundaries(),
            ];
        }

        $groups = collect($this->curatedGroups())
            ->map(function (array $group) use ($request): array {
                $items = collect($group['items'] ?? [])
                    ->map(fn (array $item): ?array => $this->resolveItem($item, $request))
                    ->filter()
                    ->values()
                    ->all();

                return array_merge($group, ['items' => $items]);
            })
            ->filter(fn (array $group): bool => count($group['items'] ?? []) > 0)
            ->values()
            ->all();

        return [
            'groups' => $groups,
            'moduleBoundaries' => $this->moduleBoundaries(),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function curatedGroups(): array
    {
        return [
            [
                'id' => 'overview',
                'title' => 'Overview',
                'icon' => 'LayoutDashboard',
                'description' => 'Command center and platform activity',
                'items' => [
                    ['title' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'LayoutDashboard', 'exact' => true],
                ],
            ],
            [
                'id' => 'workspace',
                'title' => 'Workspace',
                'icon' => 'Building2',
                'description' => 'Organisations that use the platform',
                'items' => [
                    ['title' => 'Tenants', 'route' => 'tenants.index', 'icon' => 'Building2', 'permission' => 'tenant.view'],
                    ['title' => 'Subscriptions', 'route' => 'subscriptions.index', 'icon' => 'CreditCard', 'permission' => 'permission.view'],
                    ['title' => 'Payment Gateways', 'route' => 'settings.payment-gateways', 'icon' => 'Landmark', 'permission' => 'permission.view'],
                    ['title' => 'Backups', 'route' => 'backup.index', 'icon' => 'Database', 'permission' => 'permission.view'],
                ],
            ],
            [
                'id' => 'personal',
                'title' => 'Personal Accounting',
                'icon' => 'Wallet',
                'description' => 'Personal finance module',
                'items' => [
                    ['title' => 'My Money', 'route' => 'personal.dashboard', 'icon' => 'LayoutDashboard', 'permission' => 'personal-accounting.view'],
                    ['title' => 'Transactions', 'route' => 'personal.transactions.index', 'icon' => 'ArrowLeftRight', 'permission' => 'personal-accounting.view'],
                    ['title' => 'Accounts', 'route' => 'personal.accounts.index', 'icon' => 'Wallet', 'permission' => 'personal-accounting.view'],
                    ['title' => 'Budgets', 'route' => 'personal.budgets.index', 'icon' => 'Target', 'permission' => 'personal-accounting.view'],
                    ['title' => 'Savings Goals', 'route' => 'personal.goals.index', 'icon' => 'PiggyBank', 'permission' => 'personal-accounting.view'],
                    ['title' => 'Loans', 'route' => 'personal.loans.index', 'icon' => 'HandCoins', 'permission' => 'personal-accounting.loans.view'],
                    ['title' => 'Reports', 'route' => 'personal.reports.index', 'icon' => 'BarChart3', 'permission' => 'personal-accounting.view'],
                ],
            ],
            [
                'id' => 'access',
                'title' => 'Access Control',
                'icon' => 'ShieldCheck',
                'description' => 'Users, roles and permissions',
                'items' => [
                    ['title' => 'Users', 'route' => 'users.index', 'icon' => 'Users', 'permission' => 'user.view'],
                    ['title' => 'Roles', 'route' => 'roles.index', 'icon' => 'ShieldCheck', 'permission' => 'role.view'],
                    ['title' => 'Permissions', 'route' => 'permissions.index', 'icon' => 'KeyRound', 'permission' => 'permission.view'],
                ],
            ],
            [
                'id' => 'audit',
                'title' => 'Audit',
                'icon' => 'Activity',
                'description' => 'Track everything happening on the platform',
                'items' => [
                    ['title' => 'Activity Logs', 'route' => 'activity-logs.index', 'icon' => 'Activity', 'permission' => 'activity-log.view'],
                ],
            ],
            [
                'id' => 'settings',
                'title' => 'Account Settings',
                'icon' => 'Settings',
                'description' => 'Profile, security, and preferences',
                'items' => [
                    ['title' => 'Profile', 'route' => 'profile.edit', 'icon' => 'UserRoundCog'],
                    ['title' => 'Password', 'route' => 'password.edit', 'icon' => 'KeyRound'],
                    ['title' => 'Appearance', 'route' => 'appearance', 'icon' => 'Palette'],
                ],
            ],
        ];
    }

    /** @return array<string, mixed>|null */
    private function resolveItem(array $item, Request $request): ?array
    {
        $routeName = (string) ($item['route'] ?? '');

        if ($routeName === '' || ! Route::has($routeName)) {
            return null;
        }

        $permission = $item['permission'] ?? null;
        if ($permission && $request->user()?->can($permission) === false) {
            return null;
        }

        return [
            'title' => $item['title'],
            'route' => $routeName,
            'href' => route($routeName, [], false),
            'icon' => $item['icon'] ?? 'Circle',
            'badge' => $item['badge'] ?? null,
            'exact' => (bool) ($item['exact'] ?? false),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function moduleBoundaries(): array
    {
        $modulesPath = base_path('modules');
        $modules = glob($modulesPath.'/*', GLOB_ONLYDIR) ?: [];

        return collect($modules)
            ->map(function (string $modulePath): array {
                $name = basename($modulePath);
                $routeFiles = glob($modulePath.'/Routes/*.php') ?: [];

                return [
                    'name' => $name,
                    'label' => Str::of($name)->replace('_', ' ')->lower()->headline()->toString(),
                    'path' => 'modules/'.$name,
                    'routeFiles' => collect($routeFiles)
                        ->map(fn (string $file): string => str_replace(base_path().'/', '', $file))
                        ->values()
                        ->all(),
                ];
            })
            ->sortBy('name')
            ->values()
            ->all();
    }
}
