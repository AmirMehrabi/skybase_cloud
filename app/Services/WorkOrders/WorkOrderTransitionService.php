<?php

namespace App\Services\WorkOrders;

use App\Enums\WorkOrderStatus;
use App\Enums\WorkOrderType;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkOrderTransitionService
{
    public function __construct(private WorkOrderEventService $events) {}

    /** @param array<string, mixed> $context */
    public function transition(WorkOrder $workOrder, WorkOrderStatus $target, User $actor, array $context = []): WorkOrder
    {
        return DB::transaction(function () use ($workOrder, $target, $actor, $context): WorkOrder {
            $workOrder = WorkOrder::query()->lockForUpdate()->findOrFail($workOrder->id);
            $current = $workOrder->status;

            if (! in_array($target->value, $current->allowedTransitions(), true)) {
                throw ValidationException::withMessages(['status' => "Cannot move a work order from {$current->value} to {$target->value}."]);
            }

            if ($target === WorkOrderStatus::Scheduled && (! $workOrder->assigned_user_id || ! $workOrder->scheduled_start_at || ! $workOrder->scheduled_end_at)) {
                throw ValidationException::withMessages(['status' => 'Scheduling requires an assigned technician and appointment window.']);
            }

            if (in_array($target, [WorkOrderStatus::ReadyForActivation, WorkOrderStatus::Completed], true) && ! $workOrder->requiredTasksComplete()) {
                throw ValidationException::withMessages(['status' => 'Complete every required checklist item first.']);
            }

            if ($target === WorkOrderStatus::Completed && $workOrder->type === WorkOrderType::NewInstallation && ! $workOrder->subscription_id) {
                throw ValidationException::withMessages(['status' => 'A new installation must be provisioned before completion.']);
            }

            if ($target === WorkOrderStatus::Blocked && blank($context['blocked_reason'] ?? null)) {
                throw ValidationException::withMessages(['blocked_reason' => 'A blocked reason is required.']);
            }

            if ($target === WorkOrderStatus::Cancelled && blank($context['cancellation_reason'] ?? null)) {
                throw ValidationException::withMessages(['cancellation_reason' => 'A cancellation reason is required.']);
            }

            if ($target === WorkOrderStatus::Completed && blank($context['completion_notes'] ?? null)) {
                throw ValidationException::withMessages(['completion_notes' => 'Completion notes are required.']);
            }

            $updates = ['status' => $target];
            if ($target === WorkOrderStatus::InProgress) {
                $updates['started_at'] = $workOrder->started_at ?? now();
            }
            if ($target === WorkOrderStatus::Completed) {
                $updates += ['completed_at' => now(), 'completed_by_user_id' => $actor->id, 'completion_notes' => $context['completion_notes']];
            }
            if ($target === WorkOrderStatus::Cancelled) {
                $updates += ['cancelled_at' => now(), 'cancellation_reason' => $context['cancellation_reason']];
            }
            if ($target === WorkOrderStatus::Blocked) {
                $updates += ['blocked_reason' => $context['blocked_reason'], 'follow_up_at' => $context['follow_up_at'] ?? null];
            } else {
                $updates['blocked_reason'] = null;
            }

            $workOrder->forceFill($updates)->save();
            $this->events->record($workOrder, 'work_order.status_changed', $actor, ['status' => $current->value], ['status' => $target->value]);

            return $workOrder->refresh();
        });
    }
}
