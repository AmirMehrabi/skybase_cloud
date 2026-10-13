<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionRestriction extends Model
{
    use BelongsToTenant;

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
        static::addGlobalScope('tenant', TenantScope::apply(...));
    }
}
