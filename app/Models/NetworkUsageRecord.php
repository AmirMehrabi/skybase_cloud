<?php

namespace App\Models;

use Database\Factories\NetworkUsageRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkUsageRecord extends Model
{
    /** @use HasFactory<NetworkUsageRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'subscription_id',
        'router_id',
        'ip_address',
        'download_bytes',
        'upload_bytes',
        'session_seconds',
        'started_at',
        'ended_at',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'download_bytes' => 'integer',
            'upload_bytes' => 'integer',
            'session_seconds' => 'integer',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query): void {
            if (auth()->check() && auth()->user()->tenant_id) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            }
        });

        static::creating(function (NetworkUsageRecord $record): void {
            if (auth()->check() && empty($record->tenant_id)) {
                $record->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
