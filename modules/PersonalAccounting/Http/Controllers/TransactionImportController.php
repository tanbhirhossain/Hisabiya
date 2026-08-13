<?php

namespace Modules\PersonalAccounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\PersonalAccounting\Services\TransactionImportService;

class TransactionImportController extends Controller
{
    public function __construct(private readonly TransactionImportService $service)
    {
    }

    /**
     * Render the import wizard page.
     */
    public function showImport(Request $request): Response
    {
        $tenantId = (int) $request->user()->tenant_id;

        return Inertia::render('PersonalAccounting::Transactions/Import', [
            'accounts' => \Modules\PersonalAccounting\Models\PersonalAccount::query()
                ->forTenant($tenantId)
                ->where('user_id', $request->user()->id)
                ->orderBy('name')
                ->get(['id', 'name', 'type', 'currency']),
            'categories' => \Modules\PersonalAccounting\Models\PersonalCategory::query()
                ->forTenant($tenantId)
                ->orderBy('name')
                ->get(['id', 'name', 'type']),
        ]);
    }

    /**
     * Accept a CSV, parse it, detect columns and return preview data (no DB write).
     */
    public function upload(Request $request): JsonResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:4096'],
            'account_id' => ['required', 'integer', 'exists:personal_accounts,id'],
        ]);

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $request->file('file');

        $rows = $this->service->parseCSV($file);

        if (empty($rows)) {
            return response()->json([
                'error' => 'The file appears to be empty or unreadable.',
            ], 422);
        }

        $columnMap = $this->service->detectColumns($rows);

        return response()->json([
            'filename' => $file->getClientOriginalName(),
            'account_id' => $data['account_id'],
            'total_rows' => count($rows),
            'column_map' => $columnMap,
            'headers' => array_keys($rows[0]),
            'preview' => $this->service->preview($rows, $columnMap, 5),
        ]);
    }

    /**
     * Confirm the column mapping + account and run the import.
     */
    public function confirm(Request $request): JsonResponse
    {
        $data = $request->validate([
            'account_id' => ['required', 'integer', 'exists:personal_accounts,id'],
            'column_map' => ['required', 'array', 'min:2'],
            'column_map.date' => ['required', 'string'],
            'column_map.amount' => ['required', 'string'],
            'column_map.description' => ['nullable', 'string'],
            'column_map.type' => ['nullable', 'string'],
            'column_map.category' => ['nullable', 'string'],
            'rows' => ['required', 'array'],
            'filename' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $tenantId = (int) $user->tenant_id;
        $accountId = (int) $data['account_id'];

        $result = $this->service->import(
            $data['rows'],
            $data['column_map'],
            $accountId,
            (int) $user->id,
            $tenantId,
        );

        // Record the import log.
        $this->service->log([
            'tenant_id' => $tenantId,
            'user_id' => (int) $user->id,
            'account_id' => $accountId,
            'filename' => $data['filename'] ?? 'import.csv',
            'total_rows' => count($data['rows']),
            'imported' => $result['imported'],
            'skipped' => $result['skipped'],
            'failed' => $result['failed'],
            'status' => $result['failed'] > 0 ? 'completed' : 'completed',
        ]);

        return response()->json([
            'success' => true,
            'result' => $result,
        ]);
    }
}
