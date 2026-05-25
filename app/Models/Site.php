<?php

namespace App\Models;

use Database\Factories\SiteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    /** @use HasFactory<SiteFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'description',
        'address',
        'latitude',
        'longitude',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function routers(): HasMany
    {
        return $this->hasMany(Router::class);
    }

    public function scopeActive($query)
    {
        $query->where('status', 'active');
    }

    public function scopeFilter($query, array $filters)
    {
        $query
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('status', $status);
            });
    }

    public static function getStats(): array
    {
        $query = self::query();

        return [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->where('status', 'active')->count(),
            'routers' => Router::query()->whereNotNull('site_id')->count(),
            'withOfflineRouters' => (clone $query)->whereHas('routers', fn ($query) => $query->where('status', 'offline'))->count(),
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check() && auth()->user()->tenant_id) {
                $query->where($query->qualifyColumn('tenant_id'), auth()->user()->tenant_id);
            }
        });

        static::creating(function (Site $site) {
            if (auth()->check() && empty($site->tenant_id)) {
                $site->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
