<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CORE\Models\Membership;
use Modules\CORE\Models\SubscriptionPlan;
use Modules\CORE\Models\Tenant;
use Modules\CORE\Services\CheckoutService;
use Modules\CORE\Services\ModuleMembershipService;
use Modules\CORE\Services\SubscriptionProvisioner;

uses(RefreshDatabase::class);

function moduleUserPlan(): SubscriptionPlan
{
    \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'personal-accounting.view', 'guard_name' => 'web']);
    \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'personal-accounting.acl', 'guard_name' => 'web']);

    return SubscriptionPlan::firstOrCreate(
        ['slug' => 'personal-accounting-pro'],
        [
            'module' => 'personal_accounting',
            'name' => 'PA Pro',
            'price_monthly' => 799,
            'permissions' => ['personal-accounting.view'], // NOTE: acl intentionally NOT here
            'feature_flags' => ['module_users' => true],
            'is_active' => true,
        ],
    );
}

function makeOwnerAndActivate(): array
{
    $plan = moduleUserPlan();
    $checkout = app(CheckoutService::class);
    $result = $checkout->prepare(['email' => 'owner@example.com', 'password' => 'secret1234', 'company_name' => 'Owner Co'], $plan);
    $subscription = $checkout->createPendingSubscription($result['tenant'], $plan, 'sslcommerz');
    app(SubscriptionProvisioner::class)->activate($subscription);

    return ['user' => $result['user'], 'tenant' => $result['tenant']];
}

test('owner can add a module member and grant a role', function () {
    ['user' => $owner, 'tenant' => $tenant] = makeOwnerAndActivate();

    $membership = app(ModuleMembershipService::class)->addMember($tenant, 'personal_accounting', [
        'name' => 'Rahim',
        'email' => 'rahim@example.com',
        'password' => 'secret1234',
        'role' => 'manager',
    ]);

    expect($membership->role)->toBe('manager');
    expect(Membership::where('module', 'personal_accounting')->count())->toBe(2); // owner + rahim
    // Manager gets the feature permission but NOT the owner-only acl.
    expect($membership->user->hasPermissionTo('personal-accounting.view'))->toBeTrue();
    expect($membership->user->hasPermissionTo('personal-accounting.acl'))->toBeFalse();
    // Owner has acl.
    expect($owner->hasPermissionTo('personal-accounting.acl'))->toBeTrue();
});

test('owner role cannot be changed or removed', function () {
    ['tenant' => $tenant] = makeOwnerAndActivate();
    $ownerMembership = Membership::where('module', 'personal_accounting')->where('role', 'owner')->first();
    $svc = app(ModuleMembershipService::class);

    $this->expectException(\Illuminate\Validation\ValidationException::class);
    $svc->updateMember($ownerMembership, ['role' => 'viewer']);
});

test('removing a member revokes their module permissions', function () {
    ['tenant' => $tenant] = makeOwnerAndActivate();
    $svc = app(ModuleMembershipService::class);
    $member = $svc->addMember($tenant, 'personal_accounting', [
        'name' => 'Rahim',
        'email' => 'rahim@example.com',
        'password' => 'secret1234',
        'role' => 'viewer',
    ]);

    expect($member->user->hasPermissionTo('personal-accounting.view'))->toBeTrue();

    $svc->removeMember($member->fresh());
    expect(Membership::find($member->id))->toBeNull();
    expect($member->user->fresh()->hasPermissionTo('personal-accounting.view'))->toBeFalse();
});

test('module users page is gated by acl permission', function () {
    $plan = moduleUserPlan();
    $checkout = app(CheckoutService::class);
    $result = $checkout->prepare(['email' => 'owner@example.com', 'password' => 'secret1234', 'company_name' => 'Owner Co'], $plan);
    $subscription = $checkout->createPendingSubscription($result['tenant'], $plan, 'sslcommerz');
    app(SubscriptionProvisioner::class)->activate($subscription);

    $owner = $result['user'];
    $owner->givePermissionTo('personal-accounting.acl');

    // Owner can access.
    $this->actingAs($owner)->get(route('personal.settings.users.index'))->assertOk();

    // A viewer member without acl is blocked.
    $viewer = \App\Models\User::create([
        'name' => 'Viewer',
        'email' => 'viewer@example.com',
        'password' => bcrypt('secret1234'),
        'tenant_id' => $result['tenant']->id,
    ]);
    $viewer->givePermissionTo('personal-accounting.view');
    $this->actingAs($viewer)->get(route('personal.settings.users.index'))->assertForbidden();
});
