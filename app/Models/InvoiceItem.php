<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'invoice_id',
        'subscription_item_id',
        'description',
        'quantity',
        'unit_price',
        'discount_amount',
        'discount_type',
        'tax_percentage',
        'tax_amount',
        'subtotal',
        'total',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $item): void {
            $item->tenant_id ??= Invoice::withoutGlobalScopes()->whereKey($item->invoice_id)->value('tenant_id');
        });
    }

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_percentage' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function subscriptionItem(): BelongsTo
    {
        return $this->belongsTo(SubscriptionItem::class);
    }
}
