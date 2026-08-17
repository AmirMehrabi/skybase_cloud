<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUserGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouterMonitoringState extends Model
{
    use BelongsToUserGroup;

    protected $fillable = [
        'tenant_id',
        'router_id',
        'status',
        'latency_ms',
        'packet_loss_percent',
        'uptime',
        'cpu_usage',
        'memory_usage',
        'active_sessions_count',
        'sampled_at',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'latency_ms' => 'float',
            'packet_loss_percent' => 'float',
            'cpu_usage' => 'integer',
            'memory_usage' => 'integer',
            'active_sessions_count' => 'integer',
            'sampled_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query): void {
            $tenantId = tenant_id() ?? auth()->user()?->tenant_id;

            if ($tenantId) {
                $query->where('tenant_id', $tenantId);
            }
        });
    }
}
