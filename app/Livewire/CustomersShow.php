<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Services\ActivityLogFormatter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class CustomersShow extends Component
{
    public int $customerId;

    public string $activeTab = 'overview';

    public array $tabs = [
        'overview' => 'Overview',
        'subscriptions' => 'Subscriptions',
        'invoices' => 'Invoices',
        'usage' => 'Usage',
        'tickets' => 'Tickets',
        'activity' => 'Activity Log',
    ];

    public array $customer = [];

    public function mount(int $id): void
    {
        $this->customerId = $id;
        $this->loadCustomer();
    }

    public function getInvoicesProperty(): array
    {
        return $this->customerModel()->invoices
            ->sortByDesc(fn ($invoice) => optional($invoice->issue_date ?? $invoice->created_at)->timestamp ?? 0)
            ->map(fn ($invoice): array => [
                'id' => $invoice->id,
                'number' => $invoice->invoice_number,
                'amount' => (float) $invoice->total,
                'due_date' => $invoice->due_date?->toDateString(),
                'status' => $invoice->status,
            ])
            ->values()
            ->all();
    }

    public function getSubscriptionsProperty(): array
    {
        return $this->customerModel()->subscriptions
            ->sortByDesc(fn ($subscription) => optional($subscription->created_at)->timestamp ?? 0)
            ->map(fn ($subscription): array => [
                'id' => $subscription->id,
                'code' => $subscription->subscription_code,
                'plan' => $subscription->plan?->name ?? 'N/A',
                'router' => $subscription->router?->name ?? 'N/A',
                'ip' => $subscription->ip_address ?? 'N/A',
                'status' => $subscription->status,
                'activated_at' => $subscription->activation_date?->toDateString(),
                'data_used' => 'N/A',
            ])
            ->values()
            ->all();
    }

    public function getActivityLogProperty(): array
    {
        $customer = $this->customerModel();

        return app(ActivityLogFormatter::class)
            ->forSubject($customer, $customer->tenant_id)
            ->values()
            ->all();
    }

    private function loadCustomer(): void
    {
        $customer = $this->customerModel();
        $activeSubscription = $customer->subscriptions->firstWhere('status', 'active') ?? $customer->subscriptions->first();

        $this->customer = [
            'id' => $customer->id,
            'customer_code' => $customer->customer_code,
            'type' => $customer->customer_type,
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
            'company_name' => $customer->company_name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'mobile' => $customer->mobile,
            'whatsapp' => $customer->whatsapp,
            'address' => trim(collect([$customer->address_line1, $customer->address_line2])->filter()->join(', ')),
            'city' => $customer->city,
            'state' => $customer->state,
            'postal_code' => $customer->postal_code,
            'country' => $customer->country,
            'national_id' => $customer->national_id,
            'plan' => $activeSubscription?->plan?->name ?? 'No active subscription',
            'site' => $activeSubscription?->site,
            'router' => $activeSubscription?->router?->name,
            'ip_address' => $activeSubscription?->ip_address,
            'mac_address' => $activeSubscription?->mac_address,
            'pppoe_username' => $activeSubscription?->pppoe_username,
            'status' => $customer->status,
            'balance' => (float) $customer->balance,
            'credit_limit' => (float) $customer->credit_limit,
            'tax_exempt' => (bool) $customer->tax_exempt,
            'discount' => 0,
            'billing_cycle' => $activeSubscription?->billing_cycle,
            'created_at' => $customer->created_at?->toDateString(),
            'activated_at' => $activeSubscription?->activation_date?->toDateString(),
            'last_updated' => $customer->updated_at?->format('Y-m-d H:i'),
        ];
    }

    private function customerModel(): Customer
    {
        return Customer::query()
            ->with(['subscriptions.plan', 'subscriptions.router', 'invoices'])
            ->findOrFail($this->customerId);
    }
}
