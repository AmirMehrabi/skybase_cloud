<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToUserGroup;
use App\Models\Scopes\TenantScope;
use Database\Factories\NetflowFlowFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetflowFlow extends Model
{
    use BelongsToTenant;
    use BelongsToUserGroup;

    /** @use HasFactory<NetflowFlowFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'router_id',
        'exporter_ip',
        'source_ip',
        'destination_ip',
        'source_port',
        'destination_port',
        'protocol',
        'bytes',
        'packets',
        'flow_started_at',
        'flow_ended_at',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'source_port' => 'integer',
            'destination_port' => 'integer',
            'protocol' => 'integer',
            'bytes' => 'integer',
            'packets' => 'integer',
            'flow_started_at' => 'datetime',
            'flow_ended_at' => 'datetime',
            'received_at' => 'datetime',
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

        static::creating(function (NetflowFlow $flow): void {
            if (auth()->check() && empty($flow->tenant_id)) {
                $flow->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
