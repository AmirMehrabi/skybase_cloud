<?php

namespace App\Models;

use Database\Factories\VpnUserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VpnUser extends Model
{
    /** @use HasFactory<VpnUserFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id',
        'username',
        'password_hash',
        'active',
        'last_login_at',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'last_login_at' => 'datetime',
            'created_at' => 'datetime',
            'online' => 'boolean',
            'connected_at' => 'datetime',
            'disconnected_at' => 'datetime',
            'bytes_received' => 'integer',
            'bytes_sent' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where('username', 'like', "%{$search}%");
            })
            ->when(($filters['active'] ?? '') !== '', function (Builder $query) use ($filters): void {
                $query->where('active', (bool) $filters['active']);
            });
    }

    public static function getStats(): array
    {
        $query = self::query();

        return [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->where('active', true)->count(),
            'inactive' => (clone $query)->where('active', false)->count(),
            'recentLogins' => (clone $query)->where('last_login_at', '>=', now()->subDays(7))->count(),
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            if (auth()->check() && auth()->user()->tenant_id) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            }
        });

        static::creating(function (VpnUser $vpnUser): void {
            if (auth()->check() && empty($vpnUser->tenant_id)) {
                $vpnUser->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
