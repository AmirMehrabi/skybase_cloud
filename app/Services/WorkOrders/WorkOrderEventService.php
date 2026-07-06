<?php

namespace App\Services\WorkOrders;

use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderEvent;

class WorkOrderEventService
{
    /** @param array<string, mixed>|null $oldValues @param array<string, mixed>|null $newValues @param array<string, mixed>|null $metadata */
    public function record(WorkOrder $workOrder, string $eventType, ?User $actor, ?array $oldValues = null, ?array $newValues = null, ?array $metadata = null): WorkOrderEvent
    {
        return WorkOrderEvent::create([
            'tenant_id' => $workOrder->tenant_id,
            'work_order_id' => $workOrder->id,
            'actor_user_id' => $actor?->id,
            'event_type' => $eventType,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => $metadata,
        ]);
    }
}
