<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    // Active plans grouped by module, so the landing page's pricing section
    // always reflects the current (DB) packages without being hardcoded.
    $plans = \Modules\CORE\Models\SubscriptionPlan::query()
        ->where('is_active', true)
        ->orderBy('module')
        ->orderBy('price_monthly')
        ->get(['id', 'name', 'slug', 'module', 'description', 'price_monthly', 'price_yearly', 'features', 'permissions', 'is_active']);

    $modules = app(\Modules\CORE\Services\ModuleRegistry::class)->all();

    $grouped = collect($modules)
        ->map(fn ($meta, $key) => [
            'key' => $key,
            'label' => $meta['label'],
            'tagline' => $meta['tagline'] ?? '',
            'plans' => $plans->where('module', $key)->values()->all(),
        ])
        ->filter(fn ($m) => count($m['plans']) > 0)
        ->values()
        ->all();

    return Inertia::render('Landing', [
        'modules' => $grouped,
    ]);
})->name('home');

// Admin dashboard is owned by the CORE module (modules/CORE/Routes/web.php).

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
