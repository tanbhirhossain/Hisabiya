<?php

namespace Modules\CORE\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Simple key/value store for platform settings editable by CORE super admins.
 */
class CoreSetting extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'value' => 'json',
    ];

    /**
     * Get a setting by key with a default fallback.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    /**
     * Set a setting value (creates or updates).
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
