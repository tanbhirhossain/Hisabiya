<?php

namespace Modules\CORE\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Links a user to a module within a tenant, with a module-level role.
 * One user can be a member of multiple tenants/modules.
 */
class Membership extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public const ROLES = ['owner', 'manager', 'viewer'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function canManageUsers(): bool
    {
        return in_array($this->role, ['owner', 'manager'], true);
    }
}
