<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToUserGroup;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiusUserGroup extends Model
{
    use BelongsToTenant;
    use BelongsToUserGroup;

    protected $table = 'radusergroup';

    protected $fillable = [
        'tenant_id',
        'username',
        'groupname',
        'priority',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', TenantScope::apply(...));

        static::creating(function (RadiusUserGroup $radiusUserGroup): void {
            if (auth()->check() && empty($radiusUserGroup->tenant_id)) {
                $radiusUserGroup->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
