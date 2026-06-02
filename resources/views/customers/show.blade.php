@extends('layouts.admin')

@section('title', 'Customer Profile')

@php
    $activeSubscription = $customer->subscriptions
        ->sortByDesc(fn ($subscription) => optional($subscription->activation_date ?? $subscription->created_at)->timestamp ?? 0)
        ->first();

    $customerData = [
        'id' => $customer->id,
        'name' => $customer->full_name,
        'customer_code' => $customer->customer_code,
        'customer_type' => $customer->customer_type,
        'organization' => $customer->organization?->name ?? 'Unassigned',
        'national_id' => $customer->national_id,
        'email' => $customer->email,
        'phone' => $customer->phone,
        'mobile' => $customer->mobile,
        'whatsapp' => $customer->whatsapp,
        'address' => trim(collect([$customer->address_line1, $customer->address_line2])->filter()->join(', ')),
        'city' => $customer->city,
        'state' => $customer->state,
        'country' => $customer->country,
        'status' => $customer->status,
        'balance' => (float) $customer->balance,
        'credit_limit' => (float) $customer->credit_limit,
        'billing_type' => $customer->billing_type,
        'tax_exempt' => (bool) $customer->tax_exempt,
        'plan' => $activeSubscription?->plan?->name ?? 'No active subscription',
        'site' => $activeSubscription?->site ?? 'N/A',
        'router' => $activeSubscription?->router?->name ?? 'N/A',
        'ip_address' => $activeSubscription?->ip_address ?? 'N/A',
        'billing_cycle' => $activeSubscription?->billing_cycle ?? 'monthly',
        'activated_at' => optional($activeSubscription?->activation_date ?? $activeSubscription?->created_at)->format('M d, Y'),
        'subscriptions_count' => $customer->subscriptions->count(),
        'open_balance' => (float) $customer->invoices->sum('balance_due'),
        'create_ticket_url' => route('support.tickets.create', ['customer_id' => $customer->id]),
    ];

    $subscriptions = $customer->subscriptions
        ->sortByDesc(fn ($subscription) => optional($subscription->created_at)->timestamp ?? 0)
        ->values()
        ->map(fn ($subscription) => [
            'id' => $subscription->id,
            'subscription_code' => $subscription->subscription_code,
            'name' => $subscription->name,
            'service_type' => $subscription->service_type,
            'plan' => $subscription->plan?->name ?? 'N/A',
            'router' => $subscription->router?->name ?? 'N/A',
            'ip_address' => $subscription->ip_address ?? 'N/A',
            'pppoe_username' => $subscription->pppoe_username,
            'pppoe_password' => $subscription->pppoe_password,
            'connection_status' => $subscription->connection_status,
            'status' => $subscription->status,
            'activated_at' => optional($subscription->activation_date ?? $subscription->created_at)->format('M d, Y'),
            'billing_cycle' => $subscription->billing_cycle,
            'total_price' => (float) $subscription->total_price,
            'url' => route('subscriptions.show', $subscription),
            'edit_url' => route('subscriptions.edit', $subscription),
        ])->all();

    $invoices = $customer->invoices
        ->sortByDesc(fn ($invoice) => optional($invoice->issue_date ?? $invoice->created_at)->timestamp ?? 0)
        ->values()
        ->map(fn ($invoice) => [
            'id' => $invoice->id,
            'number' => $invoice->invoice_number,
            'amount' => (float) $invoice->total,
            'paid_amount' => (float) $invoice->paid_amount,
            'balance_due' => (float) $invoice->balance_due,
            'due_date' => optional($invoice->due_date)->format('Y-m-d'),
            'issue_date' => optional($invoice->issue_date)->format('Y-m-d'),
            'status' => $invoice->status,
            'subscription' => $invoice->subscription?->subscription_code ?? 'N/A',
            'url' => route('billing.invoices.show', $invoice),
        ])->all();

    $payments = $customer->payments
        ->sortByDesc(fn ($payment) => optional($payment->paid_at ?? $payment->created_at)->timestamp ?? 0)
        ->values()
        ->map(fn ($payment) => [
            'id' => $payment->id,
            'reference' => $payment->payment_reference,
            'invoice_id' => $payment->invoice_id,
            'invoice_number' => $payment->invoice?->invoice_number ?? 'N/A',
            'amount' => (float) $payment->amount,
            'method' => $payment->payment_method ?? 'cash',
            'paid_at' => optional($payment->paid_at ?? $payment->created_at)->format('Y-m-d'),
            'status' => $payment->status,
            'url' => route('billing.payments.show', $payment),
            'invoice_url' => $payment->invoice ? route('billing.invoices.show', $payment->invoice) : '#',
        ])->all();

    $tickets = $customer->tickets
        ->sortByDesc(fn ($ticket) => optional($ticket->last_activity_at ?? $ticket->created_at)->timestamp ?? 0)
        ->values()
        ->map(fn ($ticket) => [
            'id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'subject' => $ticket->subject,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'team' => $ticket->team?->name ?? 'Unassigned',
            'assignee' => $ticket->assignedUser?->name ?? 'Unassigned',
            'subscription' => $ticket->subscription?->subscription_code ?? 'N/A',
            'last_activity_at' => optional($ticket->last_activity_at ?? $ticket->created_at)->format('M d, Y H:i'),
            'url' => route('support.tickets.show', $ticket),
        ])->all();

    $notes = $customer->notes
        ->sortByDesc(fn ($note) => optional($note->created_at)->timestamp ?? 0)
        ->values();

    $activity = $activityLog->values()->all();
