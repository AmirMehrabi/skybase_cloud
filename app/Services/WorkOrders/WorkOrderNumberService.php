<?php

namespace App\Services\WorkOrders;

use App\Models\WorkOrder;

class WorkOrderNumberService
{
    public function next(string $tenantId): string
    {
        $prefix = 'WO-'.now()->format('ymd');
        $latest = WorkOrder::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('work_order_number', 'like', $prefix.'-%')
            ->lockForUpdate()
            ->orderByDesc('work_order_number')
            ->value('work_order_number');

        $sequence = $latest ? ((int) str($latest)->afterLast('-')->toString()) + 1 : 1;

        return sprintf('%s-%04d', $prefix, $sequence);
    }
}
