@extends('layouts.admin')

@section('title', 'Edit Subscription')

@php
    $organization = $subscription->customer?->organization;
    $organizationBilling = $organization?->billing_enabled;
    $currentRouterId = old('router_id', $subscription->router_id);
    $currentIpPoolId = old('ip_pool_id', $subscription->ip_pool_id);
    $currentIpAddress = old('ip_address', $subscription->ip_address);
    $initialIpRoutes = collect(old('ip_routes', $subscription->ipRoutes->map(fn ($route) => [
        'ip_pool_id' => (string) $route->ip_pool_id,
        'ip_address' => $route->ip_address,
        'cidr' => (int) $route->cidr,
        'sync_status' => $route->routeros_sync_status,
        'sync_error' => $route->routeros_sync_error,
    ])->all()))
        ->filter(fn ($route) => is_array($route))
        ->values()
        ->map(fn ($route, $index) => [
            'key' => $index + 1,
            'ip_pool_id' => (string) ($route['ip_pool_id'] ?? ''),
            'ip_address' => (string) ($route['ip_address'] ?? ''),
            'cidr' => (int) ($route['cidr'] ?? 32),
            'sync_status' => $route['sync_status'] ?? null,
            'sync_error' => $route['sync_error'] ?? null,
        ])
        ->all();
