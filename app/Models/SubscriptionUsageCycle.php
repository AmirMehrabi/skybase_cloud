<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionUsageCycle extends Model
{
    protected $fillable = ['tenant_id', 'subscription_id', 'plan_id', 'starts_at', 'ends_at', 'allowance_bytes', 'used_upload_bytes', 'used_download_bytes', 'last_accounted_bytes', 'quota_reached_at', 'exempt_until', 'closed_at'];

    protected function casts(): array
    {
        return [
            'allowance_bytes' => 'integer', 'used_upload_bytes' => 'integer', 'used_download_bytes' => 'integer',
            'last_accounted_bytes' => 'integer', 'starts_at' => 'datetime', 'ends_at' => 'datetime',
            'quota_reached_at' => 'datetime', 'exempt_until' => 'datetime', 'closed_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(SubscriptionDataAdjustment::class);
    }

    public function usedBytes(): int
    {
        return $this->used_upload_bytes + $this->used_download_bytes;
    }

    public function activeAdjustmentBytes(): int
    {
        return (int) $this->adjustments()->where('status', 'active')->where('expires_at', '>', now())->sum('bytes');
    }

    public function effectiveAllowanceBytes(): ?int
    {
        return $this->allowance_bytes === null ? null : max(0, $this->allowance_bytes + $this->activeAdjustmentBytes());
    }

    public function isExempt(): bool
    {
        return $this->exempt_until?->isFuture() ?? false;
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
