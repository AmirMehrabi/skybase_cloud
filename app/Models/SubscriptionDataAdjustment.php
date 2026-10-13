<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionDataAdjustment extends Model
{
    use BelongsToTenant;

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
        static::addGlobalScope('tenant', TenantScope::apply(...));
    }
}
