<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Router extends Model
{
    /** @use HasFactory<\Database\Factories\RouterFactory> */
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
        'version',
        'uptime',
        'cpu_usage',
        'memory_usage',
        'active_sessions_count',
        'total_customers',
        'enable_monitoring',
        'enable_provisioning',
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
