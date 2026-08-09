<?php

namespace Modules\CORE\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
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

        foreach ($permissions->unique() as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
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
