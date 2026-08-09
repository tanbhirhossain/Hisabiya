<?php

namespace Modules\PersonalAccounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\PersonalAccounting\Traits\HasTenant;
use Modules\PersonalAccounting\Traits\HasUser;

/**
 * A personal loan — either money the user borrowed (a liability) or money they
 * lent out (an asset). Tracks principal, interest, amortisation and status.
 */
class PersonalLoan extends Model
{
    use HasFactory, HasTenant, HasUser;

    protected $guarded = [];

    protected $casts = [
        'principal_amount' => 'decimal:2',
        'interest_rate' => 'decimal:4',
        'remaining_balance' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'payment_amount' => 'decimal:2',
        'start_date' => 'date',
        'due_date' => 'date',
        'next_payment_date' => 'date',
    ];

    public const DIRECTIONS = ['borrowed', 'lent'];
    public const STATUSES = ['active', 'completed', 'overdue', 'closed'];
    public const INTEREST_TYPES = ['simple', 'compound', 'flat'];
    public const PAYMENT_FREQUENCIES = ['weekly', 'biweekly', 'monthly', 'quarterly', 'yearly'];

    /** Whether this loan represents money the user still owes. */
    public function isLiability(): bool
    {
        return $this->direction === 'borrowed';
    }

    /** Whether this loan represents money owed to the user. */
    public function isAsset(): bool
    {
        return $this->direction === 'lent';
    }

    public function progressPercent(): float
    {
        return $this->principal_amount > 0
            ? round(($this->total_paid / $this->principal_amount) * 100, 2)
            : 0.0;
    }

    public function isOverdue(): bool
    {
        return $this->status === 'active'
            && $this->next_payment_date !== null
            && $this->next_payment_date->isPast();
    }

    public function isSettled(): bool
    {
        return $this->remaining_balance <= 0;
    }
}
