<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Site;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class TenantOperationalOverview
{
    /** @return array<string, mixed> */
    public function forTenant(Tenant $tenant, bool $detailed = false): array
    {
        abort_unless(app(TenantContext::class)->id() === $tenant->id, 403);
        $customers = $this->statuses(Customer::query()->where('tenant_id', $tenant->id));
        $subscriptions = $this->statuses(Subscription::query()->where('tenant_id', $tenant->id));
        $connections = $this->statuses(Subscription::query()->where('tenant_id', $tenant->id), 'connection_status');
        $routers = $this->statuses(Router::query()->where('tenant_id', $tenant->id));
        $staff = $this->statuses(User::query()->where('tenant_id', $tenant->id));
        $tickets = $this->statuses(Ticket::query()->where('tenant_id', $tenant->id));
        $outstanding = Invoice::query()->where('tenant_id', $tenant->id)->outstanding();
        $overview = [
            'customers' => array_sum($customers), 'customer_statuses' => $customers,
            'subscriptions' => array_sum($subscriptions), 'subscription_statuses' => $subscriptions,
            'active_subscriptions' => $subscriptions['active'] ?? 0,
            'online_subscriptions' => $connections['online'] ?? 0, 'connection_statuses' => $connections,
            'routers' => array_sum($routers), 'router_statuses' => $routers,
            'online_routers' => $routers['online'] ?? 0,
            'router_checked_at' => Router::query()->where('tenant_id', $tenant->id)->max('last_status_checked_at'),
            'staff' => array_sum($staff), 'staff_statuses' => $staff,
            'last_login' => User::query()->where('tenant_id', $tenant->id)->max('last_login_at'),
            'open_tickets' => array_sum(array_diff_key($tickets, array_flip([Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED]))),
            'ticket_statuses' => $tickets,
            'overdue_invoices' => Invoice::query()->where('tenant_id', $tenant->id)->overdue()->count(),
            'outstanding_invoices' => (clone $outstanding)->count(),
            'outstanding_balance' => (clone $outstanding)->sum('balance_due'),
            'currency' => $tenant->currency ?? 'USD', 'collected_at' => now(),
        ];
        if ($detailed) {
            $overview += [
                'sites' => Site::query()->where('tenant_id', $tenant->id)->count(),
                'organizations' => Organization::query()->where('tenant_id', $tenant->id)->count(),
                'plans' => Plan::query()->where('tenant_id', $tenant->id)->count(),
                'new_customers' => Customer::query()->where('tenant_id', $tenant->id)->where('created_at', '>=', now()->subDays(30))->count(),
                'new_subscriptions' => Subscription::query()->where('tenant_id', $tenant->id)->where('created_at', '>=', now()->subDays(30))->count(),
                'recent_subscriptions' => Subscription::query()->where('tenant_id', $tenant->id)->latest()->limit(5)->get(['id', 'name', 'status', 'service_type', 'created_at']),
                'recent_tickets' => Ticket::query()->where('tenant_id', $tenant->id)->latest()->limit(5)->get(['id', 'subject', 'status', 'priority', 'created_at']),
            ];
        }

        return $overview;
    }

    /** @return array<string, int> */
    private function statuses(Builder $query, string $column = 'status'): array
    {
        return $query->select($column)->selectRaw('COUNT(*) AS aggregate_count')->groupBy($column)->get()
            ->mapWithKeys(fn ($row): array => [filled($row->{$column}) ? (string) $row->{$column} : 'unknown' => (int) $row->aggregate_count])->all();
    }
}
