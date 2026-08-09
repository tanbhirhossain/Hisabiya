<?php

namespace Modules\PersonalAccounting\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\CORE\Models\Tenant;
use Modules\PersonalAccounting\Traits\Scopes\TenantScope;

/**
 * Adds tenant awareness to a model: a belongsTo Tenant relation and a global
 * scope that automatically filters queries to the current user's tenant.
 */
trait HasTenant
{
    public static function bootHasTenant(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Query for a specific tenant (or the current user's tenant by default).
     */
    public function scopeForTenant(Builder $query, ?int $tenantId = null): Builder
    {
        return $query->withoutGlobalScope(TenantScope::class)
            ->where($this->getTable().'.tenant_id', $tenantId ?? auth()->user()?->tenant_id);
    }
}
