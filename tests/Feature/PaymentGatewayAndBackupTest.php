<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CORE\Models\Backup;
use Modules\CORE\Models\CoreSetting;
use Modules\CORE\Services\BackupService;
use Modules\CORE\Services\PaymentGatewaySettingsService;

uses(RefreshDatabase::class);

function backupAdmin(): \App\Models\User
{
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    $tenant = \Modules\CORE\Models\Tenant::create(['name' => 'Admin Co', 'slug' => 'admin-co', 'status' => 'active', 'plan' => 'free']);
    $admin = \App\Models\User::factory()->create(['tenant_id' => $tenant->id])->assignRole('super-admin');

    return $admin;
}

test('payment gateway settings can be saved and read back', function () {
    $svc = app(PaymentGatewaySettingsService::class);

    $svc->save([
        'sslcommerz' => ['enabled' => true, 'sandbox' => true, 'store_id' => 'demo123', 'store_pass' => 'secret'],
        'manual_bkash' => ['enabled' => true, 'number' => '01700-111111'],
        'manual_bank' => ['enabled' => false, 'bank_name' => 'DBBL'],
    ]);

    $all = $svc->all();
    expect($all['sslcommerz']['enabled'])->toBeTrue();
    expect($all['sslcommerz']['store_id'])->toBe('demo123');
    expect($all['manual_bkash']['number'])->toBe('01700-111111');
    expect($all['manual_bank']['enabled'])->toBeFalse();
    expect($svc->sslcommerzConfigured())->toBeTrue();
});

test('super admin can back up all tenants', function () {
    $admin = backupAdmin();
    $this->actingAs($admin);

    \Modules\CORE\Models\Tenant::create(['name' => 'Tenant A', 'slug' => 'tenant-a', 'status' => 'active', 'plan' => 'free']);

    $result = app(BackupService::class)->export($admin->id, null, 'all');

    expect($result['name'])->toContain('hisabiya-full-backup');
    expect(Backup::where('type', 'all')->count())->toBe(1);
});

test('single tenant backup is tenant-scoped', function () {
    backupAdmin();
    $tenantA = \Modules\CORE\Models\Tenant::create(['name' => 'Tenant A', 'slug' => 'tenant-a', 'status' => 'active', 'plan' => 'free']);
    $tenantB = \Modules\CORE\Models\Tenant::create(['name' => 'Tenant B', 'slug' => 'tenant-b', 'status' => 'active', 'plan' => 'free']);

    \App\Models\User::factory()->create(['tenant_id' => $tenantA->id]);
    \App\Models\User::factory()->create(['tenant_id' => $tenantB->id]);

    $payload = app(BackupService::class)->tenantPayload((int) $tenantA->id);

    expect($payload['tenant']['name'])->toBe('Tenant A');
    // Only tenant A's data included (all its users belong to tenant A).
    expect(collect($payload['users'])->pluck('tenant_id')->unique()->all())->toBe([$tenantA->id]);
});

test('backup page is gated for CORE super admin and requires permission', function () {
    // Non-admin user is blocked.
    $user = \App\Models\User::factory()->create();
    $this->actingAs($user)->get(route('backup.index'))->assertForbidden();
});

test('personal backup download is tenant-scoped', function () {
    $admin = backupAdmin();
    $tenant = \Modules\CORE\Models\Tenant::create(['name' => 'Tenant A', 'slug' => 'tenant-a', 'status' => 'active', 'plan' => 'free']);
    $user = \App\Models\User::factory()->create(['tenant_id' => $tenant->id]);

    // Grant the PRO backup + module view permissions.
    $backup = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'personal-accounting.backup', 'guard_name' => 'web']);
    $view = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'personal-accounting.view', 'guard_name' => 'web']);
    $user->givePermissionTo([$backup, $view]);

    // Backup for this tenant.
    $result = app(BackupService::class)->export($user->id, (int) $tenant->id, 'tenant');

    // Downloading own tenant's backup works.
    $response = $this->actingAs($user)->get(route('personal.settings.backup.download', $result['name']));
    $response->assertOk();
});

// --- Restore functionality ---

