<?php

namespace Modules\PersonalAccounting\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Adds a belongsTo User relation plus a convenient scope for the current user.
 */
trait HasUser
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForCurrentUser(Builder $query): Builder
    {
        return $query->where($this->getTable().'.user_id', auth()->id());
    }
}
