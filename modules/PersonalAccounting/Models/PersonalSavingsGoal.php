<?php

namespace Modules\PersonalAccounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\PersonalAccounting\Traits\HasTenant;
use Modules\PersonalAccounting\Traits\HasUser;

/**
 * A target the user is saving towards, with a running contributed amount.
 */
class PersonalSavingsGoal extends Model
{
    use HasFactory, HasTenant, HasUser;

    protected $guarded = [];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'current_amount' => 'decimal:2',
        'deadline' => 'date',
    ];

    public const STATUSES = ['active', 'completed', 'paused'];

    public function progressPercent(): float
    {
        return $this->target_amount > 0
            ? round(($this->current_amount / $this->target_amount) * 100, 2)
            : 0.0;
    }

    public function isCompleted(): bool
    {
        return $this->current_amount >= $this->target_amount;
    }
}
