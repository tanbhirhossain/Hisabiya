<?php

namespace Modules\PersonalAccounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\PersonalAccounting\Traits\HasTenant;
use Modules\PersonalAccounting\Traits\HasUser;

/**
 * A spending limit assigned to a category for a given period.
 */
class PersonalBudget extends Model
{
    use HasFactory, HasTenant, HasUser;

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public const PERIODS = ['daily', 'weekly', 'monthly', 'yearly'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(PersonalCategory::class, 'category_id');
    }
}
