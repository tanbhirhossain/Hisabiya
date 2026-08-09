<?php

namespace Modules\PersonalAccounting\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\PersonalAccounting\Interfaces\RepositoryInterface;

/**
 * Generic Eloquent repository shared by every repository in the module.
 * Concrete repositories extend this and add domain-specific queries.
 */
abstract class BaseRepository implements RepositoryInterface
{
    /** @var class-string<Model> */
    protected string $model;

    public function all(): Collection
    {
        return $this->query()->get();
    }

    public function find(int $id): ?Model
    {
        return $this->query()->find($id);
    }

    public function findOrFail(int $id): Model
    {
        return $this->query()->findOrFail($id);
    }

    public function create(array $data): Model
    {
        return $this->query()->create($data);
    }

    public function update(int $id, array $data): Model
    {
        $model = $this->findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(int $id): bool
    {
        return (bool) $this->findOrFail($id)->delete();
    }

    public function paginate(int $perPage = 15)
    {
        return $this->query()->paginate($perPage);
    }

    protected function query()
    {
        return forward_static_call([$this->model, 'query']);
    }

    /** Scope to a tenant when the auth user is known, else return unscoped. */
    protected function tenantScope()
    {
        $query = $this->query();
        $tenantId = auth()->user()?->tenant_id;

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        return $query;
    }
}
