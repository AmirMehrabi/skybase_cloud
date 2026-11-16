<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SbukPersonnelIdentity extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'customer_id', 'identity_hash', 'external_index'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', TenantScope::apply(...));
    }
}
