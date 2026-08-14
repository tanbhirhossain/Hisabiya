<?php

namespace Modules\CORE\Services;

use Illuminate\Support\Facades\Route;

/**
 * Central registry of sellable subscription modules. Each entry describes a
 * module that a tenant can subscribe to and the user can "enter" once they
 * hold an active subscription. The registry is the single source of truth for
 * module metadata used by the module chooser and login routing.
 */
class ModuleRegistry
{
    /**
     * All registered modules, keyed by module key (underscore form used in
     * memberships/subscriptions).
     *
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return [
            'personal_accounting' => [
                'key' => 'personal_accounting',
                'label' => 'Personal Accounting',
                'tagline' => 'Your money, mastered',
                'description' => 'Track income and expenses, plan budgets, grow savings goals, manage loans and read clear reports — all in one clean workspace.',
                'icon' => 'Wallet',
                'color' => '#6366f1',
                'route' => 'personal.dashboard',
                'features' => [
                    'Income & expense tracking',
                    'Budgets with live alerts',
                    'Savings goals',
                    'Loans & EMI tracking',
                    'Reports & CSV import',
                ],
            ],
        ];
    }

    /**
     * Whether a module key is a registered, sellable module.
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    /**
     * Metadata for a single module, or null if not registered.
     *
     * @return array<string, mixed>|null
     */
    public function get(string $key): ?array
    {
        return $this->all()[$key] ?? null;
    }

    /**
     * The landing route name for a module, or null.
     */
    public function routeFor(string $key): ?string
    {
        $module = $this->get($key);
        $route = $module['route'] ?? null;

        return $route !== null && Route::has($route) ? $route : null;
    }

    /**
     * Resolve a set of active module keys to their display metadata, in the
     * order given, dropping any keys that are not registered.
     *
     * @param  array<int, string>  $keys
     * @return array<int, array<string, mixed>>
     */
    public function available(array $keys): array
    {
        $modules = $this->all();

        return collect($keys)
            ->filter(fn (string $key): bool => isset($modules[$key]))
            ->map(function (string $key) use ($modules): array {
                $meta = $modules[$key];
                $route = $meta['route'] ?? null;

                return array_merge($meta, [
                    'href' => $route !== null && Route::has($route) ? route($route) : null,
                ]);
            })
            ->values()
            ->all();
    }
}
