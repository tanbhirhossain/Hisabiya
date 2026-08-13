<?php

namespace Modules\CORE\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\CORE\Models\Backup;
use Modules\CORE\Models\Membership;
use Modules\CORE\Models\Payment;
use Modules\CORE\Models\SubscriptionPlan;
use Modules\CORE\Models\Tenant;
use Modules\CORE\Models\TenantSubscription;

/**
 * Exports data as JSON for backups. Supports:
 *  - single-tenant backup (a tenant's own data, used by PRO subscribers)
 *  - all-tenants backup (CORE super-admin)
 *
 * Tenant data is isolated by tenant_id so users can never see another tenant's data.
 */
class BackupService
{
    private const MODULE_TABLES = [
        'personal_accounts',
        'personal_categories',
        'personal_transactions',
        'personal_recurring_transactions',
        'personal_budgets',
        'personal_savings_goals',
        'personal_contacts',
        'personal_loans',
        'personal_loan_payments',
        'personal_recurring_logs',
        'personal_import_logs',
    ];

    /**
     * Restore processing order (parents before children so FK constraints hold)
     * and the foreign-key columns each table carries, mapped to the table they
     * reference. Because ids are global (shared across all tenants), every row
     * is re-inserted with a fresh id and FK columns are remapped old->new.
     *
     * @var array<string, array<string, string>>
     */
    private const RESTORE_ORDER = [
        'personal_accounts' => [],
        'personal_contacts' => [],
        'personal_categories' => ['parent_id' => 'personal_categories'],
        'personal_savings_goals' => ['account_id' => 'personal_accounts'],
        'personal_recurring_transactions' => ['account_id' => 'personal_accounts', 'category_id' => 'personal_categories'],
        'personal_loans' => ['contact_id' => 'personal_contacts', 'account_id' => 'personal_accounts'],
        'personal_budgets' => ['category_id' => 'personal_categories'],
        'personal_transactions' => ['account_id' => 'personal_accounts', 'to_account_id' => 'personal_accounts', 'category_id' => 'personal_categories', 'recurring_id' => 'personal_recurring_transactions'],
        'personal_loan_payments' => ['loan_id' => 'personal_loans'],
        'personal_recurring_logs' => ['recurring_id' => 'personal_recurring_transactions', 'transaction_id' => 'personal_transactions'],
        'personal_import_logs' => ['account_id' => 'personal_accounts'],
    ];

    /**
     * Build a backup payload for a single tenant.
     *
     * @return array<string, mixed>
     */
    public function tenantPayload(int $tenantId): array
    {
        $tenant = Tenant::findOrFail($tenantId);

        return [
            'type' => 'tenant',
            'generated_at' => now()->toIso8601String(),
            'app' => 'hisabiya',
            'tenant' => $tenant->toArray(),
            'users' => DB::table('users')
                ->where('tenant_id', $tenantId)
                ->get()
                ->map(fn ($u) => ['id' => $u->id, 'tenant_id' => $u->tenant_id, 'name' => $u->name, 'email' => $u->email, 'phone' => $u->phone, 'company_name' => $u->company_name, 'is_active' => $u->is_active])
                ->values()
                ->all(),
            'memberships' => Membership::where('tenant_id', $tenantId)->get()->toArray(),
            'subscriptions' => TenantSubscription::where('tenant_id', $tenantId)->with('plan')->get()->toArray(),
            'payments' => Payment::where('tenant_id', $tenantId)->get()->toArray(),
            'modules' => [
                'personal_accounting' => $this->moduleData($tenantId),
            ],
        ];
    }

    /**
     * Build a full-platform backup payload (all tenants).
     *
     * @return array<string, mixed>
     */
    public function allPayload(): array
    {
        $tenants = Tenant::all();

        return [
            'type' => 'all',
            'generated_at' => now()->toIso8601String(),
            'app' => 'hisabiya',
            'plans' => SubscriptionPlan::all()->toArray(),
            'tenants' => $tenants->map(fn ($t) => $this->tenantPayload((int) $t->id))->values()->all(),
        ];
    }

