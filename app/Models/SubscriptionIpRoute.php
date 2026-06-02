<?php

namespace App\Models;

use Database\Factories\SubscriptionIpRouteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionIpRoute extends Model
{
    /** @use HasFactory<SubscriptionIpRouteFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'ip_pool_id',
        'ip_address_id',
        'ip_address',
        'cidr',
        'routeros_route_id',
        'routeros_comment',
        'routeros_sync_status',
        'routeros_sync_error',
        'routeros_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'cidr' => 'integer',
            'routeros_synced_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query): void {
            $tenantId = tenant_id() ?? auth()->user()?->tenant_id;

            if ($tenantId) {
                $query->where('tenant_id', $tenantId);
            }
        });

        static::creating(function (SubscriptionIpRoute $route): void {
            if (empty($route->tenant_id)) {
                $route->tenant_id = tenant_id() ?? auth()->user()?->tenant_id;
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function ipPool(): BelongsTo
    {
        return $this->belongsTo(IpPool::class);
    }

    public function ipAddressRecord(): BelongsTo
    {
        return $this->belongsTo(IpAddress::class, 'ip_address_id');
    }

    public function destinationAddress(): string
    {
        return $this->ip_address.'/'.($this->cidr ?: 32);
    }

    public function routerOsComment(): string
    {
        return $this->routeros_comment ?: 'skybase:subscription-ip-route:'.$this->id;
    }
}
