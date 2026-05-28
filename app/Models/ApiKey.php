<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    protected $fillable = [
        'name',
        'key',
        'plain_key',
        'plain_key_prefix',
        'permissions',
        'rate_limit',
        'is_active',
        'last_used_at',
        'expires_at',
        'created_by',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
        'rate_limit' => 'integer',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $hidden = ['key'];

    /**
     * All available permissions for the external API.
     */
    public const AVAILABLE_PERMISSIONS = [
        'invoices.create',
        'invoices.read',
        'quotes.create',
        'quotes.read',
        'quotes.convert',
        'clients.create',
        'clients.read',
    ];

    /**
     * Generate a new API key, returning the plain text token.
     * The hashed version is stored in the model.
     */
    public static function generateToken(): array
    {
        $plainToken = 'gb_' . Str::random(48);
        $hashedToken = hash('sha256', $plainToken);
        $prefix = substr($plainToken, 0, 12);

        return [
            'plain' => $plainToken,
            'hash' => $hashedToken,
            'prefix' => $prefix,
        ];
    }

    /**
     * Find an API key by its plain text token.
     */
    public static function findByToken(string $plainToken): ?self
    {
        $hash = hash('sha256', $plainToken);
        return static::where('key', $hash)->first();
    }

    /**
     * Check if this key has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];
        return in_array($permission, $permissions) || in_array('*', $permissions);
    }

    /**
     * Check if this key is currently valid (active, not expired).
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Record usage (update last_used_at).
     */
    public function recordUsage(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Relationship to the user who created this key.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
