<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class IpPool extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'tenant_id',
        'router_id',
        'network_address',
        'cidr',
        'gateway',
        'dns_primary',
        'dns_secondary',
        'vlan_id',
        'type',
        'status',
        'allow_static',
        'auto_assign',
        'block_reserved',
        'site',
        'total_ips',
        'used_ips',
        'reserved_ips',
        'available_ips',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cidr' => 'integer',
            'vlan_id' => 'integer',
            'allow_static' => 'boolean',
            'auto_assign' => 'boolean',
            'block_reserved' => 'boolean',
            'total_ips' => 'integer',
            'used_ips' => 'integer',
            'reserved_ips' => 'integer',
            'available_ips' => 'integer',
        ];
    }

    /**
     * Get the tenant that owns the IP pool.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the router that owns the IP pool.
     */
    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    /**
     * Get the IP addresses for the pool.
     */
    public function ipAddresses(): HasMany
    {
        return $this->hasMany(IpAddress::class);
    }

    /**
     * Get assigned IP addresses.
     */
    public function assignedAddresses(): HasMany
    {
        return $this->hasMany(IpAddress::class)->where('status', 'assigned');
    }

    /**
     * Get available IP addresses.
     */
    public function availableAddresses(): HasMany
    {
        return $this->hasMany(IpAddress::class)->where('status', 'available');
    }

    /**
     * Get reserved IP addresses.
     */
    public function reservedAddresses(): HasMany
    {
        return $this->hasMany(IpAddress::class)->where('status', 'reserved');
    }

    /**
     * Scope to only include active pools.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to only include pools for a specific router.
     */
    public function scopeForRouter($query, $routerId)
    {
        return $query->where('router_id', $routerId);
    }

    /**
     * Scope to only include pools for a specific site.
     */
    public function scopeForSite($query, $site)
    {
        return $query->where('site', $site);
    }

    /**
     * Scope to only include pools of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include pools with high usage.
     */
    public function scopeHighUsage($query, $threshold = 80)
    {
        return $query->whereRaw('(used_ips / total_ips) * 100 >= ?', [$threshold]);
    }

    /**
     * Get the usage percentage.
     */
    public function getUsagePercentageAttribute(): float
    {
        if ($this->total_ips === 0) {
            return 0;
        }

        return round(($this->used_ips / $this->total_ips) * 100, 2);
    }

    /**
     * Get the CIDR notation.
     */
    public function getCidrNotationAttribute(): string
    {
        return "{$this->network_address}/{$this->cidr}";
    }

    /**
     * Determine if the pool is exhausted.
     */
    public function isExhausted(): bool
    {
        return $this->available_ips === 0 || $this->status === 'exhausted';
    }

    /**
     * Update pool statistics.
     */
    public function updateStatistics(): void
    {
        $this->update([
            'total_ips' => $this->ipAddresses()->count(),
            'used_ips' => $this->ipAddresses()->where('status', 'assigned')->count(),
            'reserved_ips' => $this->ipAddresses()->where('status', 'reserved')->count(),
            'available_ips' => $this->ipAddresses()->where('status', 'available')->count(),
        ]);

        // Update status if exhausted
        if ($this->available_ips === 0 && $this->status !== 'exhausted') {
            $this->update(['status' => 'exhausted']);
        } elseif ($this->available_ips > 0 && $this->status === 'exhausted') {
            $this->update(['status' => 'active']);
        }
    }
}
