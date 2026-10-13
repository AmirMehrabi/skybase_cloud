<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToUserGroup;
use App\Models\Scopes\TenantScope;
use Database\Factories\NetworkBandwidthSampleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkBandwidthSample extends Model
{
    use BelongsToTenant;
    use BelongsToUserGroup;

    /** @use HasFactory<NetworkBandwidthSampleFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'router_id',
        'interface_name',
        'download_bps',
        'upload_bps',
        'capacity_bps',
        'sampled_at',
    ];

    protected function casts(): array
    {
        return [
            'download_bps' => 'integer',
            'upload_bps' => 'integer',
            'capacity_bps' => 'integer',
            'sampled_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', TenantScope::apply(...));

        static::creating(function (NetworkBandwidthSample $sample): void {
            if (auth()->check() && empty($sample->tenant_id)) {
                $sample->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
