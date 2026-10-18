<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class IpdrBinding extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['valid_from' => 'immutable_datetime', 'valid_until' => 'immutable_datetime', 'revision' => 'integer'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', TenantScope::apply(...));
    }

    public function integrationPayload(): array
    {
        return $this->only(['assignment_id', 'subscription_code', 'customer_label', 'address', 'prefix_length', 'origin', 'revision']) + [
            'subscription_id' => (string) $this->subscription_id, 'router_id' => (string) $this->router_id,
            'pool_id' => $this->pool_id === null ? null : (string) $this->pool_id,
            'valid_from' => $this->valid_from->utc()->toIso8601String(), 'valid_until' => $this->valid_until?->utc()->toIso8601String(),
        ];
    }
}
