<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'status',
        'billing_type',
        'balance',
        'credit_limit',
        'tax_exempt',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'credit_limit' => 'decimal:2',
            'tax_exempt' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()->active()->latest()->first();
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

    public function scopeInactive($query)
    {
        $query->where('status', 'inactive');
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
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'inactive', 'label' => 'Inactive'],
                ['value' => 'suspended', 'label' => 'Suspended'],
            ],
        ];
    }

    public static function getStats(): array
    {
        $query = self::query();

        return [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->active()->count(),
            'inactive' => (clone $query)->inactive()->count(),
            'suspended' => (clone $query)->suspended()->count(),
            'overdue' => (clone $query)->whereColumn('balance', '>', 'credit_limit')->count(),
        ];
    }
}
