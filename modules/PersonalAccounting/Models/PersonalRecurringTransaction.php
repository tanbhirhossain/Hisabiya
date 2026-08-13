<?php

namespace Modules\PersonalAccounting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\PersonalAccounting\Traits\HasTenant;
use Modules\PersonalAccounting\Traits\HasUser;

/**
 * A template that spawns PersonalTransaction records on a schedule (daily, weekly, ...).
 */
class PersonalRecurringTransaction extends Model
{
    use HasFactory, HasTenant, HasUser;

    protected $guarded = [];

    protected $casts = [
        'template_data' => 'array',
        'amount' => 'decimal:2',
        'next_run_at' => 'datetime',
        'last_run_at' => 'datetime',
        'is_active' => 'boolean',
        'end_date' => 'date',
    ];

    public const FREQUENCIES = ['daily', 'weekly', 'monthly', 'yearly'];
    public const END_TYPES = ['never', 'on_date', 'after_occurrences'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(PersonalAccount::class, 'account_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PersonalCategory::class, 'category_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PersonalTransaction::class, 'recurring_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PersonalRecurringLog::class, 'recurring_id');
    }

    /** Whether this recurring template has reached its end condition. */
    public function hasEnded(): bool
    {
        if (! $this->is_active) {
            return true;
        }

        if ($this->end_type === 'on_date' && $this->end_date && now()->startOfDay()->gte($this->end_date->startOfDay())) {
            return true;
        }

        if ($this->end_type === 'after_occurrences'
            && $this->max_occurrences !== null
            && (int) $this->occurrences_count >= (int) $this->max_occurrences) {
            return true;
        }

        return false;
    }

    /** Recurring templates that are active and due to run at or before now. */
    public function scopeDue(Builder $query, $now = null): Builder
    {
        $now ??= now();

        return $query->where('is_active', true)->where('next_run_at', '<=', $now);
    }
}
