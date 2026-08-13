<?php

namespace Modules\PersonalAccounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\PersonalAccounting\Traits\HasTenant;
use Modules\PersonalAccounting\Traits\HasUser;

/**
 * A log entry for each run of a recurring transaction (success or failure).
 */
class PersonalRecurringLog extends Model
{
    use HasFactory, HasTenant, HasUser;

    protected $guarded = [];

    protected $casts = [
        'ran_at' => 'datetime',
    ];

    public function recurring(): BelongsTo
    {
        return $this->belongsTo(PersonalRecurringTransaction::class, 'recurring_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PersonalTransaction::class, 'transaction_id');
    }
}
