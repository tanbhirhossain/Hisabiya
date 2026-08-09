<?php

namespace Modules\CORE\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\CORE\Requests\RoleRequest;
use Modules\CORE\Services\PermissionService;
use Modules\CORE\Services\RoleService;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleService $roleService,
        private readonly PermissionService $permissionService,
    ) {
    }

    public function index(Request $request): Response
    {
        return Inertia::render('CORE::Roles/Index', [
            'roles' => $this->roleService->paginate($request),
            'filters' => $request->only(['search', 'sort', 'direction', 'per_page']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('CORE::Roles/Create', [
            'options' => $this->roleService->options(),
        ]);
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        $role = $this->roleService->create($request->safe()->except('permissions'));
        $this->roleService->syncPermissions($role, $request->validated('permissions', []));

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function edit(Role $role): Response
    {
        return Inertia::render('CORE::Roles/Edit', [
            'role' => $role,
            'permissions' => $this->permissionService->groupForRoleForm($role),
            'options' => $this->roleService->options(),
        ]);
    }

    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        $this->roleService->update($role, $request->safe()->except('permissions'));
        $this->roleService->syncPermissions($role, $request->validated('permissions', []));

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->roleService->delete($role);

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role deleted successfully.');
    }
}
