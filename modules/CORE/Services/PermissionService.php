<?php

namespace Modules\CORE\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionService
{
    public function paginate(Request $request): LengthAwarePaginator
    {
        return Permission::query()
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->string('search'));
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('guard_name', 'like', "%{$search}%");
            })
            ->withCount('roles')
            ->when($request->filled('sort') && $request->filled('direction'), function ($query) use ($request): void {
                $sort = $request->string('sort');
                $direction = $request->string('direction') === 'asc' ? 'asc' : 'desc';
                $column = in_array($sort, ['name', 'guard_name', 'created_at'], true) ? (string) $sort : 'created_at';
                $query->orderBy($column, $direction);
            }, fn ($query) => $query->orderBy('name'))
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();
    }

    public function create(array $data): Permission
    {
        $permission = Permission::create($data);

        activity('permission')
            ->performedOn($permission)
            ->event('created')
            ->log("Permission :subject.name was created");

        return $permission->refresh();
    }

    public function update(Permission $permission, array $data): Permission
    {
        $permission->update($data);

        activity('permission')
            ->performedOn($permission)
            ->event('updated')
            ->log("Permission :subject.name was updated");

        return $permission->refresh();
    }

    public function delete(Permission $permission): void
    {
        $name = $permission->name;
        $permission->delete();

        activity('permission')
            ->event('deleted')
            ->log("Permission {$name} was deleted");
    }

    public function groupForRoleForm(Role $role): array
    {
        $all = Permission::query()->orderBy('name')->get(['id', 'name']);

        return [
            'all' => $all,
            'selected' => $role->permissions()->pluck('id')->all(),
            'grouped' => $all->groupBy(fn ($permission) => str($permission->name)->before('.'))->map(
                fn ($group) => $group->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])
            ),
        ];
    }
}
