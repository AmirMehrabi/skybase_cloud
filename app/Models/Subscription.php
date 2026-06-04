<?php

namespace App\Models;

use App\Models\Concerns\LogsTenantActivity;
use App\Services\RadiusProvisioningService;
use App\Services\SubscriptionIpRouteSyncService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use LdapRecord\Laravel\ImportableFromLdap;
use LdapRecord\Laravel\LdapImportable;

class Subscription extends Model implements LdapImportable
{
    use HasFactory, ImportableFromLdap, LogsTenantActivity, SoftDeletes;

    protected $fillable = ['tenant_id', 'customer_id', 'subscription_code', 'name', 'service_type', 'plan_id', 'router_id', 'site', 'connection_type', 'ip_address', 'mac_address', 'ip_pool_id', 'ip_management', 'pppoe_username', 'pppoe_password', 'connection_status', 'connection_status_checked_at', 'base_price', 'discount_amount', 'discount_type', 'tax_amount', 'total_price', 'billing_cycle', 'billing_enabled', 'grace_period_days', 'next_billing_date', 'last_billed_at', 'billing_disabled_at', 'status', 'start_date', 'end_date', 'activation_date', 'suspended_at', 'cancelled_at', 'notes', 'ldap_guid', 'ldap_domain', 'ldap_dn', 'ldap_synced_at'];

