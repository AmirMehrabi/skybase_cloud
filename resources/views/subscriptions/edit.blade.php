@extends('layouts.admin')

@section('title', 'Edit Subscription')

@php
    $organization = $subscription->customer?->organization;
    $organizationBilling = $organization?->billing_enabled;
@endphp

@section('content')
<div class="space-y-6 pb-24">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('subscriptions.show', $subscription) }}" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit Subscription</h1>
                <p class="text-sm text-gray-500 mt-1">{{ $subscription->subscription_code }}</p>
            </div>
        </div>
    </div>

    <x-form.validation-summary :errors="$errors" />

    @if($organizationBilling)
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
            Billing is managed by <span class="font-semibold">{{ $organization->name }}</span>. The default service, billing cycle, grace period, discount, and tax are enforced on save.
        </div>
    @endif

    <form action="{{ route('subscriptions.update', $subscription) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Customer & Service</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Customer</label>
                    <input type="text" value="{{ $subscription->customer?->full_name }} ({{ $subscription->customer?->customer_code }})" readonly class="block w-full rounded-lg border-gray-300 bg-gray-50 sm:text-sm py-2 px-3 border">
                </div>
                <div>
                    <label for="plan_id" class="block text-sm font-medium text-gray-700 mb-1">Service Plan</label>
                    <select name="plan_id" id="plan_id" @disabled($organizationBilling) class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border bg-white disabled:bg-gray-50">
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" @selected((string) old('plan_id', $organizationBilling ? $organization->default_plan_id : $subscription->plan_id) === (string) $plan->id)>
                                {{ $plan->name }} - ${{ number_format((float) $plan->price, 2) }}/{{ $plan->billing_cycle }}
                            </option>
                        @endforeach
                    </select>
                    @if($organizationBilling)
                        <input type="hidden" name="plan_id" value="{{ $organization->default_plan_id }}">
                    @endif
                    @error('plan_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="router_id" class="block text-sm font-medium text-gray-700 mb-1">Router / NAS</label>
                    <select name="router_id" id="router_id" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border bg-white">
                        <option value="">Select a router</option>
                        @foreach($routers as $router)
                            <option value="{{ $router->id }}" @selected((string) old('router_id', $subscription->router_id) === (string) $router->id)>{{ $router->name }} ({{ $router->vendor }} {{ $router->model }})</option>
                        @endforeach
                    </select>
                    @error('router_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="site" class="block text-sm font-medium text-gray-700 mb-1">Site</label>
                    <input type="text" name="site" id="site" value="{{ old('site', $subscription->site) }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
                    @error('site')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="ip_address" class="block text-sm font-medium text-gray-700 mb-1">IP Address</label>
                    <input type="text" name="ip_address" id="ip_address" value="{{ old('ip_address', $subscription->ip_address) }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
                    @error('ip_address')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="status" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border bg-white">
                        @foreach(['pending' => 'Pending', 'active' => 'Active', 'suspended' => 'Suspended', 'cancelled' => 'Cancelled'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $subscription->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Connection</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <label for="pppoe_username" class="block text-sm font-medium text-gray-700 mb-1">PPPoE Username</label>
                    <input type="text" name="pppoe_username" id="pppoe_username" value="{{ old('pppoe_username', $subscription->pppoe_username) }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
                    @error('pppoe_username')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="pppoe_password" class="block text-sm font-medium text-gray-700 mb-1">PPPoE Password</label>
                    <input type="password" name="pppoe_password" id="pppoe_password" value="{{ old('pppoe_password', $subscription->pppoe_password) }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
                    @error('pppoe_password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Billing & Schedule</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div>
                    <label for="billing_cycle" class="block text-sm font-medium text-gray-700 mb-1">Billing Cycle</label>
                    <select name="billing_cycle" id="billing_cycle" @disabled($organizationBilling) class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border bg-white disabled:bg-gray-50">
                        @foreach(['monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'yearly' => 'Yearly'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('billing_cycle', $organizationBilling ? $organization->default_billing_cycle : $subscription->billing_cycle) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @if($organizationBilling)
                        <input type="hidden" name="billing_cycle" value="{{ $organization->default_billing_cycle }}">
                    @endif
                    @error('billing_cycle')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="grace_period_days" class="block text-sm font-medium text-gray-700 mb-1">Grace Period</label>
                    <input type="number" min="0" max="365" name="grace_period_days" id="grace_period_days" value="{{ old('grace_period_days', $organizationBilling ? $organization->default_grace_period_days : $subscription->grace_period_days) }}" @disabled($organizationBilling) class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border disabled:bg-gray-50">
                    @if($organizationBilling)
                        <input type="hidden" name="grace_period_days" value="{{ $organization->default_grace_period_days }}">
                    @endif
                    @error('grace_period_days')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="next_billing_date" class="block text-sm font-medium text-gray-700 mb-1">Next Billing Date</label>
                    <input type="date" name="next_billing_date" id="next_billing_date" value="{{ old('next_billing_date', optional($subscription->next_billing_date)->format('Y-m-d')) }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
                    @error('next_billing_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="flex items-center justify-between rounded-xl border border-gray-200 p-4">
                    <div>
                        <label for="billing_enabled" class="text-sm font-medium text-gray-700">Billing Enabled</label>
                        <p class="text-xs text-gray-500 mt-1">Include in automated billing.</p>
                    </div>
                    <div>
                        <input type="hidden" name="billing_enabled" value="0">
                        <input type="checkbox" name="billing_enabled" id="billing_enabled" value="1" @checked(old('billing_enabled', $organizationBilling ? true : $subscription->billing_enabled)) @disabled($organizationBilling) class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 disabled:opacity-60">
                        @if($organizationBilling)
                            <input type="hidden" name="billing_enabled" value="1">
                        @endif
                    </div>
                </div>
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                    <input type="date" name="start_date" id="start_date" value="{{ old('start_date', optional($subscription->start_date)->format('Y-m-d')) }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
                </div>
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                    <input type="date" name="end_date" id="end_date" value="{{ old('end_date', optional($subscription->end_date)->format('Y-m-d')) }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
                </div>
                <div class="md:col-span-2">
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" id="notes" rows="3" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">{{ old('notes', $subscription->notes) }}</textarea>
                </div>
            </div>
        </div>

        <div class="fixed bottom-0 right-0 left-0 lg:left-64 bg-white border-t border-gray-200 shadow-lg p-4 z-40">
            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('subscriptions.show', $subscription) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">Cancel</a>
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">Save Changes</button>
            </div>
        </div>
    </form>
</div>
@endsection
