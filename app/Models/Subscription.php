<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'subscription_code',
        'plan_id',
        'router_id',
        'site',
        'ip_address',
        'pppoe_username',
        'pppoe_password',
        'base_price',
        'discount_amount',
        'discount_type',
        'tax_amount',
        'total_price',
        'billing_cycle',
        'status',
        'start_date',
        'end_date',
        'activation_date',
        'suspended_at',
        'cancelled_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_price' => 'decimal:2',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'activation_date' => 'datetime',
            'suspended_at' => 'datetime',
            'cancelled_at' => 'datetime',
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

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SubscriptionItem::class);
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

    public function scopeCancelled($query)
    {
        $query->where('status', 'cancelled');
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('subscription_code', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        })->when($filters['status'] ?? null, function ($query, $status) {
            $query->where('status', $status);
        })->when($filters['plan'] ?? null, function ($query, $plan) {
            $query->where('plan_id', $plan);
        })->when($filters['customer'] ?? null, function ($query, $customer) {
            $query->where('customer_id', $customer);
        });
    }

    public function scopeForCustomer($query, $customerId)
    {
        $query->where('customer_id', $customerId);
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active' &&
               (! $this->end_date || $this->end_date->isFuture());
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->end_date && $this->end_date->isPast();
    }

    public function activate(): void
    {
        $this->update([
            'status' => 'active',
            'activation_date' => $this->activation_date ?? now(),
            'suspended_at' => null,
        ]);
    }

    public function suspend(?string $reason = null): void
    {
        $this->update([
            'status' => 'suspended',
            'suspended_at' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    public function calculateTotalPrice(): float
    {
        $total = $this->items()->sum('total');
        $this->update(['total_price' => $total]);

        return $total;
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check() && auth()->user()->tenant_id) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            }
        });

        static::creating(function ($subscription) {
            if (empty($subscription->subscription_code)) {
                $subscription->subscription_code = self::generateSubscriptionCode();
            }

            if (auth()->check() && empty($subscription->tenant_id)) {
                $subscription->tenant_id = auth()->user()->tenant_id;
            }
        });

        static::updating(function ($subscription) {
            if ($subscription->isDirty('status') && $subscription->status === 'active' && ! $subscription->activation_date) {
                $subscription->activation_date = now();
            }
        });
    }

    public static function generateSubscriptionCode(): string
    {
        $prefix = 'SUB';
        $timestamp = now()->format('ymd');
        $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));

        return "{$prefix}-{$timestamp}-{$random}";
    }

    public static function getStats(): array
    {
        $query = self::query();

        return [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->active()->count(),
            'suspended' => (clone $query)->suspended()->count(),
            'pending' => (clone $query)->pending()->count(),
            'cancelled' => (clone $query)->cancelled()->count(),
        ];
    }
}
