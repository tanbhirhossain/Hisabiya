<?php

namespace Modules\CORE\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\CORE\Services\DashboardService;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    public function index(): Response
    {
        return Inertia::render('CORE::Dashboard/Index', [
            'stats' => $this->dashboardService->stats(),
            'growth' => $this->dashboardService->growth(),
            'revenueSeries' => $this->dashboardService->revenueSeries(),
            'statusBreakdown' => $this->dashboardService->statusBreakdown(),
            'planBreakdown' => $this->dashboardService->planBreakdown(),
            'topTenants' => $this->dashboardService->topTenants(),
            'quickActions' => $this->dashboardService->quickActions(),
        ]);
    }
}
