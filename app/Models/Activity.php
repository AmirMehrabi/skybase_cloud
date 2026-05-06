<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeForTenant(Builder $query, ?string $tenantId): Builder
    {
        if ($tenantId === null) {
            return $query->whereNull('tenant_id');
        }

        return $query->where('tenant_id', $tenantId);
    }

    protected static function booted(): void
    {
        static::creating(function (Activity $activity): void {
            if ($activity->tenant_id !== null) {
                return;
            }

            $tenantId = data_get($activity->subject, 'tenant_id')
                ?? data_get($activity->causer, 'tenant_id')
                ?? tenant_id()
                ?? auth()->user()?->tenant_id;

            if ($tenantId !== null) {
                $activity->tenant_id = (string) $tenantId;
            }
        });
    }
}
