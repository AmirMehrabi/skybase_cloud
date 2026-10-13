<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToUserGroup;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiusReply extends Model
{
    use BelongsToTenant;
    use BelongsToUserGroup;

    protected $table = 'radreply';

    protected $fillable = [
        'tenant_id',
        'username',
        'attribute',
        'op',
        'value',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', TenantScope::apply(...));

        static::creating(function (RadiusReply $radiusReply): void {
            if (auth()->check() && empty($radiusReply->tenant_id)) {
                $radiusReply->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
