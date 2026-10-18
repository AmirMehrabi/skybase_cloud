<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class IpdrOutbox extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'delivered_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', TenantScope::apply(...));
    }
}
