<?php

namespace App\Services\WorkOrders;

use App\Enums\WorkOrderStatus;
use App\Jobs\Subscriptions\ActivateSubscriptionJob;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkOrderProvisioningService
{
    public function __construct(private WorkOrderEventService $events) {}

    /** @param array<string, mixed> $data */
    public function provision(WorkOrder $workOrder, User $actor, array $data): Subscription
    {
        $subscription = DB::transaction(function () use ($workOrder, $actor, $data): Subscription {
            $workOrder = WorkOrder::query()->lockForUpdate()->findOrFail($workOrder->id);

            if ($workOrder->subscription_id) {
                return $workOrder->subscription()->firstOrFail();
            }

            if ($workOrder->status !== WorkOrderStatus::ReadyForActivation || ! $workOrder->requiredTasksComplete()) {
                throw ValidationException::withMessages(['status' => 'The work order must be ready for activation with its checklist complete.']);
            }

            $plan = Plan::query()->findOrFail($data['plan_id']);
            $subscription = Subscription::withoutEvents(fn (): Subscription => Subscription::create([
                'tenant_id' => $workOrder->tenant_id,
                'subscription_code' => Subscription::generateSubscriptionCode(),
                'customer_id' => $workOrder->customer_id,
                'name' => $data['name'],
                'service_type' => $data['service_type'],
                'plan_id' => $plan->id,
                'router_id' => $data['router_id'],
                'access_point_id' => $data['access_point_id'] ?? null,
                'site' => trim(implode(', ', array_filter([$workOrder->service_address_line1, $workOrder->service_city]))),
                'connection_type' => $data['connection_type'],
                'pppoe_username' => $data['pppoe_username'] ?? null,
                'pppoe_password' => $data['pppoe_password'] ?? null,
                'mac_address' => $data['mac_address'] ?? null,
                'ip_management' => $data['ip_management'] ?? 'router',
                'ip_address' => $data['ip_address'] ?? null,
                'base_price' => $plan->price,
                'total_price' => $plan->price,
                'billing_cycle' => $plan->billing_cycle,
                'billing_enabled' => true,
                'auto_suspension_enabled' => true,
                'grace_period_days' => 0,
                'next_billing_date' => now()->toDateString(),
                'status' => 'pending',
                'start_date' => now(),
            ]));
            $subscription->forceFill([
                'status' => 'active',
                'activation_date' => now(),
                'activated_by' => $actor->id,
            ])->saveQuietly();

            $workOrder->forceFill(['subscription_id' => $subscription->id, 'plan_id' => $plan->id, 'router_id' => $data['router_id']])->save();
            $this->events->record($workOrder, 'work_order.provisioned', $actor, null, ['subscription_id' => $subscription->id]);

            return $subscription;
        });

        ActivateSubscriptionJob::dispatch($subscription->id, $subscription->tenant_id)
            ->onQueue('subscriptions')
            ->afterCommit();

        return $subscription;
    }
}
