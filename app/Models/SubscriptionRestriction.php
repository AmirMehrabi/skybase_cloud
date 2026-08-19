<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionRestriction extends Model
{
    protected $fillable = ['tenant_id', 'subscription_id', 'type', 'reason', 'metadata', 'effective_at', 'cleared_at', 'created_by', 'cleared_by'];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'effective_at' => 'datetime', 'cleared_at' => 'datetime'];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('cleared_at');
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
