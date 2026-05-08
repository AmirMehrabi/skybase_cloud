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
    ];

    $services = $customer->subscriptions
        ->sortByDesc(fn ($subscription) => optional($subscription->created_at)->timestamp ?? 0)
        ->values()
        ->map(fn ($subscription) => [
            'id' => $subscription->id,
            'service_id' => $subscription->subscription_code,
            'plan' => $subscription->plan?->name ?? 'N/A',
            'router' => $subscription->router?->name ?? 'N/A',
            'ip_address' => $subscription->ip_address ?? 'N/A',
            'status' => $subscription->status,
            'activated_at' => optional($subscription->activation_date ?? $subscription->created_at)->format('M d, Y'),
        ])->all();

    $invoices = $customer->invoices
        ->sortByDesc(fn ($invoice) => optional($invoice->issue_date ?? $invoice->created_at)->timestamp ?? 0)
        ->values()
        ->map(fn ($invoice) => [
            'id' => $invoice->id,
            'number' => $invoice->invoice_number,
            'amount' => (float) $invoice->total,
            'due_date' => optional($invoice->due_date)->format('Y-m-d'),
            'status' => $invoice->status,
        ])->all();

    $activity = $activityLog->values()->all();
@endphp

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="space-y-6" x-data="customerShow(@js($customerData), @js($services), @js($invoices), @js($activity))" x-cloak>
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
                <p class="text-blue-100 text-xs uppercase tracking-wider">Balance</p>
                <p class="text-lg font-semibold mt-1" :class="customer?.balance < 0 ? 'text-green-300' : 'text-red-300'" x-text="formatBalance(customer?.balance || 0)"></p>
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

            <div x-show="activeTab === 'services'" style="display: none;">
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Service ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Plan</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Router</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">IP</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Activated</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-if="services.length === 0">
                                    <tr><td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">No subscriptions found.</td></tr>
                                </template>
                                <template x-for="service in services" :key="service.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900" x-text="service.service_id"></td>
                                        <td class="px-6 py-4 text-sm text-gray-700" x-text="service.plan"></td>
                                        <td class="px-6 py-4 text-sm text-gray-700" x-text="service.router"></td>
                                        <td class="px-6 py-4 text-sm font-mono text-gray-700" x-text="service.ip_address"></td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                                                  :class="getStatusBadgeClass(service.status)"
                                                  x-text="formatStatus(service.status)"></span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500" x-text="service.activated_at || '—'"></td>
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
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Due Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-if="invoices.length === 0">
                                    <tr><td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">No invoices found.</td></tr>
                                </template>
                                <template x-for="invoice in invoices" :key="invoice.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-sm font-medium text-blue-600" x-text="invoice.number"></td>
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-900" x-text="formatBalance(invoice.amount)"></td>
                                        <td class="px-6 py-4 text-sm text-gray-500" x-text="invoice.due_date || '—'"></td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                                                  :class="getStatusBadgeClass(invoice.status)"
                                                  x-text="formatStatus(invoice.status)"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
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
</div>

@push('scripts')
<script>
function customerShow(customer, services, invoices, activityLog) {
    return {
        customer,
        services,
        invoices,
        activityLog,
        activeTab: 'overview',
        tabs: {
            overview: 'Overview',
            services: 'Services',
            invoices: 'Invoices',
            activity: 'Activity Log',
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
                suspended: 'bg-red-100 text-red-800 border-red-200',
                cancelled: 'bg-gray-100 text-gray-800 border-gray-200',
                inactive: 'bg-gray-100 text-gray-800 border-gray-200',
                paid: 'bg-green-100 text-green-800 border-green-200',
                issued: 'bg-blue-100 text-blue-800 border-blue-200',
                overdue: 'bg-red-100 text-red-800 border-red-200',
                partially_paid: 'bg-amber-100 text-amber-800 border-amber-200',
            };

            return classes[status] || classes.inactive;
        },
    };
}
</script>
@endpush
@endsection
