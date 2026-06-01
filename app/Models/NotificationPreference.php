<?php

namespace App\Models;

use Database\Factories\NotificationPreferenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationPreference extends Model
{
    /** @use HasFactory<NotificationPreferenceFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'notifiable_type',
        'notifiable_id',
        'notifications_enabled',
        'in_app_enabled',
        'email_enabled',
        'sms_enabled',
    ];

    protected function casts(): array
    {
        return [
            'notifications_enabled' => 'boolean',
            'in_app_enabled' => 'boolean',
            'email_enabled' => 'boolean',
            'sms_enabled' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (NotificationPreference $preference): void {
            if (empty($preference->tenant_id)) {
                $preference->tenant_id = tenant_id() ?? auth()->user()?->tenant_id ?? auth('customer')->user()?->tenant_id;
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
