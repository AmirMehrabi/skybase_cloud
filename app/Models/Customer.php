<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'customer_code',
        'customer_type',
        'first_name',
        'last_name',
        'company_name',
        'name',
        'national_id',
        'email',
        'phone',
        'mobile',
        'whatsapp',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country',
        'plan',
        'site',
        'router',
        'ip_address',
        'pppoe_username',
        'pppoe_password',
        'status',
        'billing_type',
        'billing_cycle',
        'balance',
        'credit_limit',
        'tax_exempt',
        'activation_date',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'credit_limit' => 'decimal:2',
            'tax_exempt' => 'boolean',
            'activation_date' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('customer_code', 'like', "%{$search}%");
            });
        })->when($filters['status'] ?? null, function ($query, $status) {
            $query->where('status', $status);
        })->when($filters['plan'] ?? null, function ($query, $plan) {
            $query->where('plan', $plan);
        })->when($filters['site'] ?? null, function ($query, $site) {
            $query->where('site', $site);
        })->when($filters['router'] ?? null, function ($query, $router) {
            $query->where('router', $router);
        });
    }

    public function scopeActive($query)
    {
        $query->where('status', 'active');
    }

    public function scopeSuspended($query)
    {
        $query->where('status', 'suspended');
    }

    public function scopePending($query)
    {
        $query->where('status', 'pending');
    }

    public function getFullNameAttribute(): string
    {
        if ($this->customer_type === 'business') {
            return $this->company_name ?? $this->name;
        }

        return trim(($this->first_name ?? '').' '.($this->last_name ?? '')) ?: $this->name;
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->balance > $this->credit_limit;
    }

    public function getGravatarAttribute(): string
    {
        return sprintf('https://www.gravatar.com/avatar/%s?s=80&d=mp', md5(strtolower(trim($this->email ?? ''))));
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check() && auth()->user()->tenant_id) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            }
        });

        static::creating(function ($customer) {
            if (empty($customer->customer_code)) {
                $customer->customer_code = self::generateCustomerCode();
            }

            if (auth()->check() && empty($customer->tenant_id)) {
                $customer->tenant_id = auth()->user()->tenant_id;
            }
        });

        static::updating(function ($customer) {
            if ($customer->isDirty('status') && $customer->status === 'active' && ! $customer->activation_date) {
                $customer->activation_date = now();
            }
        });
    }

    public static function generateCustomerCode(): string
    {
        $prefix = 'CUS';
        $timestamp = now()->format('ymd');
        $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));

        return "{$prefix}-{$timestamp}-{$random}";
    }

    public static function getFilterOptions(): array
    {
        return [
            'statuses' => [
                ['value' => 'pending', 'label' => 'Pending'],
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'suspended', 'label' => 'Suspended'],
                ['value' => 'inactive', 'label' => 'Inactive'],
            ],
            'plans' => [
                ['value' => 'Fiber 50 Mbps', 'label' => 'Fiber 50 Mbps'],
                ['value' => 'Fiber 100 Mbps', 'label' => 'Fiber 100 Mbps'],
                ['value' => 'Fiber 200 Mbps', 'label' => 'Fiber 200 Mbps'],
                ['value' => 'Fiber 500 Mbps', 'label' => 'Fiber 500 Mbps'],
                ['value' => 'Fiber 1 Gbps', 'label' => 'Fiber 1 Gbps'],
            ],
            'sites' => [
                ['value' => 'Downtown Hub', 'label' => 'Downtown Hub'],
                ['value' => 'Business Park', 'label' => 'Business Park'],
                ['value' => 'North Tower', 'label' => 'North Tower'],
                ['value' => 'South Station', 'label' => 'South Station'],
                ['value' => 'West Station', 'label' => 'West Station'],
                ['value' => 'East Center', 'label' => 'East Center'],
            ],
            'routers' => [
                ['value' => 'Mikrotik-01', 'label' => 'Mikrotik-01'],
                ['value' => 'Mikrotik-02', 'label' => 'Mikrotik-02'],
                ['value' => 'Mikrotik-03', 'label' => 'Mikrotik-03'],
                ['value' => 'Cisco-01', 'label' => 'Cisco-01'],
                ['value' => 'Cisco-02', 'label' => 'Cisco-02'],
                ['value' => 'Cisco-03', 'label' => 'Cisco-03'],
            ],
        ];
    }

    public static function getStats(): array
    {
        $query = self::query();

        return [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->active()->count(),
            'suspended' => (clone $query)->suspended()->count(),
            'overdue' => (clone $query)->whereColumn('balance', '>', 'credit_limit')->count(),
        ];
    }
}
