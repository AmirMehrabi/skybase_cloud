<?php

namespace App\Models;

use Database\Factories\RouterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Router extends Model
{
    /** @use HasFactory<RouterFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'model',
        'vendor',
        'ip_address',
        'api_port',
        'api_username',
        'api_password',
        'ssh_port',
        'location',
        'site',
        'status',
        'last_status_checked_at',
        'last_status_changed_at',
        'status_check_error',
        'version',
        'uptime',
        'cpu_usage',
        'memory_usage',
        'active_sessions_count',
        'total_customers',
        'enable_monitoring',
        'enable_provisioning',
        'netflow_enabled',
        'netflow_collector_host',
        'netflow_collector_port',
        'netflow_version',
        'netflow_interfaces',
        'netflow_sampling_interval',
        'netflow_setup_status',
        'netflow_test_status',
        'netflow_last_setup_at',
        'netflow_last_tested_at',
        'netflow_last_packet_at',
        'netflow_error',
        'timeout',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enable_monitoring' => 'boolean',
            'enable_provisioning' => 'boolean',
            'netflow_enabled' => 'boolean',
            'netflow_collector_port' => 'integer',
            'netflow_version' => 'integer',
            'netflow_sampling_interval' => 'integer',
            'netflow_last_setup_at' => 'datetime',
            'netflow_last_tested_at' => 'datetime',
            'netflow_last_packet_at' => 'datetime',
            'last_status_checked_at' => 'datetime',
            'last_status_changed_at' => 'datetime',
        ];
    }

    /**
     * Check if router is online.
     */
    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function netflowFlows(): HasMany
    {
        return $this->hasMany(NetflowFlow::class);
    }

    public function isMikrotik(): bool
    {
        return strcasecmp((string) $this->vendor, 'Mikrotik') === 0;
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('site', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%");
            });
        })->when($filters['status'] ?? null, function ($query, $status) {
            $query->where('status', $status);
        })->when($filters['vendor'] ?? null, function ($query, $vendor) {
            $query->where('vendor', $vendor);
        })->when($filters['site'] ?? null, function ($query, $site) {
            $query->where('site', $site);
        });
    }

    public static function getFilterOptions(): array
    {
        $sites = self::query()
            ->whereNotNull('site')
            ->where('site', '!=', '')
            ->distinct()
            ->pluck('site')
            ->map(fn ($site) => ['value' => $site, 'label' => $site])
            ->values()
            ->toArray();

        return [
            'sites' => $sites,
        ];
    }

    public static function getStats(): array
    {
        $query = self::query();

        return [
            'total' => (clone $query)->count(),
            'online' => (clone $query)->where('status', 'online')->count(),
            'offline' => (clone $query)->where('status', 'offline')->count(),
            'activeSessions' => (clone $query)->sum('active_sessions_count') ?? 0,
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check() && auth()->user()->tenant_id) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            }
        });

        static::creating(function ($router) {
            if (auth()->check() && empty($router->tenant_id)) {
                $router->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