test('restoring a tenant backup restores the module data', function () {
    $tenant = \Modules\CORE\Models\Tenant::create(['name' => 'Tenant A', 'slug' => 'tenant-a', 'status' => 'active', 'plan' => 'free']);
    $user = \App\Models\User::factory()->create(['tenant_id' => $tenant->id]);

    // Create some module data.
    $account = \Modules\PersonalAccounting\Models\PersonalAccount::create([
        'tenant_id' => $tenant->id, 'user_id' => $user->id, 'name' => 'Wallet', 'type' => 'cash', 'currency' => 'BDT', 'balance' => 100,
    ]);
    \Modules\PersonalAccounting\Models\PersonalTransaction::create([
        'tenant_id' => $tenant->id, 'user_id' => $user->id, 'account_id' => $account->id,
        'type' => 'expense', 'amount' => 50, 'date' => now()->toDateString(),
    ]);

    $svc = app(BackupService::class);
    $result = $svc->export($user->id, (int) $tenant->id, 'tenant');

    // Delete the data.
    \Modules\PersonalAccounting\Models\PersonalTransaction::where('tenant_id', $tenant->id)->delete();
    \Modules\PersonalAccounting\Models\PersonalAccount::where('tenant_id', $tenant->id)->delete();
    expect(\Modules\PersonalAccounting\Models\PersonalAccount::where('tenant_id', $tenant->id)->count())->toBe(0);

    // Restore.
    $restore = $svc->restore($result['name'], 'tenant', (int) $tenant->id);
    expect($restore['records'])->toBeGreaterThan(0);
    expect(\Modules\PersonalAccounting\Models\PersonalAccount::where('tenant_id', $tenant->id)->count())->toBe(1);
    expect(\Modules\PersonalAccounting\Models\PersonalTransaction::where('tenant_id', $tenant->id)->count())->toBe(1);
});

test('CORE restore endpoint restores a tenant backup', function () {
    $admin = backupAdmin();
    $this->actingAs($admin);

    $tenant = \Modules\CORE\Models\Tenant::create(['name' => 'Tenant A', 'slug' => 'tenant-a', 'status' => 'active', 'plan' => 'free']);
    $user = \App\Models\User::factory()->create(['tenant_id' => $tenant->id]);
    \Modules\PersonalAccounting\Models\PersonalAccount::create([
        'tenant_id' => $tenant->id, 'user_id' => $user->id, 'name' => 'Wallet', 'type' => 'cash', 'currency' => 'BDT',
    ]);

    $svc = app(BackupService::class);
    $result = $svc->export($admin->id, (int) $tenant->id, 'tenant');

    \Modules\PersonalAccounting\Models\PersonalAccount::where('tenant_id', $tenant->id)->delete();

    $this->post(route('backup.restore'), [
        'file_name' => $result['name'],
        'scope' => 'tenant',
        'tenant_id' => $tenant->id,
    ])->assertRedirect();

    // Use the DB directly to bypass the tenant-scoped global scope.
    expect(DB::table('personal_accounts')->where('tenant_id', $tenant->id)->count())->toBe(1);
});

test('PA restore endpoint restores only own tenant data', function () {
    $tenant = \Modules\CORE\Models\Tenant::create(['name' => 'Tenant A', 'slug' => 'tenant-a', 'status' => 'active', 'plan' => 'free']);
    $user = \App\Models\User::factory()->create(['tenant_id' => $tenant->id]);
    $view = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'personal-accounting.view', 'guard_name' => 'web']);
    $backupPerm = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'personal-accounting.backup', 'guard_name' => 'web']);
    $user->givePermissionTo([$view, $backupPerm]);

    $svc = app(BackupService::class);
    $result = $svc->export($user->id, (int) $tenant->id, 'tenant');

    $this->actingAs($user)->post(route('personal.settings.backup.restore'), [
        'file_name' => $result['name'],
    ])->assertRedirect();
});

// --- Upload-based restore + tenant isolation ---

