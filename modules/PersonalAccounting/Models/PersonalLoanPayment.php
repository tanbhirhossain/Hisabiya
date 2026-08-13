<?php

namespace Modules\PersonalAccounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\PersonalAccounting\Traits\HasTenant;
use Modules\PersonalAccounting\Traits\HasUser;

/**
 * A single payment / instalment made against a loan.
 */
class PersonalLoanPayment extends Model
{
    use HasFactory, HasTenant, HasUser;

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'principal_part' => 'decimal:2',
        'interest_part' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'paid_at' => 'date',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(PersonalLoan::class, 'loan_id');
    }
}
