<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'key',
        'value',
        'type',
        'group',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    public static function get(string $key, mixed $default = null, ?string $tenantId = null): mixed
    {
        $query = static::where('key', $key);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $setting = $query->first();

        if (! $setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => (bool) $setting->value,
            'integer' => (int) $setting->value,
            'json' => $setting->value,
            default => $setting->value,
        };
    }

    public static function set(string $key, mixed $value, string $type = 'string', ?string $tenantId = null): void
    {
        static::updateOrCreate(
            [
                'key' => $key,
                'tenant_id' => $tenantId ?? tenant()?->id,
            ],
            [
                'value' => $value,
                'type' => $type,
            ]
        );
    }

    public static function getDefaultSettings(): array
    {
        return [
            [
                'key' => 'company_name',
                'value' => null,
                'type' => 'string',
                'group' => 'general',
            ],
            [
                'key' => 'timezone',
                'value' => 'UTC',
                'type' => 'string',
                'group' => 'general',
            ],
            [
                'key' => 'currency',
                'value' => 'USD',
                'type' => 'string',
                'group' => 'billing',
            ],
            [
                'key' => 'invoice_prefix',
                'value' => 'INV-',
                'type' => 'string',
                'group' => 'billing',
            ],
            [
                'key' => 'invoice_number_start',
                'value' => 1001,
                'type' => 'integer',
                'group' => 'billing',
            ],
            [
                'key' => 'default_plan_id',
                'value' => null,
                'type' => 'integer',
                'group' => 'billing',
            ],
            [
                'key' => 'require_customer_approval',
                'value' => false,
                'type' => 'boolean',
                'group' => 'customers',
            ],
            [
                'key' => 'auto_suspend_unpaid',
                'value' => true,
                'type' => 'boolean',
                'group' => 'billing',
            ],
            [
                'key' => 'grace_period_days',
                'value' => 7,
                'type' => 'integer',
                'group' => 'billing',
            ],
        ];
    }
}
