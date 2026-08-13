<?php

namespace Modules\PersonalAccounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\PersonalAccounting\Traits\HasTenant;
use Modules\PersonalAccounting\Traits\HasUser;

/**
 * A record of a CSV import: how many rows were imported/skipped/failed.
 */
class PersonalImportLog extends Model
{
    use HasFactory, HasTenant, HasUser;

    protected $guarded = [];

    protected $casts = [
        'error_log' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(PersonalAccount::class, 'account_id');
    }
}
