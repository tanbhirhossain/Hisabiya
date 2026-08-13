<?php

namespace Modules\CORE\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Modules\CORE\Models\Tenant;
use Modules\CORE\Services\BackupService;

/**
 * CORE backup center: super-admin can back up ALL tenants or a single tenant.
 */
class BackupController extends Controller
{
    public function __construct(private readonly BackupService $backups)
    {
    }

    public function index(Request $request): Response
    {
        return Inertia::render('CORE::Backup/Index', [
            'backups' => $this->backups->historyAll(),
            'tenants' => Tenant::query()->select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function backupAll(Request $request): \Illuminate\Http\RedirectResponse
    {
        $result = $this->backups->export((int) $request->user()->id, null, 'all');

        return redirect()->route('backup.index')->with('success', "Full backup created: {$result['name']}");
    }

    public function backupTenant(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate(['tenant_id' => ['required', 'integer', 'exists:tenants,id']]);

        $result = $this->backups->export((int) $request->user()->id, (int) $data['tenant_id'], 'tenant');

        return redirect()->route('backup.index')->with('success', "Tenant backup created: {$result['name']}");
    }

    public function download(Request $request, string $file)
    {
        $path = 'backups/'.basename($file);
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, basename($file));
    }

    /**
     * Restore a backup (full or single-tenant). Super-admin only.
     */
    public function restore(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'file_name' => ['required', 'string'],
            'scope' => ['required', 'in:all,tenant'],
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
        ]);

        $result = $this->backups->restore(
            $data['file_name'],
            $data['scope'],
            isset($data['tenant_id']) ? (int) $data['tenant_id'] : null,
        );

        return redirect()->route('backup.index')
            ->with('success', "Restored {$result['tenants']} tenant(s), {$result['records']} record(s).");
    }

    /**
     * Restore from an uploaded backup file. Super-admin can target any tenant or
     * restore a full backup.
     */
    public function restoreUpload(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:json,txt', 'max:10240'],
            'scope' => ['required', 'in:all,tenant'],
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
        ]);

        $result = $this->backups->restoreUploadedFile(
            $request->file('file'),
            $data['scope'],
            null,
            isset($data['tenant_id']) ? (int) $data['tenant_id'] : null,
        );

        return redirect()->route('backup.index')
            ->with('success', "Restored from upload: {$result['tenants']} tenant(s), {$result['records']} record(s).");
    }
}
