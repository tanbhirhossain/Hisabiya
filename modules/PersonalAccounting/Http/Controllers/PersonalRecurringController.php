<?php

namespace Modules\PersonalAccounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\PersonalAccounting\Models\PersonalRecurringLog;
use Modules\PersonalAccounting\Models\PersonalRecurringTransaction;

class PersonalRecurringController extends Controller
{
    /**
     * List recurring templates for the authenticated user.
     */
    public function index(Request $request): \Inertia\Response
    {
        $user = $request->user();
        $tenantId = (int) $user->tenant_id;

        $recurring = PersonalRecurringTransaction::query()
            ->forTenant($tenantId)
            ->where('user_id', $user->id)
            ->with(['account:id,name,color', 'category:id,name,icon,color'])
            ->orderBy('is_active', 'desc')
            ->orderBy('next_run_at')
            ->get();

        return \Inertia\Inertia::render('PersonalAccounting::Recurring/Index', [
            'recurring' => $recurring,
        ]);
    }

    /**
     * Paginated run logs for a recurring template.
     */
    public function logs(Request $request, PersonalRecurringTransaction $recurring): JsonResponse
    {
        $logs = PersonalRecurringLog::query()
            ->where('tenant_id', (int) $request->user()->tenant_id)
            ->where('user_id', (int) $request->user()->id)
            ->where('recurring_id', $recurring->id)
            ->with('transaction:id,date,amount')
            ->latest('ran_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json($logs);
    }

    /**
     * Toggle a recurring template's active state.
     */
    public function toggle(Request $request, PersonalRecurringTransaction $recurring): JsonResponse
    {
        $recurring->forceFill(['is_active' => ! $recurring->is_active])->save();

        return response()->json(['success' => true, 'is_active' => $recurring->is_active]);
    }
}