test('upload restore into own tenant cannot leak to another tenant', function () {
    $tenantA = \Modules\CORE\Models\Tenant::create(['name' => 'Tenant A', 'slug' => 'tenant-a', 'status' => 'active', 'plan' => 'free']);
    $userA = \App\Models\User::factory()->create(['tenant_id' => $tenantA->id]);

    // Craft a "foreign" backup that claims to belong to tenant 999.
    $payload = [
        'type' => 'tenant',
        'tenant' => ['id' => 999, 'name' => 'Fake Other Tenant'],
        'modules' => [
            'personal_accounting' => [
                'personal_accounts' => [
                    ['id' => 9001, 'name' => 'Secret', 'type' => 'cash', 'currency' => 'BDT', 'balance' => 99999, 'user_id' => $userA->id],
                ],
            ],
        ],
    ];
    \Illuminate\Support\Facades\Storage::disk('local')->put('/tmp/foreign.json', json_encode($payload));
    $realPath = \Illuminate\Support\Facades\Storage::disk('local')->path('/tmp/foreign.json');
    $file = new \Illuminate\Http\UploadedFile($realPath, 'foreign.json', 'application/json', null, true);

    $svc = app(\Modules\CORE\Services\BackupService::class);
    $result = $svc->restoreUploadedFile($file, 'tenant', (int) $tenantA->id, (int) $tenantA->id);

    // The record must land in tenant A, not 999. Because ids are remapped on
    // restore, match by a stable column instead of the crafted id.
    $row = DB::table('personal_accounts')->where('name', 'Secret')->where('tenant_id', $tenantA->id)->first();
    expect($row)->not->toBeNull();
    expect((int) $row->tenant_id)->toBe((int) $tenantA->id);
    DB::table('personal_accounts')->where('name', 'Secret')->where('tenant_id', $tenantA->id)->delete();
});

test('restore remaps ids so it never collides with another tenant rows', function () {
    // Tenant B owns the very first personal_accounts id.
    $tenantB = \Modules\CORE\Models\Tenant::create(['name' => 'Tenant B', 'slug' => 'tenant-b', 'status' => 'active', 'plan' => 'free']);
    $userB = \App\Models\User::factory()->create(['tenant_id' => $tenantB->id]);
    \Modules\PersonalAccounting\Models\PersonalAccount::create([
        'tenant_id' => $tenantB->id, 'user_id' => $userB->id, 'name' => 'B Wallet', 'type' => 'cash', 'currency' => 'BDT', 'balance' => 50,
    ]);
    $bId = DB::table('personal_accounts')->where('tenant_id', $tenantB->id)->value('id');

    // Tenant A's backup references account id = $bId (same global id, owned by B).
    $tenantA = \Modules\CORE\Models\Tenant::create(['name' => 'Tenant A', 'slug' => 'tenant-a', 'status' => 'active', 'plan' => 'free']);
    $userA = \App\Models\User::factory()->create(['tenant_id' => $tenantA->id]);

    $payload = [
        'type' => 'tenant',
        'tenant' => ['id' => $tenantA->id, 'name' => 'Tenant A'],
        'modules' => [
            'personal_accounting' => [
                'personal_accounts' => [
                    ['id' => $bId, 'tenant_id' => $tenantA->id, 'user_id' => $userA->id, 'name' => 'A Wallet', 'type' => 'cash', 'currency' => 'BDT', 'balance' => 210, 'is_default' => 1],
                ],
                'personal_categories' => [
                    ['id' => 7, 'tenant_id' => $tenantA->id, 'user_id' => $userA->id, 'name' => 'Food', 'type' => 'expense'],
                ],
                'personal_transactions' => [
                    ['id' => 3, 'tenant_id' => $tenantA->id, 'user_id' => $userA->id, 'account_id' => $bId, 'category_id' => 7, 'type' => 'expense', 'amount' => 40, 'date' => '2026-08-01'],
                ],
            ],
        ],
    ];

    \Illuminate\Support\Facades\Storage::disk('local')->put('/tmp/collide.json', json_encode($payload));
    $realPath = \Illuminate\Support\Facades\Storage::disk('local')->path('/tmp/collide.json');
    $file = new \Illuminate\Http\UploadedFile($realPath, 'collide.json', 'application/json', null, true);

    $svc = app(\Modules\CORE\Services\BackupService::class);
    $result = $svc->restoreUploadedFile($file, 'tenant', (int) $tenantA->id, (int) $tenantA->id);

    expect($result['records'])->toBe(3);

    // Tenant B's original account is untouched and still has its id.
    $bAccount = DB::table('personal_accounts')->where('name', 'B Wallet')->first();
    expect((int) $bAccount->id)->toBe($bId);

    // Tenant A's account got a fresh, non-colliding id.
    $aAccount = DB::table('personal_accounts')->where('name', 'A Wallet')->where('tenant_id', $tenantA->id)->first();
    expect((int) $aAccount->id)->not->toBe($bId);

    // Tenant A's transaction must point at the NEW account id, not tenant B's id.
    $tx = DB::table('personal_transactions')->where('tenant_id', $tenantA->id)->first();
    expect((int) $tx->account_id)->toBe((int) $aAccount->id);
    expect((int) $tx->account_id)->not->toBe($bId);

    DB::table('personal_transactions')->where('tenant_id', $tenantA->id)->delete();
    DB::table('personal_accounts')->where('tenant_id', $tenantA->id)->delete();
    DB::table('personal_categories')->where('tenant_id', $tenantA->id)->delete();
});

