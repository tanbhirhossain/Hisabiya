<?php

namespace Modules\PersonalAccounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\PersonalAccounting\Traits\HasTenant;
use Modules\PersonalAccounting\Traits\HasUser;

/**
 * An income or expense category, optionally nested under a parent category.
 */
class PersonalCategory extends Model
{
    use HasFactory, HasTenant, HasUser;

    protected $guarded = [];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public const TYPES = ['income', 'expense'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PersonalTransaction::class, 'category_id');
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(PersonalBudget::class, 'category_id');
    }
}
