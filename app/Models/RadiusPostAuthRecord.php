<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiusPostAuthRecord extends Model
{
    use MassPrunable;

    protected $table = 'radpostauth';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'username',
        'pass',
        'reply',
        'authdate',
    ];

    protected function casts(): array
    {
        return [
            'authdate' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function prunable(): Builder
    {
        return static::query()
            ->where('authdate', '<=', now()->subMinutes(20));
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            if (auth()->check() && auth()->user()->tenant_id) {
                $query->where($query->qualifyColumn('tenant_id'), auth()->user()->tenant_id);
            }
        });

        static::creating(function (RadiusPostAuthRecord $radiusPostAuthRecord): void {
            if (auth()->check() && empty($radiusPostAuthRecord->tenant_id)) {
                $radiusPostAuthRecord->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
