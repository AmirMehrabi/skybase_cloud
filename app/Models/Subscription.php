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

    protected $fillable = ['tenant_id', 'customer_id', 'subscription_code', 'plan_id', 'router_id', 'site', 'connection_type', 'ip_address', 'mac_address', 'ip_pool_id', 'ip_management', 'pppoe_username', 'pppoe_password', 'base_price', 'discount_amount', 'discount_type', 'tax_amount', 'total_price', 'billing_cycle', 'status', 'start_date', 'end_date', 'activation_date', 'suspended_at', 'cancelled_at', 'notes'];

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

    public function ipPool(): BelongsTo
    {
        return $this->belongsTo(IpPool::class);
    }

    public function ipAddress()
    {
        return $this->hasOne(IpAddress::class, 'subscription_code', 'subscription_code');
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
        $query
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('subscription_code', 'like', "%{$search}%")->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
                    });
                });
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($filters['plan'] ?? null, function ($query, $plan) {
                $query->where('plan_id', $plan);
            })
            ->when($filters['customer'] ?? null, function ($query, $customer) {
                $query->where('customer_id', $customer);
            });
    }

    public function scopeForCustomer($query, $customerId)
    {
        $query->where('customer_id', $customerId);
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active' && (!$this->end_date || $this->end_date->isFuture());
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->end_date && $this->end_date->isPast();
    }

    /**
     * Check if this is a PPPoE connection.
     */
    public function isPppoe(): bool
    {
        return $this->connection_type === 'pppoe';
    }

    /**
     * Check if this is a DHCP connection.
     */
    public function isDhcp(): bool
    {
        return $this->connection_type === 'dhcp';
    }

    /**
     * Check if this is a Static IP connection.
     */
    public function isStatic(): bool
    {
        return $this->connection_type === 'static';
    }

    /**
     * Check if IP is system-managed.
     */
    public function isSystemManagedIp(): bool
    {
        return $this->ip_management === 'system';
    }

    /**
     * Check if IP is router-managed.
     */
    public function isRouterManagedIp(): bool
    {
        return $this->ip_management === 'router';
    }

    /**
     * Assign an IP address from the pool.
     */
    public function assignIpAddress(?string $specificIp = null): ?IpAddress
    {
        if (!$this->ip_pool_id || $this->ip_management !== 'system') {
            return null;
        }

        $pool = $this->ipPool;

        if ($specificIp) {
            // Assign specific IP
            $ip = $pool->ipAddresses()->where('ip_address', $specificIp)->first();

            if (!$ip || !$ip->isAvailable()) {
                return null;
            }

            $ip->assignTo($this->customer, $this->mac_address, $this->subscription_code);
            $this->update(['ip_address' => $specificIp]);

            return $ip;
        }

        // Auto-assign next available IP
        $ip = $pool->availableAddresses()->first();

        if (!$ip) {
            return null;
        }

        $ip->assignTo($this->customer, $this->mac_address, $this->subscription_code);
        $this->update(['ip_address' => $ip->ip_address]);

        // Update pool statistics
        $pool->updateStatistics();

        return $ip;
    }

    /**
     * Release the assigned IP address.
     */
    public function releaseIpAddress(): bool
    {
        if (!$this->ip_address || $this->ip_management !== 'system') {
            return false;
        }

        $ip = $this->ipAddress;

        if (!$ip) {
            return false;
        }

        $ip->release();

        // Update pool statistics
        if ($this->ip_pool_id) {
            $this->ipPool->updateStatistics();
        }

        $this->update(['ip_address' => null]);

        return true;
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
            if ($subscription->isDirty('status') && $subscription->status === 'active' && !$subscription->activation_date) {
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
