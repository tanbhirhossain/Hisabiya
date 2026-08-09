<?php

namespace Modules\CORE\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\CORE\Models\Tenant;

class UserService
{
    public function paginate(Request $request): LengthAwarePaginator
    {
        return User::query()
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->string('search'));
                $query->where(fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%"));
            })
            ->when($request->filled('tenant_id'), fn ($query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->when($request->filled('status'), function ($query) use ($request): void {
                $query->where('is_active', $request->string('status') === 'active');
            })
            ->when($request->filled('role'), fn ($query) => $query->role($request->string('role')))
            ->when($request->filled('sort') && $request->filled('direction'), function ($query) use ($request): void {
                $sort = $request->string('sort');
                $direction = $request->string('direction') === 'asc' ? 'asc' : 'desc';
                $column = in_array($sort, ['name', 'email', 'created_at', 'is_active'], true) ? (string) $sort : 'created_at';
                $query->orderBy($column, $direction);
            }, fn ($query) => $query->orderByDesc('created_at'))
            ->with(['tenant:id,name', 'roles:id,name'])
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();
    }

    public function create(array $data, array $roles = []): User
    {
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);
        $user->syncRoles($roles);

        return $user->load('roles:id,name', 'tenant:id,name');
    }

    public function update(User $user, array $data, array $roles = []): User
    {
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);
        $user->syncRoles($roles);

        return $user->load('roles:id,name', 'tenant:id,name');
    }

    public function delete(User $user): void
    {
        if ($user->id === auth()->id()) {
            abort(422, 'You cannot delete your own account.');
        }

        $user->delete();
    }

    public function options(): array
    {
        return [
            'tenants' => Tenant::query()->select('id', 'name')->orderBy('name')->get(),
            'roles' => \Spatie\Permission\Models\Role::query()->select('id', 'name')->orderBy('name')->get(),
        ];
    }
}
