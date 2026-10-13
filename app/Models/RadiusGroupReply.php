<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToUserGroup;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiusGroupReply extends Model
{
    use BelongsToTenant;
    use BelongsToUserGroup;

    protected $table = 'radgroupreply';

    protected $fillable = [
        'tenant_id',
        'groupname',
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

        static::creating(function (RadiusGroupReply $radiusGroupReply): void {
            if (auth()->check() && empty($radiusGroupReply->tenant_id)) {
                $radiusGroupReply->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
