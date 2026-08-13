<?php

namespace Modules\PersonalAccounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Modules\CORE\Services\BackupService;

/**
 * Tenant user's own data backup (Personal Accounting). Gated by the PRO
 * `personal-accounting.backup` permission — a user can only back up their own
 * tenant's data, never another tenant's.
 */
class PersonalBackupController extends Controller
{
    public function __construct(private readonly BackupService $backups)
    {
    }

    public function index(Request $request): Response
    {
        $tenantId = (int) $request->user()->tenant_id;

        return Inertia::render('PersonalAccounting::Settings/Backup', [
            'backups' => $this->backups->historyForTenant($tenantId),
        ]);
    }

    public function create(Request $request): \Illuminate\Http\RedirectResponse
    {
        $tenantId = (int) $request->user()->tenant_id;
        $result = $this->backups->export((int) $request->user()->id, $tenantId, 'tenant');

        return redirect()->route('personal.settings.backup.index')
            ->with('success', "Backup created: {$result['name']}");
    }

    public function download(Request $request, string $file)
    {
        // Only allow downloading backups belonging to the user's own tenant.
        $path = 'backups/'.basename($file);
        $belongsToTenant = \Modules\CORE\Models\Backup::where('file_path', $path)
            ->where('tenant_id', $request->user()->tenant_id)
            ->exists();

        abort_unless($belongsToTenant && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, basename($file));
    }

    /**
     * Restore the user's own tenant data from one of their backups.
     */
    public function restore(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate(['file_name' => ['required', 'string']]);

        // Ensure the backup belongs to the user's own tenant.
        $belongsToTenant = \Modules\CORE\Models\Backup::where('file_name', $data['file_name'])
            ->where('tenant_id', $request->user()->tenant_id)
            ->exists();

        abort_unless($belongsToTenant, 404, 'Backup not found.');

        $result = $this->backups->restore($data['file_name'], 'tenant', (int) $request->user()->tenant_id);

        return redirect()->route('personal.settings.backup.index')
            ->with('success', "Restored {$result['records']} record(s).");
    }

    /**
     * Restore the user's own tenant data from an uploaded backup file. The data is
     * always forced into the caller's own tenant — a tenant can never overwrite
     * another tenant's data, even with a crafted/foreign backup file.
     */
    public function restoreUpload(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:json,txt', 'max:10240'],
        ]);

        $result = $this->backups->restoreUploadedFile(
            $request->file('file'),
            'tenant',
            (int) $request->user()->tenant_id,
            (int) $request->user()->tenant_id,
        );

        return redirect()->route('personal.settings.backup.index')
            ->with('success', "Restored from upload: {$result['records']} record(s).");
    }
}