    /**
     * Export a backup as a downloadable JSON file and record it.
     *
     * @return array<string, mixed>  ['path' => ..., 'name' => ..., 'size' => ...]
     */
    public function export(int $userId, ?int $tenantId, string $type): array
    {
        $payload = $type === 'all' ? $this->allPayload() : $this->tenantPayload($tenantId);

        $filename = ($type === 'all' ? 'hisabiya-full-backup' : 'hisabiya-tenant-'.$tenantId).'-'.now()->format('Ymd-His').'.json';
        $path = 'backups/'.$filename;

        $size = Storage::disk('local')->put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        Backup::create([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'type' => $type,
            'file_path' => $path,
            'file_name' => $filename,
            'file_size' => (int) $size,
            'status' => 'completed',
        ]);

        return ['path' => $path, 'name' => $filename, 'size' => (int) $size];
    }

    /**
     * List backup history (scoped to the requesting user/tenant).
     */
    public function historyForTenant(int $tenantId)
    {
        return Backup::where('tenant_id', $tenantId)->latest()->get();
    }

    public function historyAll()
    {
        return Backup::with('tenant:id,name')->latest()->get();
    }

    /**
     * Fetch the underlying tenant data for the module tables.
     *
     * @return array<string, mixed>
     */
    private function moduleData(int $tenantId): array
    {
        $data = [];
        foreach (self::MODULE_TABLES as $table) {
            if (! \Schema::hasTable($table)) {
                continue;
            }
            $data[$table] = DB::table($table)->where('tenant_id', $tenantId)->get()->toArray();
        }

        return $data;
    }

    /**
     * Read and decode a backup file payload.
     *
     * @return array<string, mixed>
     */
    public function readBackup(string $fileName): array
    {
        $path = 'backups/'.basename($fileName);
        abort_unless(Storage::disk('local')->exists($path), 404, 'Backup file not found.');

        $contents = Storage::disk('local')->get($path);
        $payload = json_decode($contents, true);

        abort_if(! is_array($payload), 422, 'Invalid backup file.');

        return $payload;
    }

    /**
     * Restore a tenant's data from a backup payload (single-tenant restore).
     * This replaces the tenant's module data for the given tenant_id.
     *
     * @param  array<string, mixed>  $payload
     * @return int number of records restored
     */
    public function restoreTenantData(array $payload, ?int $targetTenantId = null): int
    {
        $tenantId = $targetTenantId ?? ($payload['tenant']['id'] ?? null);
        abort_if(! $tenantId, 422, 'Backup does not reference a tenant.');

        return DB::transaction(function () use ($payload, $tenantId): int {
            $restored = 0;

            $modules = $payload['modules'] ?? [];

            // Collect all module tables present in the backup.
            $tablesToRestore = [];
            foreach ($modules as $moduleTables) {
                foreach ($moduleTables as $table => $rows) {
                    if (\Schema::hasTable($table) && array_key_exists($table, self::RESTORE_ORDER)) {
                        $tablesToRestore[$table] = (array) $rows;
                    }
                }
            }

            // Delete the tenant's existing rows first, children before parents
            // so foreign-key constraints are not violated.
            $reverseOrder = array_reverse(array_keys(self::RESTORE_ORDER));
            foreach ($reverseOrder as $table) {
                DB::table($table)->where('tenant_id', $tenantId)->delete();
            }

            // id maps: table => [oldId => newId]
            $idMaps = [];

            foreach (self::RESTORE_ORDER as $table => $fkColumns) {
                if (! isset($tablesToRestore[$table])) {
                    continue;
                }

                $rows = $tablesToRestore[$table];

                // Categories self-reference (parent_id), so parents must be
                // inserted before their children.
                if ($table === 'personal_categories') {
                    $rows = $this->sortParentsFirst($rows);
                }

                $tableMap = [];
                foreach ($rows as $row) {
                    $row = (array) $row;
                    $oldId = isset($row['id']) ? (int) $row['id'] : null;
                    unset($row['id']);
                    $row['tenant_id'] = $tenantId;

                    // Rewrite foreign keys from their old ids to the freshly
                    // assigned ids of already-restored parent rows.
                    foreach ($fkColumns as $column => $parentTable) {
                        if (! isset($row[$column]) || $row[$column] === null) {
                            continue;
                        }
                        $oldFk = (int) $row[$column];
                        $resolved = null;
                        if ($parentTable === $table) {
                            // Self-reference: the parent row is already in $tableMap.
                            $resolved = $tableMap[$oldFk] ?? null;
                        } elseif (isset($idMaps[$parentTable][$oldFk])) {
                            $resolved = $idMaps[$parentTable][$oldFk];
                        }
                        if ($resolved !== null) {
                            $row[$column] = $resolved;
                        }
                    }

                    $newId = DB::table($table)->insertGetId($row);
                    if ($oldId !== null) {
                        $tableMap[$oldId] = $newId;
                    }
                    $restored++;
                }

                $idMaps[$table] = $tableMap;
            }

            return $restored;
        });
    }