@endphp

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="space-y-6" x-data="customerShow(@js($customerData), @js($subscriptions), @js($invoices), @js($payments), @js($tickets), @js($activity), @js(route('billing.payments.store')))" x-cloak>
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-2xl p-6 text-white">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-20 h-20 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-2xl font-bold" x-text="customer?.name?.charAt(0)?.toUpperCase() || 'U'"></div>
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold" x-text="customer?.name || 'Customer'"></h1>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                              :class="getStatusBadgeClass(customer?.status)"
                              x-text="formatStatus(customer?.status)"></span>
                    </div>
                    <p class="text-blue-100 text-sm mt-1" x-text="customer?.customer_code || ''"></p>
                    <p class="text-blue-100 text-sm" x-text="(customer?.email || 'No email') + ' • ' + (customer?.mobile || 'No mobile')"></p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a :href="`/customers/${customer?.id}/edit`" class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm font-medium transition-colors">
                    Edit
                </a>
                <a :href="`/subscriptions/create?customer_id=${customer?.id}`" class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm font-medium transition-colors">
                    Add Subscription
                </a>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 pt-6 border-t border-white/20">
            <div>
                <p class="text-blue-100 text-xs uppercase tracking-wider">Current Plan</p>
                <p class="text-lg font-semibold mt-1" x-text="customer?.plan || 'N/A'"></p>
            </div>
            <div>
                <p class="text-blue-100 text-xs uppercase tracking-wider">Open Balance</p>
                <p class="text-lg font-semibold mt-1" :class="customer?.open_balance <= 0 ? 'text-green-300' : 'text-red-300'" x-text="formatBalance(customer?.open_balance || 0)"></p>
            </div>
            <div>
                <p class="text-blue-100 text-xs uppercase tracking-wider">IP Address</p>
                <p class="text-lg font-semibold mt-1 font-mono" x-text="customer?.ip_address || 'N/A'"></p>
            </div>
            <div>
                <p class="text-blue-100 text-xs uppercase tracking-wider">Billing Cycle</p>
                <p class="text-lg font-semibold mt-1 capitalize" x-text="customer?.billing_cycle || 'N/A'"></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Subscription Access</h2>
                <p class="text-sm text-gray-500">IP address and PPP credentials for this customer's services.</p>
            </div>
            <a :href="`/subscriptions/create?customer_id=${customer?.id}`" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                Add Subscription
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Subscription</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Plan</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Router</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">IP Address</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">PPP Username</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">PPP Password</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <template x-if="subscriptions.length === 0">
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">No subscriptions found.</td>
                        </tr>
                    </template>
                    <template x-for="subscription in subscriptions" :key="subscription.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <a :href="subscription.url" class="text-sm font-semibold text-blue-600 hover:text-blue-700" x-text="subscription.subscription_code"></a>
                                <p class="text-xs text-gray-500" x-text="subscription.name || subscription.service_type || ''"></p>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700" x-text="subscription.plan"></td>
                            <td class="px-6 py-4 text-sm text-gray-700" x-text="subscription.router"></td>
                            <td class="px-6 py-4 text-sm font-mono text-gray-900" x-text="subscription.ip_address || '—'"></td>
                            <td class="px-6 py-4 text-sm font-mono text-gray-900" x-text="subscription.pppoe_username || '—'"></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-mono text-gray-900" x-text="subscription.pppoe_password ? (revealedCredentials[subscription.id] ? subscription.pppoe_password : '••••••••') : '—'"></span>
                                    <button type="button" x-show="subscription.pppoe_password" @click="toggleCredential(subscription.id)" class="rounded-md border border-gray-300 px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50" x-text="revealedCredentials[subscription.id] ? 'Hide' : 'Show'"></button>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="space-y-1">
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium"
                                          :class="getStatusBadgeClass(subscription.status)"
                                          x-text="formatStatus(subscription.status)"></span>
                                    <p class="text-xs text-gray-500" x-show="subscription.connection_status" x-text="'Connection: ' + formatStatus(subscription.connection_status)"></p>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <nav class="border-b border-gray-200">
            <div class="flex space-x-8 overflow-x-auto">
                <template x-for="(label, key) in tabs" :key="key">
                    <button @click="activeTab = key"
                            :class="activeTab === key ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                            x-text="label"></button>
                </template>
            </div>
        </nav>

        <div class="mt-6">
            <div x-show="activeTab === 'overview'">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between"><span class="text-gray-500">Customer Code</span><span class="font-medium text-gray-900" x-text="customer?.customer_code || ''"></span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Type</span><span class="font-medium text-gray-900 capitalize" x-text="customer?.customer_type || ''"></span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Organization</span><span class="font-medium text-gray-900" x-text="customer?.organization || 'Unassigned'"></span></div>
                            <div class="flex justify-between"><span class="text-gray-500">National ID</span><span class="font-medium text-gray-900" x-text="customer?.national_id || '—'"></span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Status</span><span class="font-medium text-gray-900 capitalize" x-text="customer?.status || ''"></span></div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Contact Information</h3>
                        <div class="space-y-3 text-sm">
                            <div><span class="text-gray-500 block">Email</span><span class="font-medium text-gray-900" x-text="customer?.email || '—'"></span></div>
                            <div><span class="text-gray-500 block">Phone</span><span class="font-medium text-gray-900" x-text="customer?.phone || '—'"></span></div>
                            <div><span class="text-gray-500 block">Mobile</span><span class="font-medium text-gray-900" x-text="customer?.mobile || '—'"></span></div>
                            <div><span class="text-gray-500 block">WhatsApp</span><span class="font-medium text-gray-900" x-text="customer?.whatsapp || '—'"></span></div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900 mb-1">Notifications</h3>
                        <p class="text-sm text-gray-500 mb-4">{{ $unreadNotificationsCount }} unread notification(s)</p>

                        <form method="POST" action="{{ route('customers.notifications.update', $customer) }}" class="space-y-3">
                            @csrf
                            @method('PATCH')

                            @foreach([
                                'notifications_enabled' => 'Enable notifications',
                                'in_app_enabled' => 'In-app',
                                'email_enabled' => 'Email preference',
                                'sms_enabled' => 'SMS preference',
                            ] as $field => $label)
                                <label class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 px-3 py-2">
                                    <span class="text-sm font-medium text-gray-700">{{ $label }}</span>
                                    <span>
                                        <input type="hidden" name="{{ $field }}" value="0">
                                        <input type="checkbox" name="{{ $field }}" value="1" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" @checked(old($field, $notificationPreference[$field]))>
                                    </span>
                                </label>
                            @endforeach

                            <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Save Notification Preferences</button>
                        </form>
                    </div>

                    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Address Information</h3>
                        <div class="space-y-3 text-sm">
                            <div><span class="text-gray-500 block">Address</span><span class="font-medium text-gray-900" x-text="customer?.address || '—'"></span></div>
                            <div class="grid grid-cols-2 gap-2">
                                <div><span class="text-gray-500 block">City</span><span class="font-medium text-gray-900" x-text="customer?.city || '—'"></span></div>
                                <div><span class="text-gray-500 block">State</span><span class="font-medium text-gray-900" x-text="customer?.state || '—'"></span></div>
                            </div>
                            <div><span class="text-gray-500 block">Country</span><span class="font-medium text-gray-900" x-text="customer?.country || '—'"></span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div x-show="activeTab === 'subscriptions'" style="display: none;">
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Subscription</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Plan</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Router</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">IP</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Activated</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-if="subscriptions.length === 0">
                                    <tr><td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">No subscriptions found.</td></tr>
                                </template>
                                <template x-for="subscription in subscriptions" :key="subscription.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <a :href="subscription.url" class="text-sm font-medium text-blue-600 hover:text-blue-700" x-text="subscription.subscription_code"></a>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-700" x-text="subscription.plan"></td>
                                        <td class="px-6 py-4 text-sm text-gray-700" x-text="subscription.router"></td>
                                        <td class="px-6 py-4 text-sm font-mono text-gray-700" x-text="subscription.ip_address"></td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                                                  :class="getStatusBadgeClass(subscription.status)"
                                                  x-text="formatStatus(subscription.status)"></span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500" x-text="subscription.activated_at || '—'"></td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center justify-end gap-2">
                                                <a :href="subscription.url" class="text-sm font-medium text-blue-600 hover:text-blue-700">View</a>
                                                <a :href="subscription.edit_url" class="text-sm font-medium text-gray-600 hover:text-gray-900">Edit</a>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div x-show="activeTab === 'invoices'" style="display: none;">
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Invoice</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Balance</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Due Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-if="invoices.length === 0">
                                    <tr><td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">No invoices found.</td></tr>
                                </template>
                                <template x-for="invoice in invoices" :key="invoice.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <a :href="invoice.url" class="text-sm font-medium text-blue-600 hover:text-blue-700" x-text="invoice.number"></a>
                                            <p class="text-xs text-gray-500" x-text="invoice.subscription"></p>
                                        </td>
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-900" x-text="formatBalance(invoice.amount)"></td>
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-900" x-text="formatBalance(invoice.balance_due)"></td>
                                        <td class="px-6 py-4 text-sm text-gray-500" x-text="invoice.due_date || '—'"></td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                                                  :class="getStatusBadgeClass(invoice.status)"
                                                  x-text="formatStatus(invoice.status)"></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center justify-end gap-2">
                                                <a :href="invoice.url" class="text-sm font-medium text-blue-600 hover:text-blue-700">View</a>
                                                <button type="button" x-show="Number(invoice.balance_due) > 0" @click="openPayment(invoice)" class="text-sm font-medium text-emerald-700 hover:text-emerald-800">Record Payment</button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div x-show="activeTab === 'payments'" style="display: none;">
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Payments</h3>
                            <p class="text-sm text-gray-500">Real payments recorded against this customer's invoices.</p>
                        </div>
                        <button type="button" x-show="outstandingInvoices.length > 0" @click="openPayment(outstandingInvoices[0])" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                            Record Payment
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Reference</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Invoice</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Method</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Paid At</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-if="payments.length === 0">
                                    <tr><td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">No payments recorded.</td></tr>
                                </template>
                                <template x-for="payment in payments" :key="payment.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <a :href="payment.url" class="text-sm font-medium text-blue-600 hover:text-blue-700" x-text="payment.reference"></a>
                                        </td>
                                        <td class="px-6 py-4">
                                            <a :href="payment.invoice_url" class="text-sm text-gray-700 hover:text-blue-600" x-text="payment.invoice_number"></a>
                                        </td>
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-900" x-text="formatBalance(payment.amount)"></td>
                                        <td class="px-6 py-4 text-sm text-gray-700 capitalize" x-text="String(payment.method || 'cash').replace('_', ' ')"></td>
                                        <td class="px-6 py-4 text-sm text-gray-500" x-text="payment.paid_at || '—'"></td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                                                  :class="getStatusBadgeClass(payment.status)"
                                                  x-text="formatStatus(payment.status)"></span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <a :href="payment.url" class="text-sm font-medium text-blue-600 hover:text-blue-700">View</a>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div x-show="activeTab === 'tickets'" style="display: none;">
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Support Tickets</h3>
                            <p class="text-sm text-gray-500">Open and historical tickets linked to this customer.</p>
                        </div>
                        <a :href="customer?.create_ticket_url" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                            Create Ticket
                        </a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Ticket</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Priority</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Team</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Assignee</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Subscription</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Last Activity</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-if="tickets.length === 0">
                                    <tr><td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">No support tickets found.</td></tr>
                                </template>
                                <template x-for="ticket in tickets" :key="ticket.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <a :href="ticket.url" class="text-sm font-semibold text-blue-600 hover:text-blue-700" x-text="ticket.ticket_number"></a>
                                            <p class="text-sm text-gray-700" x-text="ticket.subject"></p>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                                                  :class="getStatusBadgeClass(ticket.priority)"
                                                  x-text="formatStatus(ticket.priority)"></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                                                  :class="getStatusBadgeClass(ticket.status)"
                                                  x-text="formatStatus(ticket.status)"></span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-700" x-text="ticket.team"></td>
                                        <td class="px-6 py-4 text-sm text-gray-700" x-text="ticket.assignee"></td>
                                        <td class="px-6 py-4 text-sm font-mono text-gray-700" x-text="ticket.subscription"></td>
                                        <td class="px-6 py-4 text-sm text-gray-500" x-text="ticket.last_activity_at || '—'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div x-show="activeTab === 'notes'" style="display: none;">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm lg:col-span-1">
                        <h3 class="text-lg font-semibold text-gray-900">Add Customer Note</h3>
                        <p class="mt-1 text-sm text-gray-500">Internal notes stay on the customer profile.</p>

                        <form method="POST" action="{{ route('customers.notes.store', $customer) }}" class="mt-4 space-y-4">
                            @csrf
                            <div>
                                <label for="body" class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                                <textarea id="body" name="body" rows="6" required class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Add operational context, follow-up details, or customer preferences...">{{ old('body') }}</textarea>
                                @error('body')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Save Note</button>
                        </form>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm lg:col-span-2">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-lg font-semibold text-gray-900">Customer Notes</h3>
                            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600">{{ $notes->count() }} note(s)</span>
                        </div>
                        <div class="mt-4 space-y-4">
                            @forelse($notes as $note)
                                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                        <p class="text-sm font-semibold text-gray-900">{{ $note->author?->name ?? 'Unknown user' }}</p>
                                        <p class="text-xs text-gray-500">{{ $note->created_at?->format('M d, Y H:i') }}</p>
                                    </div>
                                    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-gray-700">{{ $note->body }}</p>
                                </div>
                            @empty
                                <p class="rounded-xl border border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-500">No customer notes yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div x-show="activeTab === 'activity'" style="display: none;">
                <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Activity Timeline</h3>
                    <div class="space-y-6">
                        <template x-if="activityLog.length === 0">
                            <p class="text-sm text-gray-500">No activity recorded yet.</p>
                        </template>
                        <template x-for="(activity, index) in activityLog" :key="index">
                            <div class="flex gap-4">
                                <div class="flex flex-col items-center">
                                    <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center"></div>
                                    <div class="w-0.5 flex-1 bg-gray-200 mt-2" x-show="index < activityLog.length - 1"></div>
                                </div>
                                <div class="flex-1 pb-6">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-medium text-gray-900" x-text="activity.action"></p>
                                        <p class="text-xs text-gray-500" x-text="activity.time"></p>
                                    </div>
                                    <p class="text-sm text-gray-500 mt-1" x-text="activity.description"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-show="paymentModal.open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4" style="display: none;">
        <div class="w-full max-w-md rounded-2xl bg-white shadow-xl" @click.outside="closePayment()">
            <div class="border-b border-gray-200 px-6 py-4">
                <h3 class="text-lg font-semibold text-gray-900">Record Payment</h3>
                <p class="text-sm text-gray-500" x-text="paymentModal.invoice ? paymentModal.invoice.number + ' balance ' + formatBalance(paymentModal.invoice.balance_due) : ''"></p>
            </div>
            <form class="space-y-4 p-6" @submit.prevent="submitPayment()">
                <input type="hidden" x-model="paymentForm.invoice_id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Invoice</label>
                    <select x-model="paymentForm.invoice_id" @change="selectPaymentInvoice()" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border bg-white" required>
                        <template x-for="invoice in outstandingInvoices" :key="invoice.id">
                            <option :value="invoice.id" x-text="invoice.number + ' - ' + formatBalance(invoice.balance_due)"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                    <input type="number" min="0.01" step="0.01" x-model="paymentForm.amount" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                    <select x-model="paymentForm.payment_method" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border bg-white">
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="card">Card</option>
                        <option value="online">Online</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Paid At</label>
                    <input type="date" x-model="paymentForm.paid_at" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
                </div>
                <p x-show="paymentModal.error" class="text-sm text-red-600" x-text="paymentModal.error"></p>
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="closePayment()" class="px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">Cancel</button>
                    <button type="submit" :disabled="paymentModal.submitting" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-60" x-text="paymentModal.submitting ? 'Saving...' : 'Save Payment'"></button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function customerShow(customer, subscriptions, invoices, payments, tickets, activityLog, paymentStoreUrl) {
    return {
        customer,
        subscriptions,
        invoices,
        payments,
        tickets,
        activityLog,
        paymentStoreUrl,
        revealedCredentials: {},
        activeTab: 'overview',
        paymentModal: {
            open: false,
            invoice: null,
            submitting: false,
            error: '',
        },
        paymentForm: {
            invoice_id: '',
            amount: '',
            payment_method: 'cash',
            paid_at: new Date().toISOString().slice(0, 10),
        },
        tabs: {
            overview: 'Overview',
            subscriptions: 'Subscriptions',
            invoices: 'Invoices',
            payments: 'Payments',
            tickets: 'Tickets',
            notes: 'Notes',
            activity: 'Activity Log',
        },

        get outstandingInvoices() {
            return this.invoices.filter((invoice) => Number(invoice.balance_due || 0) > 0);
        },

        formatBalance(amount) {
            return '$' + Number(amount || 0).toFixed(2);
        },

        formatStatus(status) {
            if (!status) {
                return 'Unknown';
            }

            return status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ');
        },

        getStatusBadgeClass(status) {
            const classes = {
                active: 'bg-green-100 text-green-800 border-green-200',
                pending: 'bg-yellow-100 text-yellow-800 border-yellow-200',
                pending_customer: 'bg-amber-100 text-amber-800 border-amber-200',
                pending_staff: 'bg-blue-100 text-blue-800 border-blue-200',
                open: 'bg-blue-100 text-blue-800 border-blue-200',
                new: 'bg-purple-100 text-purple-800 border-purple-200',
                resolved: 'bg-green-100 text-green-800 border-green-200',
                closed: 'bg-gray-100 text-gray-800 border-gray-200',
                suspended: 'bg-red-100 text-red-800 border-red-200',
                cancelled: 'bg-gray-100 text-gray-800 border-gray-200',
                inactive: 'bg-gray-100 text-gray-800 border-gray-200',
                paid: 'bg-green-100 text-green-800 border-green-200',
                issued: 'bg-blue-100 text-blue-800 border-blue-200',
                overdue: 'bg-red-100 text-red-800 border-red-200',
                partially_paid: 'bg-amber-100 text-amber-800 border-amber-200',
                low: 'bg-gray-100 text-gray-800 border-gray-200',
                normal: 'bg-blue-100 text-blue-800 border-blue-200',
                high: 'bg-orange-100 text-orange-800 border-orange-200',
                urgent: 'bg-red-100 text-red-800 border-red-200',
                online: 'bg-green-100 text-green-800 border-green-200',
                offline: 'bg-gray-100 text-gray-800 border-gray-200',
            };

            return classes[status] || classes.inactive;
        },

        toggleCredential(subscriptionId) {
            this.revealedCredentials[subscriptionId] = ! this.revealedCredentials[subscriptionId];
        },

        openPayment(invoice) {
            this.paymentModal.open = true;
            this.paymentModal.invoice = invoice;
            this.paymentModal.error = '';
            this.paymentForm.invoice_id = invoice?.id || '';
            this.paymentForm.amount = Number(invoice?.balance_due || 0).toFixed(2);
            this.paymentForm.payment_method = 'cash';
            this.paymentForm.paid_at = new Date().toISOString().slice(0, 10);
        },

        closePayment() {
            this.paymentModal.open = false;
            this.paymentModal.invoice = null;
            this.paymentModal.error = '';
        },

        selectPaymentInvoice() {
            const invoice = this.invoices.find((item) => String(item.id) === String(this.paymentForm.invoice_id));
            this.paymentModal.invoice = invoice || null;
            this.paymentForm.amount = Number(invoice?.balance_due || 0).toFixed(2);
        },

        async submitPayment() {
            this.paymentModal.submitting = true;
            this.paymentModal.error = '';

            try {
                const response = await fetch(this.paymentStoreUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify({
                        invoice_id: this.paymentForm.invoice_id,
                        amount: this.paymentForm.amount,
                        payment_method: this.paymentForm.payment_method,
                        paid_at: this.paymentForm.paid_at,
                    }),
                });

                const data = await response.json();

                if (!response.ok) {
                    this.paymentModal.error = data.message || 'Payment could not be recorded.';
                    return;
                }

                window.location.reload();
            } catch (error) {
                this.paymentModal.error = 'Payment could not be recorded.';
            } finally {
                this.paymentModal.submitting = false;
            }
        },
    };
}
</script>
@endpush
@endsection
