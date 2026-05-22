@extends('layouts.admin')

@section('title', 'Organization Details')

@section('content')
<div class="space-y-6">
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-2xl p-6 text-white">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold">{{ $organization->name }}</h1>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-white/15 border border-white/20">{{ ucfirst($organization->status) }}</span>
                </div>
                <p class="text-blue-100 text-sm mt-1">{{ $organization->code }}</p>
                <p class="text-blue-100 text-sm">{{ $organization->description ?: 'No description provided.' }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('organizations.edit', $organization) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm font-medium">Edit</a>
                <a href="{{ route('customers.create', ['organization_id' => $organization->id]) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm font-medium">Add Customer</a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Billing Defaults</h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Billing</span><span class="font-medium text-gray-900">{{ $organization->billing_enabled ? 'Enabled' : 'Disabled' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Default Service</span><span class="font-medium text-gray-900">{{ $organization->defaultPlan?->name ?? 'N/A' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Cycle</span><span class="font-medium text-gray-900 capitalize">{{ $organization->default_billing_cycle ?? 'N/A' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Grace</span><span class="font-medium text-gray-900">{{ $organization->default_grace_period_days ?? 'N/A' }} days</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Discount</span><span class="font-medium text-gray-900">{{ ucfirst($organization->default_discount_type) }} {{ $organization->default_discount_amount }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Tax</span><span class="font-medium text-gray-900">{{ $organization->default_tax_percentage }}%</span></div>
            </div>
        </div>

        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Assigned Customers</h3>
                <p class="text-sm text-gray-500 mt-1">{{ $organization->customers_count }} customers assigned</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Subscriptions</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($organization->customers as $customer)
                            <tr>
                                <td class="px-6 py-4">
                                    <a href="{{ route('customers.show', $customer) }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">{{ $customer->full_name }}</a>
                                    <div class="text-xs text-gray-500">{{ $customer->customer_code }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $customer->subscriptions->count() }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700 capitalize">{{ $customer->status }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-6 py-8 text-sm text-gray-500 text-center">No customers assigned.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
