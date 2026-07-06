<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkOrder;

class WorkOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('work_orders.read');
    }

    public function view(User $user, WorkOrder $workOrder): bool
    {
        return $this->sameTenant($user, $workOrder)
            && ($user->hasPermission('work_orders.manage')
                || (int) $workOrder->assigned_user_id === (int) $user->id
                || (int) $workOrder->created_by_user_id === (int) $user->id
                || $user->ticketTeams()->whereKey($workOrder->assigned_team_id)->where('ticket_team_user.is_active', true)->exists());
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('work_orders.create');
    }

    public function update(User $user, WorkOrder $workOrder): bool
    {
        return $this->view($user, $workOrder) && $user->hasPermission('work_orders.update');
    }

    public function assign(User $user, WorkOrder $workOrder): bool
    {
        return $this->sameTenant($user, $workOrder) && $user->hasPermission('work_orders.assign');
    }

    public function schedule(User $user, WorkOrder $workOrder): bool
    {
        return $this->sameTenant($user, $workOrder) && $user->hasPermission('work_orders.schedule');
    }

    public function execute(User $user, WorkOrder $workOrder): bool
    {
        return $this->view($user, $workOrder) && $user->hasPermission('work_orders.execute');
    }

    public function provision(User $user, WorkOrder $workOrder): bool
    {
        return $this->sameTenant($user, $workOrder) && $user->hasPermission('work_orders.provision');
    }

    public function complete(User $user, WorkOrder $workOrder): bool
    {
        return $this->sameTenant($user, $workOrder) && $user->hasPermission('work_orders.complete');
    }

    public function cancel(User $user, WorkOrder $workOrder): bool
    {
        return $this->sameTenant($user, $workOrder) && $user->hasPermission('work_orders.cancel');
    }

    public function delete(User $user, WorkOrder $workOrder): bool
    {
        return $this->sameTenant($user, $workOrder) && $user->hasPermission('work_orders.manage');
    }

    private function sameTenant(User $user, WorkOrder $workOrder): bool
    {
        return (string) $user->tenant_id === (string) $workOrder->tenant_id;
    }
}
