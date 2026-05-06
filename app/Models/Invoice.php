<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'subscription_id',
        'invoice_number',
        'billing_period_start',
        'billing_period_end',
        'issue_date',
        'due_date',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'paid_amount',
        'balance_due',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'billing_period_start' => 'date',
            'billing_period_end' => 'date',
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance_due' => 'decimal:2',
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

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->whereIn('status', ['issued', 'partially_paid', 'overdue'])
            ->where('balance_due', '>', 0);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->outstanding()->where('due_date', '<', today());
    }

    public function recalculateTotals(): void
    {
        $subtotal = (float) $this->items()->sum('subtotal');
        $taxTotal = (float) $this->items()->sum('tax_amount');
        $total = (float) $this->items()->sum('total');
        $balanceDue = max(0, $total - (float) $this->paid_amount);

        $status = $this->status;
        if ($balanceDue <= 0) {
            $status = 'paid';
        } elseif ((float) $this->paid_amount > 0) {
            $status = $this->due_date->isPast() ? 'overdue' : 'partially_paid';
        } elseif ($this->due_date->isPast()) {
            $status = 'overdue';
        }

        $this->update([
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'total' => $total,
            'balance_due' => $balanceDue,
            'status' => $status,
        ]);
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $query) {
            if (auth()->check() && auth()->user()->tenant_id) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            }
        });
    }
}