    protected function activityLogExcept(): array
    {
        return ['pppoe_password', 'updated_at', 'deleted_at'];
    }

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_price' => 'decimal:2',
            'billing_enabled' => 'boolean',
            'grace_period_days' => 'integer',
            'next_billing_date' => 'date',
            'last_billed_at' => 'datetime',
            'billing_disabled_at' => 'datetime',
            'connection_status_checked_at' => 'datetime',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'activation_date' => 'datetime',
            'suspended_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'ldap_synced_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query): void {
            $tenantId = tenant_id() ?? auth()->user()?->tenant_id;

            if ($tenantId) {
                $query->where('tenant_id', $tenantId);
            }
        });

        static::creating(function (Subscription $subscription): void {
            if (empty($subscription->subscription_code)) {
                $subscription->subscription_code = self::generateSubscriptionCode();
            }

            if (empty($subscription->tenant_id)) {
                $subscription->tenant_id = tenant_id() ?? auth()->user()?->tenant_id;
            }

            if (empty($subscription->name) && $subscription->customer_id) {
                $subscription->name = self::defaultNameForCustomer((int) $subscription->customer_id);
            }

            if (empty($subscription->service_type)) {
                $subscription->service_type = 'hotspot';
            }

            if (! $subscription->isPppoe()) {
                $subscription->connection_status = null;

                return;
            }

            if (blank($subscription->connection_status)) {
                $subscription->connection_status = 'offline';
            }
        });

        static::updating(function (Subscription $subscription): void {
            if ($subscription->isDirty('status') && $subscription->status === 'active' && ! $subscription->activation_date) {
                $subscription->activation_date = now();
            }
        });

        static::saved(function (Subscription $subscription): void {
            app(RadiusProvisioningService::class)->syncSubscription(
                $subscription,
                $subscription->wasChanged('pppoe_username') ? $subscription->getOriginal('pppoe_username') : null,
            );

            if ($subscription->wasChanged(['ip_address', 'router_id', 'status'])) {
                app(SubscriptionIpRouteSyncService::class)->syncRoutes($subscription);
            }
        });

        static::deleting(function (Subscription $subscription): void {
            app(SubscriptionIpRouteSyncService::class)->removeRoutes($subscription);
        });

        static::deleted(function (Subscription $subscription): void {
            app(RadiusProvisioningService::class)->removeSubscription($subscription);
        });
    }

    public function getLdapGuidColumn(): string
    {
        return 'ldap_guid';
    }

    public function getLdapDomainColumn(): string
    {
        return 'ldap_domain';
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

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function ipRoutes(): HasMany
    {
        return $this->hasMany(SubscriptionIpRoute::class);
    }

    public function bandwidthState(): HasOne
    {
        return $this->hasOne(SubscriptionBandwidthState::class);
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

    public function scopeBillable($query)
    {
        $query->where('billing_enabled', true)
            ->whereIn('status', ['pending', 'active'])
            ->whereHas('customer', function ($query) {
                $query->where('billing_enabled', true);
            })
            ->where(function ($query) {
                $query->whereDoesntHave('customer.organization', function ($query) {
                    $query->where('billing_enabled', true);
                })->orWhereHas('customer.organization', function ($query) {
                    $query->where('billing_enabled', true)
                        ->whereColumn('organizations.default_plan_id', 'subscriptions.plan_id');
                });
            });
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
                    $query->where('subscription_code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('pppoe_username', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($q) use ($search) {
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
        return $this->status === 'active' && (! $this->end_date || $this->end_date->isFuture());
    }

    public static function defaultNameForCustomer(int $customerId): ?string
    {
        $customer = Customer::query()->find($customerId);

        if (! $customer) {
            return null;
        }

        return trim($customer->first_name.' '.$customer->last_name)
            ?: $customer->name
            ?: $customer->company_name;
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->end_date && $this->end_date->isPast();
    }

    public function isBillable(): bool
    {
        return (bool) $this->billing_enabled
            && $this->status !== 'cancelled'
            && (bool) $this->customer?->billing_enabled
            && (! $this->customer?->organization?->billing_enabled
                || (int) $this->plan_id === (int) $this->customer->organization->default_plan_id);
    }

    public function effectiveGracePeriodDays(): int
    {
        return (int) (
            $this->grace_period_days
            ?? ($this->customer?->organization?->billing_enabled ? $this->customer->organization->default_grace_period_days : null)
            ?? $this->plan?->grace_period_days
            ?? 7
        );
    }

    public function billingPeriodEndFor(CarbonInterface $periodStart): CarbonInterface
    {
        return match ($this->billing_cycle) {
            'quarterly' => $periodStart->copy()->addMonthsNoOverflow(3)->subDay(),
            'yearly' => $periodStart->copy()->addYearNoOverflow()->subDay(),
            default => $periodStart->copy()->addMonthNoOverflow()->subDay(),
        };
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
        if (! $this->ip_pool_id || $this->ip_management !== 'system') {
            return null;
        }

        $pool = $this->ipPool;

        if ($specificIp) {
            // Assign specific IP
            $ip = $pool->ipAddresses()->where('ip_address', $specificIp)->first();

            if (! $ip || ! $ip->isAvailable()) {
                return null;
            }

            $ip->assignTo($this->customer, $this->mac_address, $this->subscription_code);
            $this->update(['ip_address' => $specificIp]);

            return $ip;
        }

        // Auto-assign next available IP
        $ip = $pool->availableAddresses()->first();

        if (! $ip) {
            return null;
        }

        $ip->assignTo($this->customer, $this->mac_address, $this->subscription_code);
        $this->update(['ip_address' => $ip->ip_address]);

        // Update pool statistics
        $pool->updateStatistics();

        return $ip;
    }

    /**
     * Suggest the next available IP address from the current pool.
     */
    public function suggestIpAddress(): ?IpAddress
    {
        if (! $this->ip_pool_id || $this->ip_management !== 'system') {
            return null;
        }

        $availableAddresses = $this->ipPool
            ?->availableAddresses()
            ->when($this->ip_address, function ($query): void {
                $query->where('ip_address', '!=', $this->ip_address);
            })
            ->get();

        return $availableAddresses?->sortBy(fn (IpAddress $ipAddress): int => (int) sprintf('%u', ip2long($ipAddress->ip_address)))->first();
    }

    /**
     * Update the subscription IP address while keeping system-managed pools in sync.
     */
    public function updateIpAddress(?string $ipAddress): ?IpAddress
    {
        if (! $this->isSystemManagedIp()) {
            $this->update(['ip_address' => $ipAddress]);

            return null;
        }

        if (blank($ipAddress)) {
            $this->releaseIpAddress();

            return null;
        }

        if ($this->ip_address === $ipAddress) {
            return $this->ipAddress;
        }

        $assignedIp = $this->ipPool?->ipAddresses()
            ->where('ip_address', $ipAddress)
            ->first();

        if (! $assignedIp || ! $assignedIp->isAvailable()) {
            return null;
        }

        if ($this->ipAddress) {
            $this->ipAddress->release();
        }

        $assignedIp->assignTo($this->customer, $this->mac_address, $this->subscription_code);
        $this->update(['ip_address' => $ipAddress]);

        if ($this->ipPool) {
            $this->ipPool->updateStatistics();
        }

        return $assignedIp;
    }

    /**
     * Release the assigned IP address.
     */
    public function releaseIpAddress(): bool
    {
        if (! $this->ip_address || $this->ip_management !== 'system') {
            return false;
        }

        $ip = $this->ipAddress;

        if (! $ip) {
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
