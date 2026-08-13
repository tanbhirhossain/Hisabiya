<?php

namespace Modules\CORE\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A record of a data backup (CORE all-tenants or single-tenant, or a PRO user's
 * own tenant data).
 */
class Backup extends Model
{
    use HasFactory;

    protected $guarded = [];

    public const TYPES = ['tenant', 'all'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
