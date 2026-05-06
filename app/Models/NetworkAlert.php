<?php

namespace App\Models;

use Database\Factories\NetworkAlertFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkAlert extends Model
{
    /** @use HasFactory<NetworkAlertFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'router_id',
        'severity',
        'category',
        'message',
        'status',
        'occurred_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'resolved_at' => 'datetime',
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

    public function scopeActive($query)
    {
        $query->where('status', 'active');
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query): void {
            if (auth()->check() && auth()->user()->tenant_id) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            }
        });

        static::creating(function (NetworkAlert $alert): void {
            if (auth()->check() && empty($alert->tenant_id)) {
                $alert->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