@endphp

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="space-y-6 pb-24" x-data="subscriptionEditForm({
    routerId: @js((string) $currentRouterId),
    ipPoolId: @js((string) $currentIpPoolId),
    ipAddress: @js((string) $currentIpAddress),
    ipPools: @js($ipPools),
    ipRoutes: @js($initialIpRoutes),
})" x-cloak>
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
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $subscription->name) }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
                    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="service_type" class="block text-sm font-medium text-gray-700 mb-1">Subscription Type</label>
                    <select name="service_type" id="service_type" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border bg-white">
                        @foreach(['hotspot' => 'Hotspot', 'pppoe' => 'PPPoE', 'vpn' => 'VPN'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('service_type', $subscription->service_type ?? 'hotspot') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('service_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
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
                    <select name="router_id" id="router_id" x-model="form.router_id" @change="handleRouterChange()" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border bg-white">
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
                @if($subscription->isSystemManagedIp() && $subscription->ipPool)
                    <div class="lg:col-span-3 rounded-2xl border border-gray-200 bg-gray-50 p-4">
                        <input type="hidden" name="sync_ip_routes" value="1">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="space-y-2 flex-1">
                                <div class="flex items-center gap-2">
                                    <label for="ip_address" class="block text-sm font-medium text-gray-700">IP Pool Assignment</label>
                                    <span class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700" x-show="selectedIpPool">
                                        <span x-text="selectedIpPool?.name"></span>
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500">Primary IP is selected from the subscription pool. Routes below use their own IPAM row and can include a subnet.</p>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="ip_pool_id" class="block text-sm font-medium text-gray-700 mb-1">IP Pool</label>
                                        <select name="ip_pool_id" id="ip_pool_id" x-model="form.ip_pool_id" @change="handleIpPoolChange()" class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="">Select IP Pool</option>
                                            <template x-for="pool in availableIpPools()" :key="pool.id">
                                                <option :value="String(pool.id)" x-text="`${pool.name} (${pool.cidr_notation}) - ${pool.available_ips} available`"></option>
                                            </template>
                                        </select>
                                        @error('ip_pool_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                    </div>
                                    <div class="rounded-xl border border-blue-100 bg-white p-4 text-sm text-gray-600">
                                        <div class="flex items-center justify-between gap-4">
                                            <span>Current IP</span>
                                            <span class="font-mono text-gray-900" x-text="form.ip_address || 'Not set'"></span>
                                        </div>
                                        <div class="flex items-center justify-between gap-4 mt-2">
                                            <span>Available</span>
                                            <span class="font-medium text-gray-900" x-text="selectedIpPool ? selectedIpPool.available_ips : '0'"></span>
                                        </div>
                                        <div class="mt-3 min-h-5 text-xs" :class="ipMessage.type === 'error' ? 'text-red-600' : 'text-emerald-700'">
                                            <span x-text="ipMessage.text"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                    <div class="sm:max-w-md flex-1">
                                        <select name="ip_address" id="ip_address" x-model="form.ip_address" class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm font-mono shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="">Release primary IP</option>
                                            <template x-for="address in availablePrimaryAddresses()" :key="address.id">
                                                <option :value="address.ip_address" x-text="address.ip_address"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <button
                                        type="button"
                                        @click="suggestIpAddress()"
                                        :disabled="suggestingIp || ! canSuggestIp"
                                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-medium text-emerald-800 transition hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        <svg x-show="! suggestingIp" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                        <svg x-show="suggestingIp" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span x-text="suggestingIp ? 'Finding...' : 'Suggest free IP'"></span>
                                    </button>
                                </div>
                                @error('ip_address')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="mt-6 rounded-xl border border-blue-100 bg-white p-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900">IP Route</h4>
                                    <p class="mt-1 text-xs text-gray-500">RouterOS route dst-address values. Gateway is the primary IP above.</p>
                                </div>
                                <button type="button" @click="addIpRoute()" class="inline-flex items-center justify-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Add IP Route
                                </button>
                            </div>

                            <div x-show="ipRoutes.length === 0" class="mt-4 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-500">
                                No IP routes configured.
                            </div>

                            <div class="mt-4 space-y-3">
                                <template x-for="(route, index) in ipRoutes" :key="route.key">
                                    <div class="grid grid-cols-1 gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3 md:grid-cols-12 md:items-end">
                                        <div class="md:col-span-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">IPAM</label>
                                            <select :name="'ip_routes[' + index + '][ip_pool_id]'" x-model="route.ip_pool_id" @change="route.ip_address = ''" class="block w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                <option value="">Select IPAM</option>
                                                @foreach($ipPools ?? [] as $pool)
                                                    <option value="{{ $pool->id }}">{{ $pool->name }} ({{ $pool->cidr_notation }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="md:col-span-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">IP Address</label>
                                            <select :name="'ip_routes[' + index + '][ip_address]'" x-model="route.ip_address" class="block w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm font-mono shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                <option value="">Select IP address</option>
                                                <template x-for="address in availableRouteAddresses(route, index)" :key="address.id">
                                                    <option :value="address.ip_address" x-text="address.ip_address"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Subnet</label>
                                            <div class="flex rounded-lg shadow-sm">
                                                <span class="inline-flex items-center rounded-l-lg border border-r-0 border-gray-300 bg-gray-100 px-3 text-sm text-gray-500">/</span>
                                                <input type="number" min="1" max="32" :name="'ip_routes[' + index + '][cidr]'" x-model="route.cidr" class="block w-full rounded-r-lg border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                                            </div>
                                        </div>
                                        <div class="md:col-span-2">
                                            <button type="button" @click="removeIpRoute(index)" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-red-200 bg-white px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50">
                                                Remove
                                            </button>
                                        </div>
                                        <template x-if="route.sync_error">
                                            <div class="md:col-span-12 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700" x-text="route.sync_error"></div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="lg:col-span-3 rounded-2xl border border-gray-200 bg-gray-50 p-4">
                        <label for="ip_address" class="block text-sm font-medium text-gray-700 mb-1">Manual IP Address</label>
                        <input type="text" name="ip_address" id="ip_address" value="{{ $currentIpAddress }}" placeholder="192.168.1.100" class="block w-full max-w-md rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
                        @error('ip_address')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                @endif
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
                    <label for="pppoe_username" class="block text-sm font-medium text-gray-700 mb-1">PPP Username</label>
                    <input type="text" name="pppoe_username" id="pppoe_username" value="{{ old('pppoe_username', $subscription->pppoe_username) }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
                    @error('pppoe_username')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="pppoe_password" class="block text-sm font-medium text-gray-700 mb-1">PPP Password</label>
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

@push('scripts')
<script>
function subscriptionEditForm({ routerId, ipPoolId, ipAddress, ipPools, ipRoutes }) {
    return {
        form: {
            router_id: routerId || '',
            ip_pool_id: ipPoolId || '',
            ip_address: ipAddress || '',
        },
        ipPools: Array.isArray(ipPools) ? ipPools : [],
        ipRoutes: Array.isArray(ipRoutes) ? ipRoutes : [],
        nextIpRouteKey: (Array.isArray(ipRoutes) ? ipRoutes : []).length + 1,
        suggestingIp: false,
        ipMessage: {
            type: 'info',
            text: 'Choose a router and pool to suggest a free IP address.',
        },
        get selectedIpPool() {
            if (! this.form.ip_pool_id) {
                return null;
            }

            return this.ipPools.find(pool => String(pool.id) === String(this.form.ip_pool_id)) || null;
        },
        get canSuggestIp() {
            return this.availablePoolAddresses().length > 0;
        },
        availableIpPools() {
            if (! this.form.router_id) {
                return [];
            }

            return this.ipPools.filter(pool => pool.all_devices || String(pool.router_id ?? '') === String(this.form.router_id));
        },
        routeIpPool(route) {
            if (! route.ip_pool_id) {
                return null;
            }

            return this.ipPools.find(pool => String(pool.id) === String(route.ip_pool_id));
        },
        uniqueAddresses(addresses) {
            const seen = new Set();

            return addresses.filter(address => {
                if (! address || ! address.ip_address || seen.has(address.ip_address)) {
                    return false;
                }

                seen.add(address.ip_address);

                return true;
            });
        },
        availablePoolAddresses() {
            const pool = this.selectedIpPool;

            if (! pool) {
                return [];
            }

            const addresses = Array.isArray(pool.available_addresses) ? [...pool.available_addresses] : [];
            const selectedRouteAddresses = this.ipRoutes
                .map(route => route.ip_address)
                .filter(Boolean);

            return this.uniqueAddresses(addresses).filter(address => ! selectedRouteAddresses.includes(address.ip_address));
        },
        availablePrimaryAddresses() {
            const addresses = this.availablePoolAddresses();

            if (this.form.ip_address && ! addresses.some(address => address.ip_address === this.form.ip_address)) {
                addresses.unshift({
                    id: `current-primary-${this.form.ip_address}`,
                    ip_address: this.form.ip_address,
                });
            }

            return this.uniqueAddresses(addresses);
        },
        syncPrimaryIpSelection() {
            const addresses = this.availablePoolAddresses();

            if (! this.selectedIpPool) {
                this.form.ip_address = '';
                return;
            }

            this.form.ip_address = addresses[0]?.ip_address || '';
            this.ipMessage = addresses.length > 0
                ? {
                    type: 'info',
                    text: `Using ${this.selectedIpPool.name}. The first free IP has been selected.`,
                }
                : {
                    type: 'error',
                    text: 'No free IP addresses are available in the selected pool.',
                };
        },
        handleRouterChange() {
            const pools = this.availableIpPools();

            if (! pools.length) {
                this.form.ip_pool_id = '';
                this.form.ip_address = '';
                this.ipMessage = {
                    type: 'error',
                    text: 'This router does not have any active IP pools.',
                };

                return;
            }

            const currentPoolStillAvailable = pools.some(pool => String(pool.id) === String(this.form.ip_pool_id));

            if (! currentPoolStillAvailable) {
                this.form.ip_pool_id = String(pools[0].id);
            }

            this.syncPrimaryIpSelection();
        },
        handleIpPoolChange() {
            this.syncPrimaryIpSelection();
        },
        availableRouteAddresses(route, index) {
            const pool = this.routeIpPool(route);
            if (! pool) return [];

            const addresses = Array.isArray(pool.available_addresses) ? [...pool.available_addresses] : [];
            if (route.ip_address && ! addresses.some(address => address.ip_address === route.ip_address)) {
                addresses.unshift({
                    id: `current-route-${index}-${route.ip_address}`,
                    ip_address: route.ip_address,
                });
            }

            const selectedAddresses = this.ipRoutes
                .filter((item, itemIndex) => itemIndex !== index)
                .map(item => item.ip_address)
                .filter(Boolean);

            return this.uniqueAddresses(addresses).filter(address => {
                if (address.ip_address === this.form.ip_address && address.ip_address !== route.ip_address) return false;

                return ! selectedAddresses.includes(address.ip_address);
            });
        },
        addIpRoute() {
            this.ipRoutes.push({
                key: this.nextIpRouteKey++,
                ip_pool_id: '',
                ip_address: '',
                cidr: 32,
                sync_status: null,
                sync_error: null,
            });
        },
        removeIpRoute(index) {
            this.ipRoutes.splice(index, 1);
        },
        suggestIpAddress() {
            if (! this.canSuggestIp || this.suggestingIp) {
                return;
            }

            this.suggestingIp = true;
            const pool = this.selectedIpPool;
            const addresses = this.availablePoolAddresses();

            if (! pool) {
                this.ipMessage = {
                    type: 'error',
                    text: 'Select an IP pool first.',
                };
                this.suggestingIp = false;

                return;
            }

            if (! addresses.length) {
                this.form.ip_address = '';
                this.ipMessage = {
                    type: 'error',
                    text: 'No free IP address is available in the selected pool.',
                };
                this.suggestingIp = false;

                return;
            }

            this.ipMessage = {
                type: 'info',
                text: 'Looking up a free IP address...',
            };

            this.form.ip_address = addresses[0].ip_address;
            this.ipMessage = {
                type: 'success',
                text: `Suggested ${addresses[0].ip_address} from ${pool.name}.`,
            };
            this.suggestingIp = false;
        },
    };
}
</script>
@endpush
