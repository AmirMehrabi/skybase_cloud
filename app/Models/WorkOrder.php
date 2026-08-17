<?php

namespace App\Models;

use App\Enums\WorkOrderPriority;
use App\Enums\WorkOrderStatus;
use App\Enums\WorkOrderType;
use App\Models\Concerns\BelongsToUserGroup;
use Database\Factories\WorkOrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkOrder extends Model
{
    use BelongsToUserGroup;

    /** @use HasFactory<WorkOrderFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => WorkOrderType::class,
            'priority' => WorkOrderPriority::class,
            'status' => WorkOrderStatus::class,
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'requested_at' => 'datetime',
            'promised_at' => 'datetime',
            'scheduled_start_at' => 'datetime',
            'scheduled_end_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'follow_up_at' => 'datetime',
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

        static::creating(function (WorkOrder $workOrder): void {
            $workOrder->tenant_id ??= tenant_id() ?? auth()->user()?->tenant_id;
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function sourceTicket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'source_ticket_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function accessPoint(): BelongsTo
    {
        return $this->belongsTo(AccessPoint::class);
    }

    public function assignedTeam(): BelongsTo
    {
        return $this->belongsTo(TicketTeam::class, 'assigned_team_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(WorkOrderTask::class)->orderBy('sort_order');
    }

    public function events(): HasMany
    {
        return $this->hasMany(WorkOrderEvent::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(WorkOrderNote::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(WorkOrderAttachment::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(WorkOrderAppointment::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(WorkOrderMaterial::class);
    }

    public function requiredTasksComplete(): bool
    {
        return ! $this->tasks()->where('is_required', true)->where('status', '!=', 'completed')->exists();
    }
}
