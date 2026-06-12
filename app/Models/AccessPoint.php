<?php

namespace App\Models;

use Database\Factories\AccessPointFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccessPoint extends Model
{
    /** @use HasFactory<AccessPointFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'site_id',
        'router_id',
        'name',
        'model',
        'vendor',
        'mac_address',
        'ip_address',
        'serial_number',
        'firmware_version',
        'frequency_band',
        'channel',
        'ssid',
        'tx_power',
        'antenna_type',
        'antenna_gain',
        'height_meters',
        'azimuth',
        'coverage_angle',
        'max_clients',
        'connected_clients',
        'status',
        'last_status_checked_at',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tx_power' => 'integer',
            'antenna_gain' => 'integer',
            'height_meters' => 'decimal:2',
            'azimuth' => 'integer',
            'coverage_angle' => 'integer',
            'max_clients' => 'integer',
            'connected_clients' => 'integer',
            'last_status_checked_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function siteRecord(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('mac_address', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('ssid', 'like', "%{$search}%");
            });
        })->when($filters['status'] ?? null, function ($query, $status) {
            $query->where('status', $status);
        })->when($filters['vendor'] ?? null, function ($query, $vendor) {
            $query->where('vendor', $vendor);
        })->when($filters['site'] ?? null, function ($query, $site) {
            $query->whereHas('siteRecord', fn ($query) => $query->where('name', $site));
        })->when($filters['frequency_band'] ?? null, function ($query, $band) {
            $query->where('frequency_band', $band);
        });
    }

    public static function getFilterOptions(): array
    {
        $sites = Site::query()
            ->orderBy('name')
            ->get(['name'])
            ->map(fn (Site $site) => ['value' => $site->name, 'label' => $site->name]);

        return [
            'sites' => $sites->toArray(),
        ];
    }

    public static function getStats(): array
    {
        $query = self::query();

        return [
            'total' => (clone $query)->count(),
            'online' => (clone $query)->where('status', 'online')->count(),
            'offline' => (clone $query)->where('status', 'offline')->count(),
            'totalConnectedClients' => (clone $query)->sum('connected_clients') ?? 0,
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check() && auth()->user()->tenant_id) {
                $query->where($query->qualifyColumn('tenant_id'), auth()->user()->tenant_id);
            }
        });

        static::creating(function ($accessPoint) {
            if (auth()->check() && empty($accessPoint->tenant_id)) {
                $accessPoint->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
