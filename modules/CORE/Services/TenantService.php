<?php

namespace Modules\CORE\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\CORE\Models\Tenant;

class TenantService
{
    public function paginate(Request $request): LengthAwarePaginator
    {
        return Tenant::query()
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->string('search'));
                $query->where(fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%"));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('sort') && $request->filled('direction'), function ($query) use ($request): void {
                $sort = $request->string('sort');
                $direction = $request->string('direction') === 'asc' ? 'asc' : 'desc';
                $column = in_array($sort, ['name', 'status', 'currency', 'created_at'], true) ? (string) $sort : 'created_at';
                $query->orderBy($column, $direction);
            }, fn ($query) => $query->orderByDesc('created_at'))
            ->withCount('users')
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();
    }

    public function create(array $data): Tenant
    {
        return Tenant::create($data);
    }

    public function update(Tenant $tenant, array $data): Tenant
    {
        $tenant->update($data);

        return $tenant->fresh();
    }

    public function delete(Tenant $tenant): void
    {
        $tenant->delete();
    }
}
