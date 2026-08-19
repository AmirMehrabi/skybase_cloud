<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUserGroup;
use App\Models\Concerns\LogsTenantActivity;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use BelongsToUserGroup;

    /** @use HasFactory<OrganizationFactory> */
    use HasFactory, LogsTenantActivity, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'user_group_id',
        'code',
        'name',
        'description',
        'status',
        'billing_enabled',
        'billing_disabled_at',
        'default_plan_id',
        'default_billing_cycle',
        'default_grace_period_days',
        'default_discount_type',
        'default_discount_amount',
        'default_tax_percentage',
        'ldap_guid',
        'ldap_domain',
        'ldap_dn',
        'ldap_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'billing_enabled' => 'boolean',
            'billing_disabled_at' => 'datetime',
            'default_grace_period_days' => 'integer',
            'default_discount_amount' => 'decimal:2',
            'default_tax_percentage' => 'decimal:2',
            'ldap_synced_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function defaultPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'default_plan_id');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function scopeActive($query)
    {
        $query->where('status', 'active');
    }

    public function scopeFilter($query, array $filters)
    {
        $query
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($filters['billing'] ?? null, function ($query, $billing) {
                $query->where('billing_enabled', $billing === 'enabled');
            });
    }

    public function requiresBillingDefaults(): bool
    {
        return (bool) $this->billing_enabled;
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check() && auth()->user()->tenant_id) {
                $query->where($query->qualifyColumn('tenant_id'), auth()->user()->tenant_id);
            }
        });

        static::creating(function (Organization $organization) {
            if (empty($organization->code)) {
                $organization->code = self::generateCode();
            }

            if (auth()->check() && empty($organization->tenant_id)) {
                $organization->tenant_id = auth()->user()->tenant_id;
            }
        });
    }

    public static function generateCode(): string
    {
        $prefix = 'ORG';
        $timestamp = now()->format('ymd');
        $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));

        return "{$prefix}-{$timestamp}-{$random}";
    }

    public static function getStats(): array
    {
        $query = self::query();

        return [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->where('status', 'active')->count(),
            'billing_enabled' => (clone $query)->where('billing_enabled', true)->count(),
            'customers' => Customer::query()->whereNotNull('organization_id')->count(),
        ];
    }
}
