<?php

namespace Modules\PersonalAccounting\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Base contract every repository in the module must honour.
 */
interface RepositoryInterface
{
    public function all(): Collection;

    public function find(int $id): ?Model;

    public function findOrFail(int $id): Model;

    public function create(array $data): Model;

    public function update(int $id, array $data): Model;

    public function delete(int $id): bool;

    public function paginate(int $perPage = 15);
}
