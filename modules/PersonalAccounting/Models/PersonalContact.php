<?php

namespace Modules\PersonalAccounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\PersonalAccounting\Traits\HasTenant;
use Modules\PersonalAccounting\Traits\HasUser;

/**
 * A person or business the user lends to or borrows from.
 */
class PersonalContact extends Model
{
    use HasFactory, HasTenant, HasUser;

    protected $guarded = [];

    public const TYPES = ['person', 'business'];

    public function loans(): HasMany
    {
        return $this->hasMany(PersonalLoan::class, 'contact_id');
    }
}
