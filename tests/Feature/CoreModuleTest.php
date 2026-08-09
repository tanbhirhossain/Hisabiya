<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\CORE\Database\Seeders\CORESeeder;

uses(RefreshDatabase::class);

function coreAdmin(): User
{
    Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

    return User::factory()->create()->assignRole('super-admin');
}

test('dashboard renders for authenticated user', function () {
    $this->actingAs(coreAdmin())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('CORE::Dashboard/Index'));
});

test('tenants index renders', function () {
    $this->actingAs(coreAdmin())
        ->get(route('tenants.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('CORE::Tenants/Index'));
});

test('tenants create renders', function () {
    $this->actingAs(coreAdmin())
        ->get(route('tenants.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('CORE::Tenants/Create'));
});

test('users index renders', function () {
    $this->actingAs(coreAdmin())
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('CORE::Users/Index'));
});

test('roles index renders', function () {
    $this->actingAs(coreAdmin())
        ->get(route('roles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('CORE::Roles/Index'));
});

test('permissions index renders', function () {
    $this->actingAs(coreAdmin())
        ->get(route('permissions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('CORE::Permissions/Index'));
});

test('activity logs index renders', function () {
    $this->actingAs(coreAdmin())
        ->get(route('activity-logs.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('CORE::ActivityLogs/Index'));
});

test('tenant CRUD lifecycle', function () {
    $admin = coreAdmin();

    $this->actingAs($admin)
        ->post(route('tenants.store'), [
            'name' => 'Test Business',
            'email' => 'business@example.com',
            'currency' => 'BDT',
            'timezone' => 'Asia/Dhaka',
            'status' => 'active',
            'plan' => 'pro',
        ])
        ->assertRedirect(route('tenants.index'));

    $tenant = Modules\CORE\Models\Tenant::where('email', 'business@example.com')->firstOrFail();
    expect($tenant->slug)->toBe('test-business');
    expect($tenant->plan)->toBe('pro');

    $this->actingAs($admin)
        ->put(route('tenants.update', $tenant), ['name' => 'Renamed', 'currency' => 'BDT', 'timezone' => 'Asia/Dhaka', 'status' => 'trial', 'plan' => 'starter'])
        ->assertRedirect(route('tenants.index'));

    expect($tenant->fresh()->name)->toBe('Renamed');

    $this->actingAs($admin)->delete(route('tenants.destroy', $tenant))->assertRedirect(route('tenants.index'));
    expect(Modules\CORE\Models\Tenant::find($tenant->id))->toBeNull();
});

test('role CRUD with permissions', function () {
    $admin = coreAdmin();
    $permission = Spatie\Permission\Models\Permission::create(['name' => 'billing.view', 'guard_name' => 'web']);

    $this->actingAs($admin)
        ->post(route('roles.store'), ['name' => 'billing', 'permissions' => [$permission->id]])
        ->assertRedirect(route('roles.index'));

    $role = Spatie\Permission\Models\Role::findByName('billing');
    expect($role->hasPermissionTo('billing.view'))->toBeTrue();
});

test('users without permission cannot access protected resources', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('tenants.index'))->assertForbidden();
    $this->actingAs($user)->get(route('users.index'))->assertForbidden();
});

test('super-admin bypasses permission gates', function () {
    $this->actingAs(coreAdmin())->get(route('tenants.index'))->assertOk();
});

test('user management service creates user with roles', function () {
    $role = Spatie\Permission\Models\Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);

    $user = app(Modules\CORE\Services\UserService::class)->create(
        ['name' => 'New Person', 'email' => 'person@example.com', 'password' => 'secret1234'],
        [$role->id],
    );

    expect($user->hasRole('manager'))->toBeTrue();
});