    /**
     * Reorder rows so a row is always inserted after its parent (used for
     * self-referencing tables such as personal_categories).
     *
     * @param  array<int, mixed>  $rows
     * @return array<int, mixed>
     */
    private function sortParentsFirst(array $rows): array
    {
        $byId = [];
        foreach ($rows as $row) {
            $row = (array) $row;
            if (isset($row['id'])) {
                $byId[(int) $row['id']] = $row;
            }
        }

        $visited = [];
        $sorted = [];

        $visit = function (int $id) use (&$visit, &$visited, &$sorted, $byId): void {
            if (isset($visited[$id])) {
                return;
            }
            $visited[$id] = true;
            $row = $byId[$id];
            if (isset($row['parent_id'], $byId[(int) $row['parent_id']])) {
                $visit((int) $row['parent_id']);
            }
            $sorted[] = $row;
        };

        foreach ($byId as $id => $row) {
            $visit($id);
        }

        return $sorted;
    }

    /**
     * Restore a full backup: re-create tenants and their data.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, int>  ['tenants' => n, 'records' => n]
     */
    public function restoreAllData(array $payload): array
    {
        return DB::transaction(function () use ($payload): array {
            $tenantCount = 0;
            $recordCount = 0;

            foreach (($payload['tenants'] ?? []) as $tenantPayload) {
                $tenant = Tenant::firstOrCreate(
                    ['slug' => $tenantPayload['tenant']['slug'] ?? null],
                    array_filter($tenantPayload['tenant'] ?? [], fn ($v) => $v !== null),
                );

                $recordCount += $this->restoreTenantData($tenantPayload, (int) $tenant->id);
                $tenantCount++;
            }

            return ['tenants' => $tenantCount, 'records' => $recordCount];
        });
    }

    /**
     * Restore a backup file by name and scope.
     *
     * @return array<string, mixed>
     */
    public function restore(string $fileName, string $scope, ?int $targetTenantId = null): array
    {
        $payload = $this->readBackup($fileName);

        if ($scope === 'all' || ($payload['type'] ?? '') === 'all') {
            return $this->restoreAllData($payload);
        }

        $restored = $this->restoreTenantData($payload, $targetTenantId);

        return ['tenants' => 1, 'records' => $restored];
    }

    /**
     * Restore from an uploaded backup file. For tenant-scope restores, the data is
     * always forced into the caller's own tenant (`callerTenantId`), so a tenant
     * can never overwrite another tenant's data even with a crafted file.
     *
     * @return array<string, mixed>
     */
    public function restoreUploadedFile(\Illuminate\Http\UploadedFile $file, string $scope, ?int $callerTenantId = null, ?int $targetTenantId = null): array
    {
        $json = $file->get();
        $payload = json_decode($json, true);
        abort_if(! is_array($payload), 422, 'Invalid backup file.');

        // A tenant user can only ever restore into their own tenant.
        if ($callerTenantId !== null) {
            $targetTenantId = $callerTenantId;
            $scope = 'tenant';
        }

        if ($scope === 'all') {
            return $this->restoreAllData($payload);
        }

        // For tenant restore, require a target tenant.
        abort_if($targetTenantId === null, 422, 'A target tenant is required to restore.');
        abort_unless(Tenant::where('id', $targetTenantId)->exists(), 422, 'Target tenant not found.');

        $restored = $this->restoreTenantData($payload, $targetTenantId);

        return ['tenants' => 1, 'records' => $restored];
    }
}
