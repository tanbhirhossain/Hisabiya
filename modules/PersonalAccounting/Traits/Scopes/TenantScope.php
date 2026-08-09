<?php

namespace Modules\PersonalAccounting\Traits\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Automatically scope every Personal Accounting query to the currently
 * authenticated user's tenant, enforcing tenant isolation at the model level.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = auth()->check() ? auth()->user()->tenant_id : null;

        if ($tenantId !== null) {
            $builder->where($model->getTable().'.tenant_id', $tenantId);
        }
    }
}
