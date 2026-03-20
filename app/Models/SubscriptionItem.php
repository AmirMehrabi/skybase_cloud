<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'item_type',
        'item_code',
        'description',
        'plan_id',
        'router_id',
        'quantity',
        'unit_price',
        'discount_amount',
        'discount_type',
        'tax_percentage',
        'tax_amount',
        'subtotal',
        'total',
        'recurring',
        'billing_cycle',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_percentage' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'recurring' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function scopeRecurring($query)
    {
        $query->where('recurring', true);
    }

    public function scopeOnetime($query)
    {
        $query->where('recurring', false);
    }

    public function scopeForType($query, string $type)
    {
        $query->where('item_type', $type);
    }

    /**
     * Calculate the line item total based on quantity, price, discount, and tax
     */
    public function calculateTotals(): void
    {
        $lineTotal = $this->unit_price * $this->quantity;

        // Apply discount
        $discount = 0;
        if ($this->discount_type === 'fixed') {
            $discount = $this->discount_amount;
        } elseif ($this->discount_type === 'percentage') {
            $discount = $lineTotal * ($this->discount_amount / 100);
        }

        $subtotal = max(0, $lineTotal - $discount);

        // Calculate tax
        $tax = $subtotal * ($this->tax_percentage / 100);

        $this->subtotal = $subtotal;
        $this->tax_amount = $tax;
        $this->total = $subtotal + $tax;

        $this->save();
    }

    /**
     * Get the formatted price display
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->unit_price, 2);
    }

    /**
     * Get the formatted total display
     */
    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->total, 2);
    }
}
