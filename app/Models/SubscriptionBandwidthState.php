<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionBandwidthState extends Model
{
    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'router_id',
        'interface_name',
        'rx_bps',
        'tx_bps',
        'last_download_bytes',
        'last_upload_bytes',
        'counter_sampled_at',
        'source',
        'sampled_at',
        'last_success_at',
        'consecutive_failures',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'rx_bps' => 'integer',
            'tx_bps' => 'integer',
            'last_download_bytes' => 'integer',
            'last_upload_bytes' => 'integer',
            'counter_sampled_at' => 'datetime',
            'sampled_at' => 'datetime',
            'last_success_at' => 'datetime',
            'consecutive_failures' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
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
