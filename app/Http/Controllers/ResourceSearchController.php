<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\IpPool;
use App\Models\Router;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResourceSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $query = trim((string) ($validated['q'] ?? ''));
        $tenantId = auth()->user()?->tenant_id;

        if ($query === '' || ! $tenantId) {
            return response()->json(['modules' => []]);
        }

        $modules = [
            [
                'key' => 'customers',
                'label' => 'Customers',
                'items' => $this->searchCustomers($query, $tenantId),
            ],
            [
                'key' => 'subscriptions',
                'label' => 'Subscriptions',
                'items' => $this->searchSubscriptions($query, $tenantId),
            ],
            [
                'key' => 'ipams',
                'label' => 'IPAM',
                'items' => $this->searchIpam($query, $tenantId),
            ],
            [
                'key' => 'routers',
                'label' => 'Routers',
                'items' => $this->searchRouters($query, $tenantId),
            ],
            [
                'key' => 'invoices',
                'label' => 'Invoices',
                'items' => $this->searchInvoices($query, $tenantId),
            ],
        ];

        $modules = array_values(array_filter($modules, fn (array $module): bool => count($module['items']) > 0));

        return response()->json(['modules' => $modules]);
    }

    private function searchCustomers(string $query, int $tenantId): array
    {
        return Customer::query()
            ->where('tenant_id', $tenantId)
            ->where(function ($builder) use ($query) {
                $builder
                    ->where('customer_code', 'like', "%{$query}%")
                    ->orWhere('name', 'like', "%{$query}%")
                    ->orWhere('first_name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%")
                    ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) like ?", ["%{$query}%"])
                    ->orWhere('company_name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%")
                    ->orWhere('mobile', 'like', "%{$query}%");
            })
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (Customer $customer): array => [
                'id' => $customer->id,
                'title' => $this->highlight($customer->full_name ?: ($customer->name ?? 'Customer'), $query),
                'meta' => array_values(array_filter([
                    $customer->customer_code,
                    $customer->email,
                    $customer->mobile ?: $customer->phone,
                ])),
                'status' => $customer->status,
                'url' => route('customers.show', $customer),
            ])
            ->all();
    }

    private function searchSubscriptions(string $query, int $tenantId): array
    {
        return Subscription::query()
            ->where('tenant_id', $tenantId)
            ->with('customer:id,first_name,last_name,name')
            ->where(function ($builder) use ($query) {
                $builder
                    ->where('subscription_code', 'like', "%{$query}%")
                    ->orWhere('pppoe_username', 'like', "%{$query}%")
                    ->orWhere('ip_address', 'like', "%{$query}%")
                    ->orWhere('site', 'like', "%{$query}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($query) {
                        $customerQuery
                            ->where('name', 'like', "%{$query}%")
                            ->orWhere('first_name', 'like', "%{$query}%")
                            ->orWhere('last_name', 'like', "%{$query}%")
                            ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) like ?", ["%{$query}%"]);
                    });
            })
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(function (Subscription $subscription) use ($query): array {
                $customerName = $subscription->customer?->full_name ?? $subscription->customer?->name;

                return [
                    'id' => $subscription->id,
                    'title' => $this->highlight($subscription->subscription_code, $query),
                    'meta' => array_values(array_filter([
                        $subscription->pppoe_username ? 'PPPoE: '.$subscription->pppoe_username : null,
                        $customerName,
                        $subscription->ip_address,
                    ])),
                    'status' => $subscription->status,
                    'url' => route('subscriptions.show', $subscription),
                ];
            })
            ->all();
    }

    private function searchIpam(string $query, int $tenantId): array
    {
        return IpPool::query()
            ->where('tenant_id', $tenantId)
            ->with('router:id,name')
            ->where(function ($builder) use ($query) {
                $builder
                    ->where('name', 'like', "%{$query}%")
                    ->orWhere('network_address', 'like', "%{$query}%")
                    ->orWhere('gateway', 'like', "%{$query}%")
                    ->orWhere('site', 'like', "%{$query}%")
                    ->orWhere('type', 'like', "%{$query}%");
            })
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (IpPool $pool): array => [
                'id' => $pool->id,
                'title' => $this->highlight($pool->name, $query),
                'meta' => array_values(array_filter([
                    "{$pool->network_address}/{$pool->cidr}",
                    $pool->site,
                    $pool->router?->name,
                ])),
                'status' => $pool->status,
                'url' => route('ipam.pools.show', $pool),
            ])
            ->all();
    }

    private function searchRouters(string $query, int $tenantId): array
    {
        return Router::query()
            ->where('tenant_id', $tenantId)
            ->where(function ($builder) use ($query) {
                $builder
                    ->where('name', 'like', "%{$query}%")
                    ->orWhere('ip_address', 'like', "%{$query}%")
                    ->orWhere('site', 'like', "%{$query}%")
                    ->orWhere('location', 'like', "%{$query}%")
                    ->orWhere('model', 'like', "%{$query}%")
                    ->orWhere('vendor', 'like', "%{$query}%");
            })
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (Router $router): array => [
                'id' => $router->id,
                'title' => $this->highlight($router->name, $query),
                'meta' => array_values(array_filter([
                    $router->ip_address,
                    $router->site,
                    trim(($router->vendor ?? '').' '.($router->model ?? '')),
                ])),
                'status' => $router->status,
                'url' => route('routers.show', $router),
            ])
            ->all();
    }

    private function searchInvoices(string $query, int $tenantId): array
    {
        return Invoice::query()
            ->where('tenant_id', $tenantId)
            ->with(['customer:id,first_name,last_name,name', 'subscription:id,subscription_code'])
            ->where(function ($builder) use ($query) {
                $builder
                    ->where('invoice_number', 'like', "%{$query}%")
                    ->orWhere('notes', 'like', "%{$query}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($query) {
                        $customerQuery
                            ->where('name', 'like', "%{$query}%")
                            ->orWhere('first_name', 'like', "%{$query}%")
                            ->orWhere('last_name', 'like', "%{$query}%")
                            ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) like ?", ["%{$query}%"]);
                    })
                    ->orWhereHas('subscription', function ($subscriptionQuery) use ($query) {
                        $subscriptionQuery->where('subscription_code', 'like', "%{$query}%");
                    });
            })
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(function (Invoice $invoice) use ($query): array {
                $customerName = $invoice->customer?->full_name ?? $invoice->customer?->name;

                return [
                    'id' => $invoice->id,
                    'title' => $this->highlight($invoice->invoice_number, $query),
                    'meta' => array_values(array_filter([
                        $customerName,
                        $invoice->subscription?->subscription_code,
                        '$'.number_format((float) $invoice->total, 2),
                    ])),
                    'status' => $invoice->status,
                    'url' => route('billing.invoices.show', $invoice),
                ];
            })
            ->all();
    }

    private function highlight(?string $value, string $query): string
    {
        $text = trim((string) $value);

        if ($text === '' || trim($query) === '') {
            return e($text);
        }

        $escaped = e($text);
        $pattern = '/('.preg_quote($query, '/').')/i';

        return (string) preg_replace($pattern, '<mark>$1</mark>', $escaped);
    }
}
