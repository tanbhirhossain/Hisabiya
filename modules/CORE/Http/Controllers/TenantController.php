<?php

namespace Modules\CORE\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\CORE\Models\Tenant;
use Modules\CORE\Requests\TenantRequest;
use Modules\CORE\Services\TenantService;

class TenantController extends Controller
{
    public function __construct(private readonly TenantService $tenantService)
    {
    }

    public function index(Request $request): Response
    {
        return Inertia::render('CORE::Tenants/Index', [
            'tenants' => $this->tenantService->paginate($request),
            'filters' => $request->only(['search', 'status', 'sort', 'direction', 'per_page']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('CORE::Tenants/Create');
    }

    public function store(TenantRequest $request): RedirectResponse
    {
        $this->tenantService->create($request->validated());

        return redirect()
            ->route('tenants.index')
            ->with('success', 'Tenant created successfully.');
    }

    public function edit(Tenant $tenant): Response
    {
        return Inertia::render('CORE::Tenants/Edit', [
            'tenant' => $tenant,
        ]);
    }

    public function update(TenantRequest $request, Tenant $tenant): RedirectResponse
    {
        $this->tenantService->update($tenant, $request->validated());

        return redirect()
            ->route('tenants.index')
            ->with('success', 'Tenant updated successfully.');
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        $this->tenantService->delete($tenant);

        return redirect()
            ->route('tenants.index')
            ->with('success', 'Tenant deleted successfully.');
    }
}
