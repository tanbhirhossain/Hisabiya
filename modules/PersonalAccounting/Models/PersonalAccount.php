<?php

namespace Modules\PersonalAccounting\Models;

use Illuminate\Database\Eloquent\Builder;
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
        'is_archived' => 'boolean',
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

    /**
     * Only non-archived accounts.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where($this->getTable().'.is_archived', false);
    }

    /**
     * When the default account is deleted, reassign default to the next
     * available (non-archived) account for the same tenant + user.
     */
    protected static function booted(): void
    {
        static::deleted(function (PersonalAccount $account): void {
            if (! $account->is_default) {
                return;
            }

            $replacement = static::query()
                ->where('tenant_id', $account->tenant_id)
                ->where('user_id', $account->user_id)
                ->where('id', '!=', $account->id)
                ->active()
                ->orderBy('id')
                ->first();

            if ($replacement) {
                $replacement->forceFill(['is_default' => true])->save();
            }
        });
    }
}
