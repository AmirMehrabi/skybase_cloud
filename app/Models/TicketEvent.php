<?php

namespace App\Models;

use Database\Factories\TicketEventFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketEvent extends Model
{
    /** @use HasFactory<TicketEventFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'ticket_id',
        'actor_type',
        'actor_id',
        'event_type',
        'old_values',
        'new_values',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            $tenantId = tenant_id() ?? auth()->user()?->tenant_id ?? auth('customer')->user()?->tenant_id;

            if ($tenantId) {
                $query->where($query->qualifyColumn('tenant_id'), $tenantId);
            }
        });
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function actorName(): string
    {
        if ($this->actor_type === 'customer' && $this->actor_id) {
            return Customer::withoutGlobalScopes()->find($this->actor_id)?->full_name ?? 'Customer';
        }

        if ($this->actor_type === 'user' && $this->actor_id) {
            return User::query()->find($this->actor_id)?->name ?? 'Staff';
        }

        return 'System';
    }
}
