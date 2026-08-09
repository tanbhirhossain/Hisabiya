<?php

namespace Modules\CORE\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\CORE\Services\ActivityLogService;

class ActivityLogController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLogService)
    {
    }

    public function index(Request $request): Response
    {
        return Inertia::render('CORE::ActivityLogs/Index', [
            'activities' => $this->activityLogService->paginate($request),
            'filters' => $request->only(['search', 'event', 'sort', 'direction', 'per_page']),
        ]);
    }
}
