<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserGroupAssignmentService
{
    /** @return array{resolved: bool, group_id: int|null} */
    public function inheritedGroup(Model $model): array
    {
        foreach ($this->parentMap()[$model->getTable()] ?? [] as [$foreignKey, $parentTable]) {
            $parentId = $model->getAttribute($foreignKey);

            if (blank($parentId)) {
                continue;
            }

            $query = DB::table($parentTable)->where('id', $parentId);
            $tenantId = $model->getAttribute('tenant_id');

            if ($tenantId !== null) {
                $query->where('tenant_id', $tenantId);
            }

            $parent = $query->first(['user_group_id']);

            if ($parent !== null) {
                return [
                    'resolved' => true,
                    'group_id' => $parent->user_group_id === null ? null : (int) $parent->user_group_id,
                ];
            }
        }

        return ['resolved' => false, 'group_id' => null];
    }

    public function cascadeCustomer(int $customerId, string $tenantId, ?int $groupId): void
    {
        DB::transaction(function () use ($customerId, $tenantId, $groupId): void {
            DB::table('customers')->where('tenant_id', $tenantId)->where('id', $customerId)->update(['user_group_id' => $groupId]);

            $subscriptionIds = DB::table('subscriptions')
                ->where('tenant_id', $tenantId)
                ->where('customer_id', $customerId)
                ->whereNull('organization_id')
                ->pluck('id');
            DB::table('subscriptions')->whereIn('id', $subscriptionIds)->update(['user_group_id' => $groupId]);

            foreach (['subscription_items', 'subscription_ip_routes', 'subscription_bandwidth_states'] as $table) {
                DB::table($table)->whereIn('subscription_id', $subscriptionIds)->update(['user_group_id' => $groupId]);
            }

            $invoiceIds = DB::table('invoices')
                ->where('tenant_id', $tenantId)
                ->where('customer_id', $customerId)
                ->where(function ($query) use ($subscriptionIds): void {
                    $query->whereNull('subscription_id')->orWhereIn('subscription_id', $subscriptionIds);
                })
                ->pluck('id');
            DB::table('invoices')->whereIn('id', $invoiceIds)->update(['user_group_id' => $groupId]);
            DB::table('invoice_items')->whereIn('invoice_id', $invoiceIds)->update(['user_group_id' => $groupId]);
            DB::table('payments')
                ->where('tenant_id', $tenantId)
                ->where('customer_id', $customerId)
                ->where(function ($query) use ($invoiceIds): void {
                    $query->whereNull('invoice_id')->orWhereIn('invoice_id', $invoiceIds);
                })
                ->update(['user_group_id' => $groupId]);

            foreach (['customer_credits', 'customer_notes'] as $table) {
                DB::table($table)->where('tenant_id', $tenantId)->where('customer_id', $customerId)->update(['user_group_id' => $groupId]);
            }

            foreach (['tickets', 'work_orders'] as $table) {
                DB::table($table)
                    ->where('tenant_id', $tenantId)
                    ->where('customer_id', $customerId)
                    ->where(function ($query) use ($subscriptionIds): void {
                        $query->whereNull('subscription_id')->orWhereIn('subscription_id', $subscriptionIds);
                    })
                    ->update(['user_group_id' => $groupId]);
            }

            $ticketIds = DB::table('tickets')
                ->where('tenant_id', $tenantId)
                ->where('customer_id', $customerId)
                ->where(function ($query) use ($subscriptionIds): void {
                    $query->whereNull('subscription_id')->orWhereIn('subscription_id', $subscriptionIds);
                })
                ->pluck('id');
            foreach (['ticket_attachments', 'ticket_events', 'ticket_messages'] as $table) {
                DB::table($table)->whereIn('ticket_id', $ticketIds)->update(['user_group_id' => $groupId]);
            }

            $workOrderIds = DB::table('work_orders')
                ->where('tenant_id', $tenantId)
                ->where('customer_id', $customerId)
                ->where(function ($query) use ($subscriptionIds): void {
                    $query->whereNull('subscription_id')->orWhereIn('subscription_id', $subscriptionIds);
                })
                ->pluck('id');
            foreach (['work_order_appointments', 'work_order_attachments', 'work_order_events', 'work_order_materials', 'work_order_notes', 'work_order_tasks'] as $table) {
                DB::table($table)->whereIn('work_order_id', $workOrderIds)->update(['user_group_id' => $groupId]);
            }
        });
    }

    public function cascadeOrganization(int $organizationId, string $tenantId, ?int $groupId): void
    {
        DB::transaction(function () use ($organizationId, $tenantId, $groupId): void {
            DB::table('organizations')->where('tenant_id', $tenantId)->where('id', $organizationId)->update(['user_group_id' => $groupId]);

            DB::table('subscriptions')
                ->where('tenant_id', $tenantId)
                ->where('organization_id', $organizationId)
                ->pluck('id')
                ->each(fn (int $subscriptionId) => $this->assignSubscriptionOrganization($subscriptionId, $tenantId, $organizationId, $groupId));

            DB::table('customers')
                ->where('tenant_id', $tenantId)
                ->where('organization_id', $organizationId)
                ->pluck('id')
                ->each(fn (int $customerId) => $this->cascadeCustomer($customerId, $tenantId, $groupId));
        });
    }

    public function assignSubscriptionOrganization(int $subscriptionId, string $tenantId, int $organizationId, ?int $groupId): void
    {
        DB::transaction(function () use ($subscriptionId, $tenantId, $organizationId, $groupId): void {
            DB::table('subscriptions')
                ->where('tenant_id', $tenantId)
                ->where('id', $subscriptionId)
                ->update([
                    'organization_id' => $organizationId,
                    'user_group_id' => $groupId,
                    'updated_at' => now(),
                ]);

            foreach (['subscription_items', 'subscription_ip_routes', 'subscription_bandwidth_states'] as $table) {
                DB::table($table)->where('subscription_id', $subscriptionId)->update(['user_group_id' => $groupId]);
            }

            $invoiceIds = DB::table('invoices')
                ->where('tenant_id', $tenantId)
                ->where('subscription_id', $subscriptionId)
                ->pluck('id');
            DB::table('invoices')->whereIn('id', $invoiceIds)->update(['user_group_id' => $groupId]);
            DB::table('invoice_items')->whereIn('invoice_id', $invoiceIds)->update(['user_group_id' => $groupId]);
            DB::table('payments')->whereIn('invoice_id', $invoiceIds)->update(['user_group_id' => $groupId]);

            foreach (['tickets', 'work_orders', 'network_usage_records'] as $table) {
                DB::table($table)->where('tenant_id', $tenantId)->where('subscription_id', $subscriptionId)->update(['user_group_id' => $groupId]);
            }

            $ticketIds = DB::table('tickets')->where('tenant_id', $tenantId)->where('subscription_id', $subscriptionId)->pluck('id');
            foreach (['ticket_attachments', 'ticket_events', 'ticket_messages'] as $table) {
                DB::table($table)->whereIn('ticket_id', $ticketIds)->update(['user_group_id' => $groupId]);
            }

            $workOrderIds = DB::table('work_orders')->where('tenant_id', $tenantId)->where('subscription_id', $subscriptionId)->pluck('id');
            foreach (['work_order_appointments', 'work_order_attachments', 'work_order_events', 'work_order_materials', 'work_order_notes', 'work_order_tasks'] as $table) {
                DB::table($table)->whereIn('work_order_id', $workOrderIds)->update(['user_group_id' => $groupId]);
            }
        });
    }

    public function cascadeSite(int $siteId, string $tenantId, ?int $groupId): void
    {
        DB::transaction(function () use ($siteId, $tenantId, $groupId): void {
            $routerIds = DB::table('routers')->where('tenant_id', $tenantId)->where('site_id', $siteId)->pluck('id');
            $accessPointIds = DB::table('access_points')->where('tenant_id', $tenantId)->where('site_id', $siteId)->pluck('id');

            $linkedSubscriptions = DB::table('subscriptions')
                ->where('tenant_id', $tenantId)
                ->where(function ($query) use ($routerIds, $accessPointIds): void {
                    $query->whereIn('router_id', $routerIds)->orWhereIn('access_point_id', $accessPointIds);
                })
                ->where(function ($query) use ($groupId): void {
                    if ($groupId === null) {
                        $query->whereNotNull('user_group_id');
                    } else {
                        $query->whereNull('user_group_id')->orWhere('user_group_id', '!=', $groupId);
                    }
                })
                ->exists();

            if ($linkedSubscriptions) {
                throw ValidationException::withMessages([
                    'user_group_id' => 'This site has subscriptions owned by another User Group. Reassign those customer accounts first.',
                ]);
            }

            DB::table('sites')->where('tenant_id', $tenantId)->where('id', $siteId)->update(['user_group_id' => $groupId]);
            DB::table('routers')->whereIn('id', $routerIds)->update(['user_group_id' => $groupId]);
            DB::table('access_points')->whereIn('id', $accessPointIds)->update(['user_group_id' => $groupId]);

            foreach (['router_monitoring_states', 'netflow_flows', 'network_alerts', 'network_bandwidth_samples'] as $table) {
                DB::table($table)->whereIn('router_id', $routerIds)->update(['user_group_id' => $groupId]);
            }

            $poolIds = DB::table('ip_pool_router')->whereIn('router_id', $routerIds)->pluck('ip_pool_id');
            DB::table('ip_pools')->whereIn('id', $poolIds)->update(['user_group_id' => $groupId]);
            DB::table('ip_addresses')->whereIn('ip_pool_id', $poolIds)->update(['user_group_id' => $groupId]);
        });
    }

    /** @return array<string, list<array{string, string}>> */
    private function parentMap(): array
    {
        return [
            'customers' => [['organization_id', 'organizations']],
            'subscriptions' => [['organization_id', 'organizations'], ['customer_id', 'customers']],
            'subscription_items' => [['subscription_id', 'subscriptions']],
            'subscription_ip_routes' => [['subscription_id', 'subscriptions']],
            'subscription_bandwidth_states' => [['subscription_id', 'subscriptions']],
            'invoices' => [['subscription_id', 'subscriptions'], ['customer_id', 'customers']],
            'invoice_items' => [['invoice_id', 'invoices']],
            'payments' => [['invoice_id', 'invoices'], ['customer_id', 'customers']],
            'customer_credits' => [['customer_id', 'customers']],
            'customer_notes' => [['customer_id', 'customers']],
            'tickets' => [['subscription_id', 'subscriptions'], ['customer_id', 'customers']],
            'ticket_attachments' => [['ticket_id', 'tickets']],
            'ticket_events' => [['ticket_id', 'tickets']],
            'ticket_messages' => [['ticket_id', 'tickets']],
            'work_orders' => [['subscription_id', 'subscriptions'], ['customer_id', 'customers']],
            'work_order_appointments' => [['work_order_id', 'work_orders']],
            'work_order_attachments' => [['work_order_id', 'work_orders']],
            'work_order_events' => [['work_order_id', 'work_orders']],
            'work_order_materials' => [['work_order_id', 'work_orders']],
            'work_order_notes' => [['work_order_id', 'work_orders']],
            'work_order_tasks' => [['work_order_id', 'work_orders']],
            'routers' => [['site_id', 'sites']],
            'access_points' => [['site_id', 'sites'], ['router_id', 'routers']],
            'ip_addresses' => [['ip_pool_id', 'ip_pools'], ['customer_id', 'customers']],
            'router_monitoring_states' => [['router_id', 'routers']],
            'netflow_flows' => [['router_id', 'routers']],
            'network_alerts' => [['router_id', 'routers']],
            'network_bandwidth_samples' => [['router_id', 'routers']],
            'network_usage_records' => [['subscription_id', 'subscriptions'], ['customer_id', 'customers'], ['router_id', 'routers']],
            'import_export_run_rows' => [['import_export_run_id', 'import_export_runs']],
        ];
    }
}
