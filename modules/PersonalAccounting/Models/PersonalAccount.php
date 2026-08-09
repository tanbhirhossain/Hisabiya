<?php

namespace Modules\PersonalAccounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\PersonalAccounting\Traits\HasTenant;
use Modules\PersonalAccounting\Traits\HasUser;

/**
 * A wallet / bank / mobile-banking account the user tracks money in.
 */
class PersonalAccount extends Model
{
    use HasFactory, HasTenant, HasUser;

    protected $guarded = [];

    protected $casts = [
        'balance' => 'decimal:2',
        'is_default' => 'boolean',
    ];

    public const TYPES = ['cash', 'bank', 'mobile_banking'];

    public function transactions(): HasMany
    {
        return $this->hasMany(PersonalTransaction::class, 'account_id');
    }

    public function recurringTransactions(): HasMany
    {
        return $this->hasMany(PersonalRecurringTransaction::class, 'account_id');
    }

    public function isDebit(): bool
    {
        return $this->balance < 0;
    }
}
