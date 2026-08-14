<?php

namespace Modules\CORE\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\CORE\Services\DashboardService;
use Modules\CORE\Services\ModuleRegistry;
use Modules\CORE\Services\SubscriptionProvisioner;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
        private readonly SubscriptionProvisioner $provisioner,
        private readonly ModuleRegistry $registry,
    ) {
    }

    /**
     * Landing page after login. Routes a user based on how many subscription
     * modules they can access:
     *  - one module  -> straight into that module's dashboard
     *  - many modules -> module chooser
     *  - none but admin -> the platform/admin dashboard
     *  - none and not admin -> chooser with an empty/subscribe state
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $modules = $this->provisioner->activeModulesForUser((int) $user->id);

        // Exactly one active module -> go straight in.
        if (count($modules) === 1) {
            $route = $this->registry->routeFor($modules[0]);

            if ($route) {
                return redirect()->route($route);
            }
        }

        // No modules but the user manages the platform -> admin dashboard.
        $isAdmin = $user->can('tenant.view');
        if (count($modules) === 0 && $isAdmin) {
            return $this->renderAdminDashboard();
        }

        // Multiple modules, or no modules for a non-admin -> chooser screen.
        return Inertia::render('CORE::Module/Chooser', [
            'modules' => $this->registry->available($modules),
            'canAdmin' => $isAdmin,
            'hasSubscription' => count($modules) > 0,
        ]);
    }

    /**
     * The platform (admin) analytics dashboard.
     */
    public function admin(): Response
    {
        return $this->renderAdminDashboard();
    }

    /**
     * Explicit module chooser (reachable from navigation) so a user subscribed
     * to multiple modules can switch between them at any time.
     */
    public function modules(Request $request): Response
    {
        $user = $request->user();
        $modules = $this->provisioner->activeModulesForUser((int) $user->id);

        return Inertia::render('CORE::Module/Chooser', [
            'modules' => $this->registry->available($modules),
            'canAdmin' => $user->can('tenant.view'),
            'hasSubscription' => count($modules) > 0,
        ]);
    }

    private function renderAdminDashboard(): Response
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
