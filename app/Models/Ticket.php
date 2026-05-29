<?php

namespace App\Models;

use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory, SoftDeletes;

    public const STATUS_NEW = 'new';

    public const STATUS_OPEN = 'open';

    public const STATUS_PENDING_CUSTOMER = 'pending_customer';

    public const STATUS_PENDING_STAFF = 'pending_staff';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_NORMAL = 'normal';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_URGENT = 'urgent';

    protected $fillable = [
        'tenant_id',
        'ticket_number',
        'customer_id',
        'subscription_id',
        'ticket_team_id',
        'assigned_user_id',
        'opened_by_customer_id',
        'opened_by_user_id',
        'source',
        'subject',
        'priority',
        'status',
        'first_response_due_at',
        'resolution_due_at',
        'first_staff_response_at',
        'resolved_at',
        'closed_at',
        'last_customer_reply_at',
        'last_staff_reply_at',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'first_response_due_at' => 'datetime',
            'resolution_due_at' => 'datetime',
            'first_staff_response_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'last_customer_reply_at' => 'datetime',
            'last_staff_reply_at' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            $tenantId = tenant_id() ?? auth()->user()?->tenant_id;

            if ($tenantId) {
                $query->where($query->qualifyColumn('tenant_id'), $tenantId);
            }
        });

        static::creating(function (Ticket $ticket): void {
            if (empty($ticket->tenant_id)) {
                $ticket->tenant_id = tenant_id() ?? auth()->user()?->tenant_id ?? auth('customer')->user()?->tenant_id;
            }

            if (empty($ticket->last_activity_at)) {
                $ticket->last_activity_at = now();
            }
        });
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

    public function team(): BelongsTo
    {
        return $this->belongsTo(TicketTeam::class, 'ticket_team_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function openedByCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'opened_by_customer_id');
    }

    public function openedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(TicketEvent::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    public function getSlaStateAttribute(): string
    {
        $now = now();
        $dueAt = $this->first_staff_response_at ? $this->resolution_due_at : $this->first_response_due_at;

        if (! $dueAt || in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED], true)) {
            return 'normal';
        }

        if ($dueAt->isPast()) {
            return 'breached';
        }

        return $now->diffInMinutes($dueAt, false) <= 60 ? 'at_risk' : 'normal';
    }
}
