<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiusUserGroup extends Model
{
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
        static::addGlobalScope('tenant', function (Builder $query): void {
            if (auth()->check() && auth()->user()->tenant_id) {
                $query->where($query->qualifyColumn('tenant_id'), auth()->user()->tenant_id);
            }
        });

        static::creating(function (RadiusUserGroup $radiusUserGroup): void {
            if (auth()->check() && empty($radiusUserGroup->tenant_id)) {
                $radiusUserGroup->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
