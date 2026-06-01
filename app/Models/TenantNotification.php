<?php

namespace App\Models;

use Illuminate\Notifications\DatabaseNotification;

class TenantNotification extends DatabaseNotification
{
    protected $table = 'notifications';

    protected $fillable = [
        'id',
        'tenant_id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeVisible($query)
    {
        return $query->whereNull('archived_at');
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }
}
