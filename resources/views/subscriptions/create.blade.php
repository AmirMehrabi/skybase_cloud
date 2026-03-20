@extends('layouts.admin')

@section('title', 'Create Subscription')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="space-y-6 pb-24" x-data="subscriptionCreateForm()" x-cloak>
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Create Subscription</h1>
            <p class="text-sm text-gray-500 mt-1">Add a new subscription with service plan and line items</p>
        </div>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
        <div class="flex">
            <svg class="w-6 h-6 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">There were errors with your submission</h3>
                <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    <form action="{{ route('subscriptions.store') }}" method="POST" class="space-y-6" @submit.prevent="submit">
        @csrf

        <!-- Section 1: Customer & Service Assignment -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Customer & Service Assignment</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Customer -->
                <div class="lg:col-span-1">
                    <label for="customer_id" class="block text-sm font-medium text-gray-700 mb-1">Customer <span class="text-red-500">*</span></label>
                    @if($customer)
                        <input type="text" :value="'{{ $customer->full_name }} ({{ $customer->customer_code }})'" readonly class="block w-full rounded-lg border-gray-300 bg-gray-50 sm:text-sm py-2 px-3 border">
                        <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                    @else
                        <select name="customer_id" id="customer_id" x-model="form.customer_id" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border bg-white" required>
                            <option value="">Select a customer</option>
                            @foreach($customers ?? [] as $cust)
                                <option value="{{ $cust->id }}">{{ $cust->full_name }} ({{ $cust->customer_code }})</option>
                            @endforeach
                        </select>
                    @endif
                    @error('customer_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Plan -->
                <div>
                    <label for="plan_id" class="block text-sm font-medium text-gray-700 mb-1">Service Plan <span class="text-red-500">*</span></label>
                    <select name="plan_id" id="plan_id" x-model="form.plan_id" @change="updatePlanPrice()" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border bg-white" required>
                        <option value="">Select a plan</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" data-price="{{ $plan->price }}">{{ $plan->name }} - ${{ number_format($plan->price, 2) }}/{{ $plan->billing_cycle }}</option>
                        @endforeach
                    </select>
                    @error('plan_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Router / NAS -->
                <div>
                    <label for="router_id" class="block text-sm font-medium text-gray-700 mb-1">Router / NAS <span class="text-red-500">*</span></label>
                    <select name="router_id" id="router_id" x-model="form.router_id" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border bg-white" required>
                        <option value="">Select a router</option>
                        @foreach($routers as $router)
                            <option value="{{ $router->id }}" data-site="{{ $router->site }}">{{ $router->name }} ({{ $router->vendor }} {{ $router->model }})</option>
                        @endforeach
                    </select>
                    @error('router_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Site (auto-filled from router) -->
                <div>
                    <label for="site" class="block text-sm font-medium text-gray-700 mb-1">Site</label>
                    <input type="text" name="site" id="site" x-model="form.site" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
                    @error('site')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- IP Address -->
                <div>
                    <label for="ip_address" class="block text-sm font-medium text-gray-700 mb-1">IP Address</label>
                    <input type="text" name="ip_address" id="ip_address" x-model="form.ip_address" placeholder="192.168.1.100" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
                    @error('ip_address')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Section 2: WAN Connection & IP Management -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">WAN Connection & IP Management</h3>

            <!-- Connection Type Selector -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-3">Connection Type <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- PPPoE Option -->
                    <label class="relative cursor-pointer" @click="form.connection_type = 'pppoe'">
                        <input type="radio" name="connection_type" value="pppoe" x-model="form.connection_type" class="peer sr-only">
                        <div class="p-4 border-2 rounded-xl transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:border-gray-300">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900">PPPoE</h4>
                                    <p class="text-xs text-gray-500">Username/Password auth</p>
                                </div>
                            </div>
                        </div>
                    </label>

                    <!-- DHCP Option -->
                    <label class="relative cursor-pointer" @click="form.connection_type = 'dhcp'">
                        <input type="radio" name="connection_type" value="dhcp" x-model="form.connection_type" class="peer sr-only">
                        <div class="p-4 border-2 rounded-xl transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:border-gray-300">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900">DHCP</h4>
                                    <p class="text-xs text-gray-500">MAC-based assignment</p>
                                </div>
                            </div>
                        </div>
                    </label>

                    <!-- Static IP Option -->
                    <label class="relative cursor-pointer" @click="form.connection_type = 'static'">
                        <input type="radio" name="connection_type" value="static" x-model="form.connection_type" class="peer sr-only">
                        <div class="p-4 border-2 rounded-xl transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:border-gray-300">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900">Static IP</h4>
                                    <p class="text-xs text-gray-500">Fixed IP assignment</p>
                                </div>
                            </div>
                        </div>
                    </label>
                </div>
                @error('connection_type')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- PPPoE Credentials (shown only for PPPoE) -->
            <div x-show="form.connection_type === 'pppoe'" x-transition class="mb-6 p-4 bg-gray-50 rounded-xl border border-gray-200">
                <h4 class="text-sm font-semibold text-gray-900 mb-3">PPPoE Credentials</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="pppoe_username" class="block text-sm font-medium text-gray-700 mb-1">PPPoE Username <span class="text-red-500">*</span></label>
                        <input type="text" name="pppoe_username" id="pppoe_username" x-model="form.pppoe_username" placeholder="e.g., customer001" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
                        @error('pppoe_username')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="pppoe_password" class="block text-sm font-medium text-gray-700 mb-1">PPPoE Password <span class="text-red-500">*</span></label>
                        <input type="password" name="pppoe_password" id="pppoe_password" x-model="form.pppoe_password" placeholder="••••••••" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
                        @error('pppoe_password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- DHCP MAC Address (shown only for DHCP) -->
            <div x-show="form.connection_type === 'dhcp'" x-transition class="mb-6 p-4 bg-gray-50 rounded-xl border border-gray-200">
                <h4 class="text-sm font-semibold text-gray-900 mb-3">DHCP Configuration</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="mac_address" class="block text-sm font-medium text-gray-700 mb-1">MAC Address <span class="text-red-500">*</span></label>
                        <input type="text" name="mac_address" id="mac_address" x-model="form.mac_address" placeholder="00:1A:2B:3C:4D:5E" maxlength="17" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border uppercase">
                        @error('mac_address')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">Format: XX:XX:XX:XX:XX:XX</p>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-600 pt-6">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>MAC address binding required for DHCP assignment</span>
                    </div>
                </div>
            </div>

            <!-- IP Management -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-3">
                    <label class="block text-sm font-medium text-gray-700">IP Management</label>
                    <div class="flex items-center gap-2 text-xs text-gray-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Choose how IP addresses are managed</span>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Router Managed -->
                    <label class="relative cursor-pointer">
                        <input type="radio" name="ip_management" value="router" x-model="form.ip_management" class="peer sr-only">
                        <div class="p-4 border-2 rounded-xl transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:border-gray-300">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900">Router Managed</h4>
                                    <p class="text-xs text-gray-500 mt-1">Router/NAS handles IP assignment via RADIUS or DHCP. No tracking in SkyBase.</p>
                                </div>
                            </div>
                        </div>
                    </label>

                    <!-- System Managed -->
                    <label class="relative cursor-pointer">
                        <input type="radio" name="ip_management" value="system" x-model="form.ip_management" class="peer sr-only">
                        <div class="p-4 border-2 rounded-xl transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:border-gray-300">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900">System Managed</h4>
                                    <p class="text-xs text-gray-500 mt-1">SkyBase tracks and assigns IPs from IP pools. Full inventory management.</p>
                                </div>
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- IP Pool Selection (shown only for System Managed) -->
            <div x-show="form.ip_management === 'system'" x-transition class="p-4 bg-gray-50 rounded-xl border border-gray-200">
                <h4 class="text-sm font-semibold text-gray-900 mb-3">IP Pool Assignment</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="ip_pool_id" class="block text-sm font-medium text-gray-700 mb-1">IP Pool <span class="text-red-500">*</span></label>
                        <select name="ip_pool_id" id="ip_pool_id" x-model="form.ip_pool_id" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border bg-white">
                            <option value="">Select IP Pool</option>
                            @foreach($ipPools ?? [] as $pool)
                                <option value="{{ $pool->id }}" data-available="{{ $pool->available_ips }}">
                                    {{ $pool->name }} ({{ $pool->cidr_notation }}) - {{ $pool->available_ips }} available
                                </option>
                            @endforeach
                        </select>
                        @error('ip_pool_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div x-show="form.ip_pool_id" class="flex items-center gap-2 text-sm pt-6">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-gray-600" x-text="form.ip_pool_id ? selectedIpPool?.available_ips + ' IPs available' : 'Select a pool to see availability'"></span>
                    </div>
                </div>

                <!-- Optional IP Address field (for reference) -->
                <div class="mt-4">
                    <label for="ip_address" class="block text-sm font-medium text-gray-700 mb-1">IP Address (Optional)</label>
                    <input type="text" name="ip_address" id="ip_address" x-model="form.ip_address" placeholder="192.168.1.100" class="block w-full max-w-md rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
                    @error('ip_address')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Leave blank to auto-assign from pool, or specify a particular IP</p>
                </div>
            </div>
        </div>

        <!-- Section 3: Line Items -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Line Items</h3>
                    <p class="text-sm text-gray-500 mt-1">Configure pricing, discounts, and taxes for each item</p>
                </div>
            </div>

            <!-- Plan Line Item (Always Present) -->
            <div class="border border-gray-200 rounded-xl p-4 mb-4 bg-blue-50">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-semibold text-gray-900">Service Plan</h4>
                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded">Primary</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <input type="text" x-model="items[0].description" readonly class="block w-full rounded-lg border-gray-300 bg-gray-50 sm:text-sm py-2 px-3 border">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unit Price</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-gray-500">$</span>
                            <input type="number" step="0.01" x-model="items[0].unit_price" @input="calculateItemTotal(0)" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 pl-7 pr-3 border">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Discount</label>
                        <div class="flex gap-2">
                            <input type="number" step="0.01" x-model="items[0].discount_amount" @input="calculateItemTotal(0)" class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
                            <select x-model="items[0].discount_type" @change="calculateItemTotal(0)" class="w-28 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border bg-white">
                                <option value="none">None</option>
                                <option value="fixed">$</option>
                                <option value="percentage">%</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tax (%)</label>
                        <div class="relative">
                            <input type="number" step="0.01" x-model="items[0].tax_percentage" @input="calculateItemTotal(0)" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
                            <span class="absolute right-3 top-2 text-gray-500">%</span>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3">
                    <div class="text-sm text-gray-600">
                        Subtotal: <span class="font-semibold" x-text="'$' + formatCurrency(items[0].subtotal)"></span>
                    </div>
                    <div class="text-sm text-gray-600">
                        Tax: <span class="font-semibold" x-text="'$' + formatCurrency(items[0].tax_amount)"></span>
                    </div>
                    <div class="text-sm font-semibold text-gray-900">
                        Total: <span class="text-blue-600" x-text="'$' + formatCurrency(items[0].total)"></span>
                    </div>
                </div>
            </div>

            <!-- Additional Service Items -->
            <template x-for="(item, index) in additionalItems" :key="index">
                <div class="border border-gray-200 rounded-xl p-4 mb-4 relative">
                    <button type="button" @click="removeAdditionalItem(index)" class="absolute top-2 right-2 text-red-500 hover:text-red-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="lg:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <input type="text" x-model="item.description" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Unit Price</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-gray-500">$</span>
                                <input type="number" step="0.01" x-model="item.unit_price" @input="calculateAdditionalItemTotal(index)" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 pl-7 pr-3 border">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Discount</label>
                            <div class="flex gap-2">
                                <input type="number" step="0.01" x-model="item.discount_amount" @input="calculateAdditionalItemTotal(index)" class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
                                <select x-model="item.discount_type" @change="calculateAdditionalItemTotal(index)" class="w-28 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border bg-white">
                                    <option value="none">None</option>
                                    <option value="fixed">$</option>
                                    <option value="percentage">%</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tax (%)</label>
                            <div class="relative">
                                <input type="number" step="0.01" x-model="item.tax_percentage" @input="calculateAdditionalItemTotal(index)" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
                                <span class="absolute right-3 top-2 text-gray-500">%</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Billing Cycle</label>
                            <select x-model="item.billing_cycle" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border bg-white">
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="yearly">Yearly</option>
                                <option value="onetime">One-time</option>
                            </select>
                        </div>
                        <div>
                            <label class="flex items-center gap-2 cursor-pointer pt-6">
                                <input type="checkbox" x-model="item.recurring" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-sm text-gray-700">Recurring</span>
                            </label>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3">
                        <div class="text-sm text-gray-600">
                            Subtotal: <span class="font-semibold" x-text="'$' + formatCurrency(item.subtotal)"></span>
                        </div>
                        <div class="text-sm text-gray-600">
                            Tax: <span class="font-semibold" x-text="'$' + formatCurrency(item.tax_amount)"></span>
                        </div>
                        <div class="text-sm font-semibold text-gray-900">
                            Total: <span class="text-blue-600" x-text="'$' + formatCurrency(item.total)"></span>
                        </div>
                    </div>
                    <input type="hidden" :name="'items[' + (index + 1) + '][item_type]'" value="additional_service">
                    <input type="hidden" :name="'items[' + (index + 1) + '][quantity]'" value="1">
                    <input type="hidden" :name="'items[' + (index + 1) + '][description]'" x-model="item.description">
                    <input type="hidden" :name="'items[' + (index + 1) + '][unit_price]'" x-model="item.unit_price">
                    <input type="hidden" :name="'items[' + (index + 1) + '][discount_amount]'" x-model="item.discount_amount">
                    <input type="hidden" :name="'items[' + (index + 1) + '][discount_type]'" x-model="item.discount_type">
                    <input type="hidden" :name="'items[' + (index + 1) + '][tax_percentage]'" x-model="item.tax_percentage">
                    <input type="hidden" :name="'items[' + (index + 1) + '][tax_amount]'" x-model="item.tax_amount">
                    <input type="hidden" :name="'items[' + (index + 1) + '][subtotal]'" x-model="item.subtotal">
                    <input type="hidden" :name="'items[' + (index + 1) + '][total]'" x-model="item.total">
                    <input type="hidden" :name="'items[' + (index + 1) + '][recurring]'" x-model="item.recurring">
                    <input type="hidden" :name="'items[' + (index + 1) + '][billing_cycle]'" x-model="item.billing_cycle">
                </div>
            </template>

            <!-- Add Additional Item Button -->
            <button type="button" @click="addAdditionalItem()" class="w-full py-3 border-2 border-dashed border-gray-300 rounded-lg text-sm font-medium text-gray-600 hover:border-blue-400 hover:text-blue-600 transition-colors">
                <svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add Additional Service
            </button>

            <!-- Hidden inputs for plan item -->
            <input type="hidden" name="items[0][item_type]" value="plan">
            <input type="hidden" name="items[0][quantity]" value="1">
            <input type="hidden" name="items[0][description]" x-model="items[0].description">
            <input type="hidden" name="items[0][unit_price]" x-model="items[0].unit_price">
            <input type="hidden" name="items[0][discount_amount]" x-model="items[0].discount_amount">
            <input type="hidden" name="items[0][discount_type]" x-model="items[0].discount_type">
            <input type="hidden" name="items[0][tax_percentage]" x-model="items[0].tax_percentage">
            <input type="hidden" name="items[0][tax_amount]" x-model="items[0].tax_amount">
            <input type="hidden" name="items[0][subtotal]" x-model="items[0].subtotal">
            <input type="hidden" name="items[0][total]" x-model="items[0].total">
            <input type="hidden" name="items[0][recurring]" value="1">
            <input type="hidden" name="items[0][billing_cycle]" x-model="items[0].billing_cycle">
        </div>

        <!-- Section 4: Summary & Total -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Summary</h3>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Subtotal:</span>
                    <span class="font-medium" x-text="'$' + formatCurrency(form.subtotal)"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Total Discount:</span>
                    <span class="font-medium text-red-600" x-text="'-$' + formatCurrency(form.totalDiscount)"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Total Tax:</span>
                    <span class="font-medium" x-text="'$' + formatCurrency(form.totalTax)"></span>
                </div>
                <div class="border-t border-gray-200 pt-2 mt-2">
                    <div class="flex justify-between">
                        <span class="text-base font-semibold text-gray-900">Total Monthly:</span>
                        <span class="text-xl font-bold text-blue-600" x-text="'$' + formatCurrency(form.total)"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 5: Billing & Dates -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Billing & Schedule</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div>
                    <label for="billing_cycle" class="block text-sm font-medium text-gray-700 mb-1">Billing Cycle <span class="text-red-500">*</span></label>
                    <select name="billing_cycle" id="billing_cycle" x-model="form.billing_cycle" @change="updateBillingCycle()" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border bg-white" required>
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly (3 months)</option>
                        <option value="yearly">Yearly (12 months)</option>
                    </select>
                    @error('billing_cycle')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Initial Status <span class="text-red-500">*</span></label>
                    <select name="status" id="status" x-model="form.status" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border bg-white" required>
                        <option value="pending">Pending Activation</option>
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                    <input type="date" name="start_date" id="start_date" x-model="form.start_date" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
                    @error('start_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                    <input type="date" name="end_date" id="end_date" x-model="form.end_date" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
                    @error('end_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="mt-4">
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="notes" id="notes" x-model="form.notes" rows="3" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border" placeholder="Any additional notes about this subscription..."></textarea>
                @error('notes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Form Actions -->
        <div class="fixed bottom-0 right-0 left-0 lg:left-64 bg-white border-t border-gray-200 shadow-lg p-4 z-40">
            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('subscriptions.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" :disabled="submitting" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg x-show="!submitting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <svg x-show="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="submitting ? 'Creating...' : 'Create Subscription'"></span>
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function subscriptionCreateForm() {
    return {
        // Basic form data
        form: {
            customer_id: '{{ $customer?->id ?? '' }}',
            plan_id: '',
            router_id: '',
            site: '',
            connection_type: 'pppoe',
            ip_management: null,
            ip_pool_id: '',
            mac_address: '',
            ip_address: '',
            pppoe_username: '',
            pppoe_password: '',
            billing_cycle: 'monthly',
            status: 'pending',
            start_date: '',
            end_date: '',
            notes: '',
            subtotal: 0,
            totalDiscount: 0,
            totalTax: 0,
            total: 0
        },

        // IP pools data
        ipPools: @json( $ipPools ),

        // Plan line item (always present)
        items: [{
            description: '',
            unit_price: 0,
            discount_amount: 0,
            discount_type: 'none',
            tax_percentage: 0,
            subtotal: 0,
            tax_amount: 0,
            total: 0,
            recurring: true,
            billing_cycle: 'monthly'
        }],

        // Additional service items
        additionalItems: [],

        submitting: false,

        init() {
            // Pre-fill customer if provided
            @if($customer)
            this.updateCustomerInfo('{{ $customer->full_name }}', '{{ $customer->id }}');
            @endif
        },

        updatePlanPrice() {
            const planSelect = document.getElementById('plan_id');
            const selectedOption = planSelect.options[planSelect.selectedIndex];
            const price = parseFloat(selectedOption?.dataset.price || 0);

            this.items[0].unit_price = price;
            this.items[0].description = selectedOption?.text.split(' - ')[0] || '';
            this.calculateItemTotal(0);
            this.calculateFormTotal();
        },

        updateCustomerInfo(name, id) {
            this.form.customer_id = id;
        },

        updateBillingCycle() {
            this.items[0].billing_cycle = this.form.billing_cycle;
            this.additionalItems.forEach(item => {
                if (item.recurring) {
                    item.billing_cycle = this.form.billing_cycle;
                }
            });
        },

        calculateItemTotal(index) {
            const item = this.items[index];
            const lineTotal = item.unit_price * 1; // quantity is always 1 for plans

            let discount = 0;
            if (item.discount_type === 'fixed') {
                discount = item.discount_amount;
            } else if (item.discount_type === 'percentage') {
                discount = lineTotal * (item.discount_amount / 100);
            }

            const subtotal = Math.max(0, lineTotal - discount);
            const tax = subtotal * (item.tax_percentage / 100);

            item.subtotal = subtotal;
            item.tax_amount = tax;
            item.total = subtotal + tax;

            this.calculateFormTotal();
        },

        calculateAdditionalItemTotal(index) {
            const item = this.additionalItems[index];
            const lineTotal = item.unit_price * 1;

            let discount = 0;
            if (item.discount_type === 'fixed') {
                discount = item.discount_amount;
            } else if (item.discount_type === 'percentage') {
                discount = lineTotal * (item.discount_amount / 100);
            }

            const subtotal = Math.max(0, lineTotal - discount);
            const tax = subtotal * (item.tax_percentage / 100);

            item.subtotal = subtotal;
            item.tax_amount = tax;
            item.total = subtotal + tax;

            this.calculateFormTotal();
        },

        calculateFormTotal() {
            const allItems = [this.items[0], ...this.additionalItems];

            this.form.subtotal = allItems.reduce((sum, item) => sum + item.subtotal, 0);
            this.form.totalDiscount = allItems.reduce((sum, item) => {
                if (item.discount_type === 'fixed') return sum + item.discount_amount;
                return sum;
            }, 0);
            this.form.totalTax = allItems.reduce((sum, item) => sum + item.tax_amount, 0);
            this.form.total = allItems.reduce((sum, item) => sum + item.total, 0);
        },

        addAdditionalItem() {
            this.additionalItems.push({
                description: '',
                unit_price: 0,
                discount_amount: 0,
                discount_type: 'none',
                tax_percentage: 0,
                subtotal: 0,
                tax_amount: 0,
                total: 0,
                recurring: true,
                billing_cycle: this.form.billing_cycle
            });
        },

        removeAdditionalItem(index) {
            this.additionalItems.splice(index, 1);
            this.calculateFormTotal();
        },

        formatCurrency(value) {
            return parseFloat(value || 0).toFixed(2);
        },

        async submit() {
            this.submitting = true;

            const form = document.querySelector('form');

            // Build form data from Alpine.js state
            const formData = new FormData();

            // Add basic fields
            if (this.form.customer_id) formData.append('customer_id', this.form.customer_id);
            if (this.form.plan_id) formData.append('plan_id', this.form.plan_id);
            if (this.form.router_id) formData.append('router_id', this.form.router_id);
            if (this.form.site) formData.append('site', this.form.site);

            // Connection type and IP management
            formData.append('connection_type', this.form.connection_type || 'pppoe');
            if (this.form.mac_address) formData.append('mac_address', this.form.mac_address);
            if (this.form.ip_management) formData.append('ip_management', this.form.ip_management);
            if (this.form.ip_pool_id) formData.append('ip_pool_id', this.form.ip_pool_id);
            if (this.form.ip_address) formData.append('ip_address', this.form.ip_address);
            if (this.form.pppoe_username) formData.append('pppoe_username', this.form.pppoe_username);
            if (this.form.pppoe_password) formData.append('pppoe_password', this.form.pppoe_password);

            if (this.form.billing_cycle) formData.append('billing_cycle', this.form.billing_cycle);
            if (this.form.status) formData.append('status', this.form.status);
            if (this.form.start_date) formData.append('start_date', this.form.start_date);
            if (this.form.end_date) formData.append('end_date', this.form.end_date);
            if (this.form.notes) formData.append('notes', this.form.notes);

            // Add plan line item
            formData.append('items[0][item_type]', 'plan');
            formData.append('items[0][description]', this.items[0].description || '');
            formData.append('items[0][quantity]', '1');
            formData.append('items[0][unit_price]', this.items[0].unit_price || '0');
            formData.append('items[0][discount_amount]', this.items[0].discount_amount || '0');
            formData.append('items[0][discount_type]', this.items[0].discount_type || 'none');
            formData.append('items[0][tax_percentage]', this.items[0].tax_percentage || '0');
            formData.append('items[0][tax_amount]', this.items[0].tax_amount || '0');
            formData.append('items[0][subtotal]', this.items[0].subtotal || '0');
            formData.append('items[0][total]', this.items[0].total || '0');
            formData.append('items[0][recurring]', '1');
            formData.append('items[0][billing_cycle]', this.items[0].billing_cycle || 'monthly');

            // Add additional items
            this.additionalItems.forEach((item, index) => {
                const itemIndex = index + 1;
                formData.append(`items[${itemIndex}][item_type]`, 'additional_service');
                formData.append(`items[${itemIndex}][description]`, item.description || '');
                formData.append(`items[${itemIndex}][quantity]`, '1');
                formData.append(`items[${itemIndex}][unit_price]`, item.unit_price || '0');
                formData.append(`items[${itemIndex}][discount_amount]`, item.discount_amount || '0');
                formData.append(`items[${itemIndex}][discount_type]`, item.discount_type || 'none');
                formData.append(`items[${itemIndex}][tax_percentage]`, item.tax_percentage || '0');
                formData.append(`items[${itemIndex}][tax_amount]`, item.tax_amount || '0');
                formData.append(`items[${itemIndex}][subtotal]`, item.subtotal || '0');
                formData.append(`items[${itemIndex}][total]`, item.total || '0');
                formData.append(`items[${itemIndex}][recurring]`, item.recurring ? '1' : '0');
                formData.append(`items[${itemIndex}][billing_cycle]`, item.billing_cycle || 'monthly');
            });

            // Debug: log formData
            console.log('Submitting data:');
            for (let [key, value] of formData.entries()) {
                console.log(`${key}: ${value}`);
            }

            try {
                const response = await fetch('{{ route('subscriptions.store') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.ok) {
                    // Redirect to subscriptions index on success
                    window.location.href = '{{ route('subscriptions.index') }}';
                } else if (response.status === 422 && data.errors) {
                    // Display validation errors
                    const errorContainer = document.querySelector('.bg-red-50');
                    if (errorContainer) {
                        errorContainer.classList.remove('hidden');
                        const errorList = errorContainer.querySelector('ul');
                        errorList.innerHTML = Object.values(data.errors).flat().map(error => `<li>${error}</li>`).join('');
                    }
                } else {
                    alert(data.message || 'An error occurred while creating the subscription.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while creating the subscription.');
            } finally {
                this.submitting = false;
            }
        }
    };
}
</script>
@endpush
@endsection
