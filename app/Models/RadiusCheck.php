<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiusCheck extends Model
{
    protected $table = 'radcheck';

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
        static::addGlobalScope('tenant', function (Builder $query): void {
            if (auth()->check() && auth()->user()->tenant_id) {
                $query->where($query->qualifyColumn('tenant_id'), auth()->user()->tenant_id);
            }
        });

        static::creating(function (RadiusCheck $radiusCheck): void {
            if (auth()->check() && empty($radiusCheck->tenant_id)) {
                $radiusCheck->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
