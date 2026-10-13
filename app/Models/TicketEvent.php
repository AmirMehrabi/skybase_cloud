<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToUserGroup;
use App\Models\Scopes\TenantScope;
use Database\Factories\TicketEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketEvent extends Model
{
    use BelongsToTenant;
    use BelongsToUserGroup;

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
        static::addGlobalScope('tenant', TenantScope::apply(...));
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function actorName(): string
    {
        if ($this->actor_type === 'customer' && $this->actor_id) {
            return Customer::withoutGlobalScopes()->where('tenant_id', $this->tenant_id)->find($this->actor_id)?->full_name ?? 'Customer';
        }

        if ($this->actor_type === 'user' && $this->actor_id) {
            return User::query()->where('tenant_id', $this->tenant_id)->find($this->actor_id)?->name ?? 'Staff';
        }

        return 'System';
    }
}
