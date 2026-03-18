<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class IpAddress extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'ip_pool_id',
        'ip_address',
        'status',
        'customer_id',
        'mac_address',
        'subscription_code',
        'assigned_at',
        'released_at',
        'notes',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'released_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the tenant that owns the IP address.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the IP pool that owns the IP address.
     */
    public function ipPool(): BelongsTo
    {
        return $this->belongsTo(IpPool::class);
    }

    /**
     * Get the customer that owns the IP address.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Scope to only include available IPs.
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    /**
     * Scope to only include assigned IPs.
     */
    public function scopeAssigned($query)
    {
        return $query->where('status', 'assigned');
    }

    /**
     * Scope to only include reserved IPs.
     */
    public function scopeReserved($query)
    {
        return $query->where('status', 'reserved');
    }

    /**
     * Scope to only include blocked IPs.
     */
    public function scopeBlocked($query)
    {
        return $query->where('status', 'blocked');
    }

    /**
     * Scope to only include IPs for a specific pool.
     */
    public function scopeForPool($query, $poolId)
    {
        return $query->where('ip_pool_id', $poolId);
    }

    /**
     * Scope to only include IPs for a specific customer.
     */
    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Assign the IP to a customer.
     */
    public function assignTo(Customer $customer, ?string $macAddress = null, ?string $subscriptionCode = null): bool
    {
        return $this->update([
            'status' => 'assigned',
            'customer_id' => $customer->id,
            'mac_address' => $macAddress,
            'subscription_code' => $subscriptionCode,
            'assigned_at' => now(),
        ]);
    }

    /**
     * Release the IP address.
     */
    public function release(): bool
    {
        return $this->update([
            'status' => 'available',
            'customer_id' => null,
            'mac_address' => null,
            'subscription_code' => null,
            'released_at' => now(),
        ]);
    }

    /**
     * Reserve the IP address.
     */
    public function reserve(?string $notes = null): bool
    {
        return $this->update([
            'status' => 'reserved',
            'notes' => $notes,
        ]);
    }

    /**
     * Block the IP address.
     */
    public function block(?string $notes = null): bool
    {
        return $this->update([
            'status' => 'blocked',
            'notes' => $notes,
        ]);
    }

    /**
     * Check if the IP is available.
     */
    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    /**
     * Check if the IP is assigned.
     */
    public function isAssigned(): bool
    {
        return $this->status === 'assigned';
    }

    /**
     * Check if the IP is reserved.
     */
    public function isReserved(): bool
    {
        return $this->status === 'reserved';
    }

    /**
     * Check if the IP is blocked.
     */
    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }
}
