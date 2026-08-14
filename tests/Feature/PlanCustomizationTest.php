<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\CORE\Models\SubscriptionPlan;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function planAdmin(): User
{
    Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

    return User::factory()->create()->assignRole('super-admin');
}

function seedPlanPermissions(): void
{
    Permission::firstOrCreate(['name' => 'personal-accounting.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'personal-accounting.backup', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'personal-accounting.loans.view', 'guard_name' => 'web']);
}

test('plan create form renders with permission groups', function () {
    seedPlanPermissions();
    $this->actingAs(planAdmin())
        ->get(route('subscriptions.plans.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('CORE::Subscriptions/PlanForm')
            ->where('plan', null)
            ->has('permission_groups')
            ->has('modules'));
});

test('a plan can be created with a custom permission set', function () {
    seedPlanPermissions();
    $admin = planAdmin();

    $this->actingAs($admin)->post(route('subscriptions.plans.store'), [
        'name' => 'My Custom Package',
        'slug' => 'custom-package',
        'module' => 'personal_accounting',
        'description' => 'A customized package',
        'price_monthly' => 499,
        'price_yearly' => 4990,
        'features' => ['Custom feature A', 'Custom feature B'],
        'permissions' => ['personal-accounting.view', 'personal-accounting.backup'],
        'is_active' => 1,
    ])->assertRedirect(route('subscriptions.index'));

    $plan = SubscriptionPlan::where('slug', 'custom-package')->first();
    expect($plan)->not->toBeNull();
    expect($plan->name)->toBe('My Custom Package');
    expect((float) $plan->price_monthly)->toBe(499.0);
    expect($plan->permissions)->toContain('personal-accounting.view');
    expect($plan->permissions)->toContain('personal-accounting.backup');
    expect($plan->permissions)->not->toContain('personal-accounting.loans.view');
    expect($plan->features)->toBe(['Custom feature A', 'Custom feature B']);
});

test('a plan can be edited to change its permissions', function () {
    seedPlanPermissions();
    $admin = planAdmin();

    $plan = SubscriptionPlan::create([
        'module' => 'personal_accounting',
        'name' => 'Original',
        'slug' => 'original-plan',
        'price_monthly' => 300,
        'permissions' => ['personal-accounting.view'],
    ]);

    $this->actingAs($admin)->get(route('subscriptions.plans.edit', $plan->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('CORE::Subscriptions/PlanForm')
            ->where('plan.name', 'Original')
            ->where('plan.permissions', ['personal-accounting.view']));

    $this->actingAs($admin)->put(route('subscriptions.plans.update', $plan->id), [
        'name' => 'Updated',
        'slug' => 'original-plan',
        'module' => 'personal_accounting',
        'description' => 'Changed',
        'price_monthly' => 599,
        'permissions' => ['personal-accounting.view', 'personal-accounting.loans.view'],
        'is_active' => 1,
    ])->assertRedirect(route('subscriptions.index'));

    $plan->refresh();
    expect($plan->name)->toBe('Updated');
    expect((float) $plan->price_monthly)->toBe(599.0);
    expect($plan->permissions)->toBe(['personal-accounting.view', 'personal-accounting.loans.view']);
});

test('a plan with subscriptions cannot be deleted', function () {
    seedPlanPermissions();
    $admin = planAdmin();

    $plan = SubscriptionPlan::create([
        'module' => 'personal_accounting',
        'name' => 'In Use',
        'slug' => 'in-use-plan',
        'price_monthly' => 100,
        'permissions' => ['personal-accounting.view'],
    ]);

    // Fake a subscription on it.
    $tenant = \Modules\CORE\Models\Tenant::create(['name' => 'T', 'slug' => 't-'.uniqid(), 'status' => 'active', 'plan' => 'free']);
    $plan->subscriptions()->create([
        'tenant_id' => $tenant->id,
        'module' => 'personal_accounting',
        'status' => 'active',
        'billing_status' => 'active',
        'starts_at' => now(),
        'ends_at' => now()->addYear(),
        'auto_renew' => true,
    ]);

    $this->actingAs($admin)->delete(route('subscriptions.plans.destroy', $plan->id))
        ->assertRedirect(route('subscriptions.index'));

    expect(SubscriptionPlan::find($plan->id))->not->toBeNull();
});

test('an unused plan can be deleted', function () {
    seedPlanPermissions();
    $admin = planAdmin();

    $plan = SubscriptionPlan::create([
        'module' => 'personal_accounting',
        'name' => 'Unused',
        'slug' => 'unused-plan',
        'price_monthly' => 100,
        'permissions' => ['personal-accounting.view'],
    ]);

    $this->actingAs($admin)->delete(route('subscriptions.plans.destroy', $plan->id))
        ->assertRedirect(route('subscriptions.index'));

    expect(SubscriptionPlan::find($plan->id))->toBeNull();
});

test('plan can store feature flags along with permissions', function () {
    seedPlanPermissions();
    $admin = planAdmin();

    $this->actingAs($admin)->post(route('subscriptions.plans.store'), [
        'name' => 'Flagged Plan',
        'slug' => 'flagged-plan',
        'module' => 'personal_accounting',
        'price_monthly' => 599,
        'features' => ['X'],
        'permissions' => ['personal-accounting.view', 'personal-accounting.backup'],
        'feature_flags' => [
            'module_users' => true,
            'backup' => true,
            'loans' => false,
        ],
        'is_active' => 1,
    ])->assertRedirect(route('subscriptions.index'));

    $plan = SubscriptionPlan::where('slug', 'flagged-plan')->first();
    expect($plan->feature_flags)->toBe([
        'module_users' => true,
        'backup' => true,
        'loans' => false,
    ]);
    expect($plan->hasFlag('module_users'))->toBeTrue();
    expect($plan->hasFlag('backup'))->toBeTrue();
    expect($plan->hasFlag('loans'))->toBeFalse();
    expect($plan->hasPermission('personal-accounting.backup'))->toBeTrue();
});

test('permission picker groups are scoped by module prefix', function () {
    seedPlanPermissions();
    Permission::firstOrCreate(['name' => 'user.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'tenant.view', 'guard_name' => 'web']);

    $this->actingAs(planAdmin())
        ->get(route('subscriptions.plans.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('CORE::Subscriptions/PlanForm')
            ->has('permission_groups', 3) // personal-accounting, user, tenant
            ->has('modules', 1)); // personal_accounting from the registry
});
