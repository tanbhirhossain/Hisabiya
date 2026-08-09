<?php

namespace Modules\CORE\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\CORE\Models\SubscriptionPlan;
use Modules\CORE\Models\Tenant;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CORESeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->seedPermissions();
        $this->seedRoles();
        $this->seedTenants();
        $this->seedSubscriptionPlans();
        $this->seedSuperAdmin();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    private function seedPermissions(): void
    {
        $abilities = ['view', 'create', 'update', 'delete'];
        $resources = ['user', 'role', 'permission', 'tenant'];

        $permissions = collect(['dashboard.view', 'activity-log.view']);

        foreach ($resources as $resource) {
            foreach ($abilities as $ability) {
                $permissions->push("{$resource}.{$ability}");
            }
        }

        // Permissions for the sellable Personal Accounting module.
        $personalAccountingPermissions = collect([
            'personal-accounting.view',
            'personal-accounting.transactions.view',
            'personal-accounting.transactions.create',
            'personal-accounting.transactions.update',
            'personal-accounting.transactions.delete',
            'personal-accounting.accounts.view',
            'personal-accounting.accounts.manage',
            'personal-accounting.budgets.view',
            'personal-accounting.budgets.manage',
            'personal-accounting.goals.view',
            'personal-accounting.goals.manage',
            'personal-accounting.reports.view',
        ]);

        foreach ($personalAccountingPermissions as $permission) {
            $permissions->push($permission);
        }

        foreach ($permissions->unique() as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    private function seedSubscriptionPlans(): void
    {
        $plans = [
            [
                'module' => 'personal_accounting',
                'name' => 'Personal Accounting Lite',
                'slug' => 'personal-accounting-lite',
                'description' => 'Core personal money tracking for individuals.',
                'price_monthly' => 399,
                'price_yearly' => 3990,
                'features' => [
                    'Unlimited income & expense tracking',
                    'Cash & mobile banking accounts',
                    'Monthly budgets',
                    'Recurring transactions',
                ],
                'permissions' => [
                    'personal-accounting.view',
                    'personal-accounting.transactions.view',
                    'personal-accounting.transactions.create',
                    'personal-accounting.transactions.update',
                    'personal-accounting.transactions.delete',
                    'personal-accounting.accounts.view',
                    'personal-accounting.accounts.manage',
                    'personal-accounting.budgets.view',
                    'personal-accounting.budgets.manage',
                    'personal-accounting.goals.view',
                    'personal-accounting.reports.view',
                ],
            ],
            [
                'module' => 'personal_accounting',
                'name' => 'Personal Accounting Pro',
                'slug' => 'personal-accounting-pro',
                'description' => 'Advanced analytics, savings goals and loans.',
                'price_monthly' => 799,
                'price_yearly' => 7990,
                'features' => [
                    'Everything in Lite',
                    'Savings goals & projections',
                    'Advanced reports & analytics',
                    'Personal loans tracking',
                    'Priority support',
                ],
                'permissions' => [
                    'personal-accounting.view',
                    'personal-accounting.transactions.view',
                    'personal-accounting.transactions.create',
                    'personal-accounting.transactions.update',
                    'personal-accounting.transactions.delete',
                    'personal-accounting.accounts.view',
                    'personal-accounting.accounts.manage',
                    'personal-accounting.budgets.view',
                    'personal-accounting.budgets.manage',
                    'personal-accounting.goals.view',
                    'personal-accounting.goals.manage',
                    'personal-accounting.reports.view',
                ],
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::firstOrCreate(['slug' => $plan['slug']], $plan);
        }
    }

    private function seedRoles(): void
    {
        $all = Permission::pluck('name', 'id');

        $roleMap = [
            'super-admin' => $all,
            'admin' => $all->filter(fn ($name) => str($name)->startsWith(['dashboard', 'user', 'role', 'permission', 'tenant', 'activity-log'])),
            'manager' => $all->filter(fn ($name) => str($name)->startsWith(['dashboard', 'tenant', 'activity-log'])),
            'user' => collect(['dashboard.view']),
        ];

        foreach ($roleMap as $name => $permissions) {
            $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            $role->syncPermissions($permissions->keys());
        }
    }

    private function seedTenants(): void
    {
        $tenants = [
            [
                'slug' => 'hisabiya-demo',
                'name' => 'Hisabiya Demo',
                'email' => 'demo@hisabiya.test',
                'phone' => '+8801700000000',
                'address' => 'Gulshan, Dhaka, Bangladesh',
                'currency' => 'BDT',
                'timezone' => 'Asia/Dhaka',
                'status' => 'active',
                'plan' => 'pro',
            ],
            [
                'slug' => 'gulshan-mart',
                'name' => 'Gulshan Mart',
                'email' => 'sales@gulshanmart.com',
                'phone' => '+8801711111111',
                'address' => 'Gulshan-2, Dhaka',
                'currency' => 'BDT',
                'timezone' => 'Asia/Dhaka',
                'status' => 'active',
                'plan' => 'starter',
            ],
            [
                'slug' => 'dhaka-tex',
                'name' => 'Dhaka Textiles Ltd.',
                'email' => 'accounts@dhakatex.com',
                'phone' => '+8801722222222',
                'address' => 'Kawran Bazar, Dhaka',
                'currency' => 'BDT',
                'timezone' => 'Asia/Dhaka',
                'status' => 'active',
                'plan' => 'enterprise',
            ],
            [
                'slug' => 'bazar-super',
                'name' => 'Bazar Super Store',
                'email' => 'info@bazarsuper.com',
                'phone' => '+8801733333333',
                'address' => 'Dhanmondi, Dhaka',
                'currency' => 'BDT',
                'timezone' => 'Asia/Dhaka',
                'status' => 'trial',
                'plan' => 'starter',
            ],
            [
                'slug' => 'fresh-foods',
                'name' => 'Fresh Foods BD',
                'email' => 'hello@freshfoods.bd',
                'phone' => '+8801744444444',
                'address' => 'Mirpur, Dhaka',
                'currency' => 'BDT',
                'timezone' => 'Asia/Dhaka',
                'status' => 'active',
                'plan' => 'free',
            ],
        ];

        foreach ($tenants as $tenant) {
            Tenant::firstOrCreate(['slug' => $tenant['slug']], $tenant);
        }
    }

    private function seedSuperAdmin(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@hisabiya.test'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
                'tenant_id' => Tenant::first()?->id,
            ]
        );

        $admin->syncRoles('super-admin');
    }
}
