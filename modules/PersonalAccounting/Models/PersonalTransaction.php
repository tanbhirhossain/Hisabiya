<?php

namespace Modules\PersonalAccounting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\PersonalAccounting\Traits\HasTenant;
use Modules\PersonalAccounting\Traits\HasUser;

/**
 * A single financial movement: income, expense, or a transfer between accounts.
 */
class PersonalTransaction extends Model
{
    use HasFactory, HasTenant, HasUser;

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
        'is_recurring' => 'boolean',
    ];

    public const TYPES = ['income', 'expense', 'transfer'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(PersonalAccount::class, 'account_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PersonalCategory::class, 'category_id');
    }

    public function recurring(): BelongsTo
    {
        return $this->belongsTo(PersonalRecurringTransaction::class, 'recurring_id');
    }

    /** Whether this transaction increases the account balance (income). */
    public function isCredit(): bool
    {
        return $this->type === 'income';
    }

    /** Whether this transaction decreases the account balance (expense or transfer out). */
    public function isDebit(): bool
    {
        return in_array($this->type, ['expense', 'transfer'], true);
    }

    public function scopeBetween(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('date', [$from, $to]);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }
}
