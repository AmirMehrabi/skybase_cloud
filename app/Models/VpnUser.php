<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToUserGroup;
use App\Models\Scopes\TenantScope;
use Database\Factories\VpnUserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VpnUser extends Model
{
    use BelongsToTenant;
    use BelongsToUserGroup;

    /** @use HasFactory<VpnUserFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id',
        'user_group_id',
        'username',
        'password_hash',
        'active',
        'online',
        'connected_at',
        'disconnected_at',
        'vpn_ip',
        'real_ip',
        'bytes_received',
        'bytes_sent',
        'last_login_at',
        'observed_at',
        'last_seen_at',
        'reserved_vpn_ip',
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
            'observed_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'connected_at' => 'datetime',
            'disconnected_at' => 'datetime',
            'bytes_received' => 'integer',
            'bytes_sent' => 'integer',
        ];
    }

    public function monitoringFresh(): bool
    {
        return $this->observed_at !== null
            && $this->observed_at->gte(now()->subSeconds(config('vpn.freshness_seconds')))
            && $this->observed_at->lte(now()->addSeconds(30));
    }

    public function connectionState(): string
    {
        return $this->monitoringFresh() ? ($this->online ? 'Connected' : 'Disconnected') : 'Unknown';
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
                $query->where('active', filter_var($filters['active'], FILTER_VALIDATE_BOOLEAN));
            })
            ->when(($filters['online'] ?? '') !== '', function (Builder $query) use ($filters): void {
                if ($filters['online'] === 'unknown') {
                    $query->where(fn ($query) => $query->whereNull('observed_at')->orWhere('observed_at', '<', now()->subSeconds(config('vpn.freshness_seconds'))));
                } else {
                    $query->where('observed_at', '>=', now()->subSeconds(config('vpn.freshness_seconds')))->where('online', filter_var($filters['online'], FILTER_VALIDATE_BOOLEAN));
                }
            });
    }

    public static function getStats(): array
    {
        $query = self::query();

        return [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->where('active', true)->count(),
            'inactive' => (clone $query)->where('active', false)->count(),
            'online' => (clone $query)->where('observed_at', '>=', now()->subSeconds(config('vpn.freshness_seconds')))->where('online', true)->count(),
            'offline' => (clone $query)->where('observed_at', '>=', now()->subSeconds(config('vpn.freshness_seconds')))->where('online', false)->count(),
            'unknown' => (clone $query)->where(fn ($query) => $query->whereNull('observed_at')->orWhere('observed_at', '<', now()->subSeconds(config('vpn.freshness_seconds'))))->count(),
            'recentLogins' => (clone $query)->where('last_login_at', '>=', now()->subDays(7))->count(),
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', TenantScope::apply(...));

        static::creating(function (VpnUser $vpnUser): void {
            if (auth()->check() && empty($vpnUser->tenant_id)) {
                $vpnUser->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