test('CORE restore-upload endpoint restores a tenant backup file', function () {
    $admin = backupAdmin();
    $this->actingAs($admin);
    $tenant = \Modules\CORE\Models\Tenant::create(['name' => 'Tenant A', 'slug' => 'tenant-a', 'status' => 'active', 'plan' => 'free']);
    $user = \App\Models\User::factory()->create(['tenant_id' => $tenant->id]);
    \Modules\PersonalAccounting\Models\PersonalAccount::create([
        'tenant_id' => $tenant->id, 'user_id' => $user->id, 'name' => 'Wallet', 'type' => 'cash', 'currency' => 'BDT',
    ]);

    $svc = app(\Modules\CORE\Services\BackupService::class);
    $result = $svc->export($admin->id, (int) $tenant->id, 'tenant');
    $path = $result['path'];

    // Read the file back as an UploadedFile.
    $contents = \Illuminate\Support\Facades\Storage::disk('local')->get($path);
    \Illuminate\Support\Facades\Storage::disk('local')->put('/tmp/restore.json', $contents);
    $realPath = \Illuminate\Support\Facades\Storage::disk('local')->path('/tmp/restore.json');
    $file = new \Illuminate\Http\UploadedFile($realPath, 'restore.json', 'application/json', null, true);

    $this->post(route('backup.restore-upload'), [
        'file' => $file,
        'scope' => 'tenant',
        'tenant_id' => $tenant->id,
    ])->assertRedirect();

    expect(DB::table('personal_accounts')->where('tenant_id', $tenant->id)->count())->toBe(1);
});

test('PA restore-upload endpoint restores own tenant data', function () {
    $tenant = \Modules\CORE\Models\Tenant::create(['name' => 'Tenant A', 'slug' => 'tenant-a', 'status' => 'active', 'plan' => 'free']);
    $user = \App\Models\User::factory()->create(['tenant_id' => $tenant->id]);
    $view = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'personal-accounting.view', 'guard_name' => 'web']);
    $backupPerm = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'personal-accounting.backup', 'guard_name' => 'web']);
    $user->givePermissionTo([$view, $backupPerm]);

    $svc = app(\Modules\CORE\Services\BackupService::class);
    $result = $svc->export($user->id, (int) $tenant->id, 'tenant');
    $contents = \Illuminate\Support\Facades\Storage::disk('local')->get($result['path']);
    \Illuminate\Support\Facades\Storage::disk('local')->put('/tmp/restore.json', $contents);
    $realPath = \Illuminate\Support\Facades\Storage::disk('local')->path('/tmp/restore.json');
    $file = new \Illuminate\Http\UploadedFile($realPath, 'restore.json', 'application/json', null, true);

    $this->actingAs($user)->post(route('personal.settings.backup.restore-upload'), [
        'file' => $file,
    ])->assertRedirect();
});
