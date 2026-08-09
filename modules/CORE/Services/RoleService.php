<?php

namespace Modules\CORE\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleService
{
    public function paginate(Request $request): LengthAwarePaginator
    {
        return Role::query()
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->string('search'));
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('guard_name', 'like', "%{$search}%");
            })
            ->withCount('permissions', 'users')
            ->when($request->filled('sort') && $request->filled('direction'), function ($query) use ($request): void {
                $sort = $request->string('sort');
                $direction = $request->string('direction') === 'asc' ? 'asc' : 'desc';
                $column = in_array($sort, ['name', 'guard_name', 'created_at'], true) ? (string) $sort : 'created_at';
                $query->orderBy($column, $direction);
            }, fn ($query) => $query->orderByDesc('created_at'))
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();
    }

    public function create(array $data): Role
    {
        $role = Role::create($data);

        activity('role')
            ->performedOn($role)
            ->event('created')
            ->log("Role :subject.name was created");

        return $role->refresh();
    }

    public function update(Role $role, array $data): Role
    {
        $role->update($data);

        activity('role')
            ->performedOn($role)
            ->event('updated')
            ->log("Role :subject.name was updated");

        return $role->refresh();
    }

    public function syncPermissions(Role $role, array $permissionIds): Role
    {
        $role->syncPermissions($permissionIds);

        activity('role')
            ->performedOn($role)
            ->event('permissions')
            ->log("Permissions for :subject.name were updated");

        return $role->load('permissions:id,name')->refresh();
    }

    public function delete(Role $role): void
    {
        if (strtolower((string) $role->name) === 'super-admin') {
            abort(422, 'The super-admin role cannot be deleted.');
        }

        $name = $role->name;
        $role->delete();

        activity('role')
            ->event('deleted')
            ->log("Role {$name} was deleted");
    }

    public function options(): array
    {
        return [
            'permissions' => Permission::query()->orderBy('name')->get(['id', 'name', 'guard_name']),
        ];
    }
}
