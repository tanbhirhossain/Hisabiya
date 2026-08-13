<?php

namespace Modules\CORE\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A sellable subscription plan for a module (e.g. "Personal Accounting Pro").
 * Each plan grants a fixed set of module permissions plus feature flags.
 */
class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'permissions' => 'array',
        'features' => 'array',
        'feature_flags' => 'array',
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class, 'plan_id');
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? [], true);
    }

    public function hasFlag(string $flag): bool
    {
        return in_array($flag, $this->feature_flags ?? [], true);
    }
}
