<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionDataAdjustment extends Model
{
    protected $fillable = ['tenant_id', 'subscription_id', 'subscription_usage_cycle_id', 'type', 'bytes', 'amount', 'currency', 'status', 'reason', 'expires_at', 'created_by'];

    protected function casts(): array
    {
        return ['bytes' => 'integer', 'amount' => 'decimal:2', 'expires_at' => 'datetime'];
    }

    public function usageCycle(): BelongsTo
    {
        return $this->belongsTo(SubscriptionUsageCycle::class, 'subscription_usage_cycle_id');
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            if ($tenantId = tenant_id() ?? auth()->user()?->tenant_id) {
                $query->where('tenant_id', $tenantId);
            }
        });
    }
}
