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

    <div x-cloak x-show="validationErrorsList.length" class="bg-red-50 border border-red-200 rounded-xl p-4">
        <div class="flex">
            <svg class="w-6 h-6 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">There were errors with your submission</h3>
                <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                    <template x-for="(error, index) in validationErrorsList" :key="index">
                        <li x-text="error"></li>
                    </template>
                </ul>
            </div>
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
                        <select name="customer_id" id="customer_id" x-model="form.customer_id" @change="handleCustomerChange($event)" :class="hasValidationError('customer_id') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'" class="block w-full rounded-lg shadow-sm sm:text-sm py-2 px-3 border bg-white" required>
                            <option value="">Select a customer</option>
                            @foreach($customers ?? [] as $cust)
                                <option value="{{ $cust->id }}" data-name="{{ $cust->full_name }}">{{ $cust->full_name }} ({{ $cust->customer_code }})</option>
                            @endforeach
                        </select>
                    @endif
                    @error('customer_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <template x-if="validationError('customer_id') && !{{ $errors->has('customer_id') ? 'true' : 'false' }}">
                        <p class="mt-1 text-sm text-red-600" x-text="validationError('customer_id')"></p>
                    </template>
                </div>

                <!-- Subscription Name -->
                <div class="lg:col-span-1">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" x-model="form.name" @input="subscriptionNameTouched = true" placeholder="Subscription name" :class="hasValidationError('name') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'" class="block w-full rounded-lg shadow-sm sm:text-sm py-2 px-3 border" required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <template x-if="validationError('name') && !{{ $errors->has('name') ? 'true' : 'false' }}">
                        <p class="mt-1 text-sm text-red-600" x-text="validationError('name')"></p>
                    </template>
                </div>

                <div x-show="selectedOrganizationBilling" class="lg:col-span-3 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900" style="display: none;">
                    Billing is managed by <span class="font-semibold" x-text="selectedOrganizationBilling?.organization"></span>. The default service, cycle, grace period, discount, and tax will be enforced.
                </div>

                <!-- Subscription Type -->
                <div class="lg:col-span-3">
                    <div class="flex items-center justify-between mb-3">
                        <label class="block text-sm font-medium text-gray-700">Subscription Type <span class="text-red-500">*</span></label>
                        <span class="text-xs text-gray-500">Controls how this subscription is categorized.</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <label class="relative cursor-pointer" @click="form.service_type = 'hotspot'">
                            <input type="radio" name="service_type" value="hotspot" x-model="form.service_type" class="peer sr-only">
                            <div :class="hasValidationError('service_type') ? 'border-red-500' : 'border-2 border-gray-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:border-gray-300'" class="rounded-xl p-4 transition-all">
                                <p class="text-sm font-semibold text-gray-900">Hotspot</p>
                                <p class="mt-1 text-xs text-gray-500">Captive portal or voucher-based service</p>
                            </div>
                        </label>
                        <label class="relative cursor-pointer" @click="form.service_type = 'pppoe'">
                            <input type="radio" name="service_type" value="pppoe" x-model="form.service_type" class="peer sr-only">
                            <div :class="hasValidationError('service_type') ? 'border-red-500' : 'border-2 border-gray-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:border-gray-300'" class="rounded-xl p-4 transition-all">
                                <p class="text-sm font-semibold text-gray-900">PPPoE</p>
                                <p class="mt-1 text-xs text-gray-500">Broadband access authenticated by PPPoE</p>
                            </div>
                        </label>
                        <label class="relative cursor-pointer" @click="form.service_type = 'vpn'">
                            <input type="radio" name="service_type" value="vpn" x-model="form.service_type" class="peer sr-only">
                            <div :class="hasValidationError('service_type') ? 'border-red-500' : 'border-2 border-gray-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:border-gray-300'" class="rounded-xl p-4 transition-all">
                                <p class="text-sm font-semibold text-gray-900">VPN</p>
                                <p class="mt-1 text-xs text-gray-500">Remote access or private tunnel service</p>
                            </div>
                        </label>
                    </div>
                    @error('service_type')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <template x-if="validationError('service_type') && !{{ $errors->has('service_type') ? 'true' : 'false' }}">
                        <p class="mt-2 text-sm text-red-600" x-text="validationError('service_type')"></p>
                    </template>
                </div>

                <!-- Plan -->
                <div>
                    <label for="plan_id" class="block text-sm font-medium text-gray-700 mb-1">Service Plan <span class="text-red-500">*</span></label>
                    <select name="plan_id" id="plan_id" x-model="form.plan_id" @change="updatePlanPrice()" :disabled="!!selectedOrganizationBilling" :class="hasValidationError('plan_id') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'" class="block w-full rounded-lg shadow-sm sm:text-sm py-2 px-3 border bg-white disabled:bg-gray-50" required>
                        <option value="">Select a plan</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" data-price="{{ $plan->price }}">{{ $plan->name }} - ${{ number_format($plan->price, 2) }}/{{ $plan->billing_cycle }}</option>
                        @endforeach
                    </select>
                    @error('plan_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <template x-if="validationError('plan_id') && !{{ $errors->has('plan_id') ? 'true' : 'false' }}">
                        <p class="mt-1 text-sm text-red-600" x-text="validationError('plan_id')"></p>
                    </template>
                </div>

                <!-- Router / NAS -->
                <div>
                    <label for="router_id" class="block text-sm font-medium text-gray-700 mb-1">Router / NAS <span class="text-red-500">*</span></label>
                    <select name="router_id" id="router_id" x-model="form.router_id" @change="handleRouterChange()" :class="hasValidationError('router_id') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'" class="block w-full rounded-lg shadow-sm sm:text-sm py-2 px-3 border bg-white" required>
                        <option value="">Select a router</option>
                        @foreach($routers as $router)
                            <option value="{{ $router->id }}" data-site="{{ $router->site }}" data-status="{{ $router->status }}">{{ $router->name }} ({{ $router->vendor }} {{ $router->model }}) — {{ ucfirst($router->status) }}</option>
                        @endforeach
                    </select>
                    @error('router_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <template x-if="validationError('router_id') && !{{ $errors->has('router_id') ? 'true' : 'false' }}">
                        <p class="mt-1 text-sm text-red-600" x-text="validationError('router_id')"></p>
                    </template>
                </div>

                <!-- Access Point (filtered by router) -->
                <div x-show="form.router_id" x-transition>
                    <label for="access_point_id" class="block text-sm font-medium text-gray-700 mb-1">Access Point</label>
                    <select name="access_point_id" id="access_point_id" x-model="form.access_point_id" :class="hasValidationError('access_point_id') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'" class="block w-full rounded-lg shadow-sm sm:text-sm py-2 px-3 border bg-white">
                        <option value="">No access point</option>
                        <template x-for="ap in accessPoints" :key="ap.id">
                            <option :value="ap.id" x-text="ap.name + (ap.ssid ? ' (' + ap.ssid + ')' : '') + ' - ' + ap.frequency_band"></option>
                        </template>
                    </select>
                    @error('access_point_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <template x-if="validationError('access_point_id') && !{{ $errors->has('access_point_id') ? 'true' : 'false' }}">
                        <p class="mt-1 text-sm text-red-600" x-text="validationError('access_point_id')"></p>
                    </template>
                </div>

                <!-- Site (auto-filled from router) -->
                <div>
                    <label for="site" class="block text-sm font-medium text-gray-700 mb-1">Site</label>
                    <input type="text" name="site" id="site" x-model="form.site" :class="hasValidationError('site') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'" class="block w-full rounded-lg shadow-sm sm:text-sm py-2 px-3 border">
                    @error('site')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <template x-if="validationError('site') && !{{ $errors->has('site') ? 'true' : 'false' }}">
                        <p class="mt-1 text-sm text-red-600" x-text="validationError('site')"></p>
                    </template>
                </div>

            </div>
        </div>

        <!-- Section 2: WAN Connection & IP Management -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">WAN Connection & IP Management</h3>

            <!-- Connection Type Selector -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-3">Connection Type <span class="text-red-500">*</span></label>
                <div :class="hasValidationError('connection_type') ? 'ring-2 ring-red-500 ring-offset-2 rounded-2xl' : ''" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- PPP Option -->
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
                                    <h4 class="text-sm font-semibold text-gray-900">PPP</h4>
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
                <template x-if="validationError('connection_type') && !{{ $errors->has('connection_type') ? 'true' : 'false' }}">
                    <p class="mt-2 text-sm text-red-600" x-text="validationError('connection_type')"></p>
                </template>
            </div>

            <!-- PPP Credentials (shown only for PPP) -->
            <div x-show="form.connection_type === 'pppoe'" x-transition class="mb-6 p-4 bg-gray-50 rounded-xl border border-gray-200">
                <h4 class="text-sm font-semibold text-gray-900 mb-3">PPP Credentials</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="pppoe_username" class="block text-sm font-medium text-gray-700 mb-1">PPP Username <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="text" name="pppoe_username" id="pppoe_username" x-model="form.pppoe_username" @input="validatePppoeUsername" placeholder="e.g., customer001" :class="'block w-full rounded-lg sm:text-sm py-2 px-3 pr-10 border ' + ((hasValidationError('pppoe_username') || pppoeValidation.isError) ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500')">
                            <!-- Validation status indicator -->
                            <div class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-1">
                                <template x-if="form.pppoe_username && pppoeValidation.isChecking">
                                    <svg class="w-5 h-5 text-gray-400 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </template>
                                <template x-if="form.pppoe_username && !pppoeValidation.isChecking && pppoeValidation.isValid">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </template>
                                <template x-if="form.pppoe_username && !pppoeValidation.isChecking && pppoeValidation.isError">
                                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                </template>
                            </div>
                        </div>
                        <div class="mt-1 space-y-1">
                            <p x-show="pppoeValidation.message" :class="'text-sm ' + (pppoeValidation.isError ? 'text-red-600' : 'text-gray-500')" x-text="pppoeValidation.message"></p>
                        </div>
                        @error('pppoe_username')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <template x-if="validationError('pppoe_username') && !{{ $errors->has('pppoe_username') ? 'true' : 'false' }}">
                            <p class="mt-1 text-sm text-red-600" x-text="validationError('pppoe_username')"></p>
                        </template>
                    </div>
                    <div>
                        <label for="pppoe_password" class="block text-sm font-medium text-gray-700 mb-1">PPP Password <span class="text-red-500">*</span></label>
                        <input type="password" name="pppoe_password" id="pppoe_password" x-model="form.pppoe_password" placeholder="••••••••" :class="hasValidationError('pppoe_password') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'" class="block w-full rounded-lg shadow-sm sm:text-sm py-2 px-3 border">
                        @error('pppoe_password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <template x-if="validationError('pppoe_password') && !{{ $errors->has('pppoe_password') ? 'true' : 'false' }}">
                            <p class="mt-1 text-sm text-red-600" x-text="validationError('pppoe_password')"></p>
                        </template>
                    </div>
                </div>
            </div>

            <!-- DHCP MAC Address (shown only for DHCP) -->
            <div x-show="form.connection_type === 'dhcp'" x-transition class="mb-6 p-4 bg-gray-50 rounded-xl border border-gray-200">
                <h4 class="text-sm font-semibold text-gray-900 mb-3">DHCP Configuration</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="mac_address" class="block text-sm font-medium text-gray-700 mb-1">MAC Address <span class="text-red-500">*</span></label>
                        <input type="text" name="mac_address" id="mac_address" x-model="form.mac_address" placeholder="00:1A:2B:3C:4D:5E" maxlength="17" :class="hasValidationError('mac_address') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'" class="block w-full rounded-lg shadow-sm sm:text-sm py-2 px-3 border uppercase">
                        @error('mac_address')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <template x-if="validationError('mac_address') && !{{ $errors->has('mac_address') ? 'true' : 'false' }}">
                            <p class="mt-1 text-sm text-red-600" x-text="validationError('mac_address')"></p>
                        </template>
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

                <div x-show="form.ip_management === 'router'" x-transition class="mt-4 rounded-xl border border-orange-200 bg-orange-50 p-4">
                    <label for="manual_ip_address" class="block text-sm font-medium text-gray-700 mb-1">Manual IP Address</label>
                    <input type="text" id="manual_ip_address" x-model="form.ip_address" placeholder="192.168.1.100" :class="hasValidationError('ip_address') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'" class="block w-full max-w-md rounded-lg bg-white shadow-sm sm:text-sm py-2 px-3 border">
                    <p class="mt-1 text-xs text-gray-500">Optional fixed IP sent to the router through RADIUS. It is not reserved or tracked in SkyBase IPAM.</p>
                    @error('ip_address')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <template x-if="validationError('ip_address') && !{{ $errors->has('ip_address') ? 'true' : 'false' }}">
                        <p class="mt-1 text-sm text-red-600" x-text="validationError('ip_address')"></p>
                    </template>
                </div>
            </div>

            <!-- IP Pool Selection (shown only for System Managed) -->
            <div x-show="form.ip_management === 'system'" x-transition class="p-4 bg-gray-50 rounded-xl border border-gray-200">
                <h4 class="text-sm font-semibold text-gray-900 mb-3">IP Pool Assignment</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="ip_pool_id" class="block text-sm font-medium text-gray-700 mb-1">IP Pool <span class="text-red-500">*</span></label>
                        <select name="ip_pool_id" id="ip_pool_id" x-model="form.ip_pool_id" @change="form.ip_address = ''" :class="hasValidationError('ip_pool_id') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'" class="block w-full rounded-lg shadow-sm sm:text-sm py-2 px-3 border bg-white">
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
                        <template x-if="validationError('ip_pool_id') && !{{ $errors->has('ip_pool_id') ? 'true' : 'false' }}">
                            <p class="mt-1 text-sm text-red-600" x-text="validationError('ip_pool_id')"></p>
                        </template>
                    </div>
                    <div x-show="form.ip_pool_id" class="flex items-center gap-2 text-sm pt-6">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-gray-600" x-text="form.ip_pool_id ? selectedIpPool?.available_ips + ' IPs available' : 'Select a pool to see availability'"></span>
                    </div>
                </div>

                <!-- Primary IP Address -->
                <div class="mt-4">
                    <label for="ip_address" class="block text-sm font-medium text-gray-700 mb-1">Primary IP Address</label>
                    <select name="ip_address" id="ip_address" x-model="form.ip_address" :class="hasValidationError('ip_address') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'" class="block w-full max-w-md rounded-lg border bg-white px-3 py-2 text-sm font-mono shadow-sm">
                        <option value="">Auto-assign next available IP</option>
                        <template x-for="address in availablePrimaryAddresses()" :key="address.id">
                            <option :value="address.ip_address" x-text="address.ip_address"></option>
                        </template>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Primary IP stays a host address. Routes below may use a subnet.</p>
                    @error('ip_address')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <template x-if="validationError('ip_address') && !{{ $errors->has('ip_address') ? 'true' : 'false' }}">
                        <p class="mt-1 text-sm text-red-600" x-text="validationError('ip_address')"></p>
                    </template>
                </div>

                <div class="mt-6 rounded-xl border border-blue-100 bg-white p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h4 class="text-sm font-semibold text-gray-900">IP Route</h4>
                            <p class="mt-1 text-xs text-gray-500">Optional routed destinations. RouterOS uses each destination as dst-address and the primary IP as gateway.</p>
                        </div>
                        <button type="button" @click="addIpRoute()" class="inline-flex items-center justify-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Add IP Route
                        </button>
                    </div>

                    <div x-show="ipRoutes.length === 0" class="mt-4 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-500">
                        No IP routes configured. Add a row when this customer needs a routed host or subnet behind their primary IP.
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
                            </div>
                        </template>
                    </div>

                    <div class="mt-3 text-xs text-gray-500">
                        Use /32 for a single routed IP. Use a smaller subnet only when the destination is a routed network.
                    </div>
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
                            <input type="number" step="0.01" x-model="items[0].unit_price" @input="calculateItemTotal(0)" :disabled="!!selectedOrganizationBilling" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 pl-7 pr-3 border disabled:bg-gray-50">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Discount</label>
                        <div class="flex gap-2">
                            <input type="number" step="0.01" x-model="items[0].discount_amount" @input="calculateItemTotal(0)" :disabled="!!selectedOrganizationBilling" class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border disabled:bg-gray-50">
                            <select x-model="items[0].discount_type" @change="calculateItemTotal(0)" :disabled="!!selectedOrganizationBilling" class="w-28 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border bg-white disabled:bg-gray-50">
                                <option value="none">None</option>
                                <option value="fixed">$</option>
                                <option value="percentage">%</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tax (%)</label>
                        <div class="relative">
                            <input type="number" step="0.01" x-model="items[0].tax_percentage" @input="calculateItemTotal(0)" :disabled="!!selectedOrganizationBilling" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border disabled:bg-gray-50">
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
                    <select name="billing_cycle" id="billing_cycle" x-model="form.billing_cycle" @change="updateBillingCycle()" :disabled="!!selectedOrganizationBilling" :class="hasValidationError('billing_cycle') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'" class="block w-full rounded-lg shadow-sm sm:text-sm py-2 px-3 border bg-white disabled:bg-gray-50" required>
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly (3 months)</option>
                        <option value="yearly">Yearly (12 months)</option>
                    </select>
                    @error('billing_cycle')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <template x-if="validationError('billing_cycle') && !{{ $errors->has('billing_cycle') ? 'true' : 'false' }}">
                        <p class="mt-1 text-sm text-red-600" x-text="validationError('billing_cycle')"></p>
                    </template>
                </div>
                <div>
                    <label for="grace_period_days" class="block text-sm font-medium text-gray-700 mb-1">Grace Period</label>
                    <div class="flex gap-2">
                        <input type="number" min="0" max="365" step="1" name="grace_period_days" id="grace_period_days" x-model="form.grace_period_days" :disabled="!!selectedOrganizationBilling" :class="hasValidationError('grace_period_days') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'" class="block w-full rounded-lg shadow-sm sm:text-sm py-2 px-3 border disabled:bg-gray-50" placeholder="Plan default">
                        <span class="inline-flex items-center rounded-lg border border-gray-300 bg-gray-50 px-3 text-sm text-gray-600">days</span>
                    </div>
                    @error('grace_period_days')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <template x-if="validationError('grace_period_days') && !{{ $errors->has('grace_period_days') ? 'true' : 'false' }}">
                        <p class="mt-1 text-sm text-red-600" x-text="validationError('grace_period_days')"></p>
                    </template>
                </div>
                <div class="flex items-center justify-between rounded-xl border border-gray-200 p-4">
                    <div>
                        <label for="billing_enabled" class="text-sm font-medium text-gray-700">Billing Enabled</label>
                        <p class="text-xs text-gray-500 mt-1">Create invoices and include this subscription in automated billing.</p>
                    </div>
                    <div>
                        <input type="hidden" name="billing_enabled" value="0">
                        <input type="checkbox" name="billing_enabled" id="billing_enabled" value="1" x-model="form.billing_enabled" @change="if (!form.billing_enabled) form.auto_suspension_enabled = false" :disabled="!!selectedOrganizationBilling" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 disabled:opacity-60">
                    </div>
                    @error('billing_enabled')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex items-center justify-between rounded-xl border border-gray-200 p-4" :class="{ 'bg-gray-50': !form.billing_enabled }">
                    <div>
                        <label for="auto_suspension_enabled" class="text-sm font-medium text-gray-700">Auto Suspension Enabled</label>
                        <p class="text-xs text-gray-500 mt-1">Suspend service when an invoice remains unpaid past its due date.</p>
                    </div>
                    <div>
                        <input type="hidden" name="auto_suspension_enabled" value="0">
                        <input type="checkbox" name="auto_suspension_enabled" id="auto_suspension_enabled" value="1" x-model="form.auto_suspension_enabled" :disabled="!form.billing_enabled" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 disabled:opacity-60">
                    </div>
                    @error('auto_suspension_enabled')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Initial Status <span class="text-red-500">*</span></label>
                    <select name="status" id="status" x-model="form.status" :class="hasValidationError('status') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'" class="block w-full rounded-lg shadow-sm sm:text-sm py-2 px-3 border bg-white" required>
                        <option value="active">Active</option>
                        <option value="pending">Pending Activation</option>
                        <option value="suspended">Suspended</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <template x-if="validationError('status') && !{{ $errors->has('status') ? 'true' : 'false' }}">
                        <p class="mt-1 text-sm text-red-600" x-text="validationError('status')"></p>
                    </template>
                </div>
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                    <input type="date" name="start_date" id="start_date" x-model="form.start_date" :class="hasValidationError('start_date') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'" class="block w-full rounded-lg shadow-sm sm:text-sm py-2 px-3 border">
                    @error('start_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <template x-if="validationError('start_date') && !{{ $errors->has('start_date') ? 'true' : 'false' }}">
                        <p class="mt-1 text-sm text-red-600" x-text="validationError('start_date')"></p>
                    </template>
                </div>
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                    <input type="date" name="end_date" id="end_date" x-model="form.end_date" :class="hasValidationError('end_date') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'" class="block w-full rounded-lg shadow-sm sm:text-sm py-2 px-3 border">
                    @error('end_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <template x-if="validationError('end_date') && !{{ $errors->has('end_date') ? 'true' : 'false' }}">
                        <p class="mt-1 text-sm text-red-600" x-text="validationError('end_date')"></p>
                    </template>
                </div>
            </div>
            <div class="mt-4">
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="notes" id="notes" x-model="form.notes" rows="3" :class="hasValidationError('notes') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'" class="block w-full rounded-lg shadow-sm sm:text-sm py-2 px-3 border" placeholder="Any additional notes about this subscription..."></textarea>
                @error('notes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <template x-if="validationError('notes') && !{{ $errors->has('notes') ? 'true' : 'false' }}">
                    <p class="mt-1 text-sm text-red-600" x-text="validationError('notes')"></p>
                </template>
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
@php
    $customerNames = $customers
        ->mapWithKeys(fn ($customer) => [(string) $customer->id => $customer->full_name])
        ->all();

    $customerBillingProfiles = $customers
        ->mapWithKeys(function ($customer) {
            if (! $customer->organization?->billing_enabled) {
                return [];
            }

            return [
                (string) $customer->id => [
                    'organization' => $customer->organization->name,
                    'plan_id' => (string) $customer->organization->default_plan_id,
                    'billing_cycle' => $customer->organization->default_billing_cycle,
                    'grace_period_days' => $customer->organization->default_grace_period_days,
                    'discount_type' => $customer->organization->default_discount_type,
                    'discount_amount' => (float) $customer->organization->default_discount_amount,
                    'tax_percentage' => (float) $customer->organization->default_tax_percentage,
                ],
            ];
        })
        ->all();
@endphp
<script>
function subscriptionCreateForm() {
    return {
        // Basic form data
        form: {
            customer_id: '{{ $customer?->id ?? '' }}',
            name: @js(old('name', $customer?->full_name ?? '')),
            service_type: @js(old('service_type', 'hotspot')),
            plan_id: '',
            router_id: '',
            access_point_id: '',
            site: '',
            connection_type: 'pppoe',
            ip_management: null,
            ip_pool_id: '',
            mac_address: '',
            ip_address: '',
            pppoe_username: '',
            pppoe_password: '',
            billing_cycle: 'monthly',
            billing_enabled: @js((bool) old('billing_enabled', true)),
            auto_suspension_enabled: @js((bool) old('billing_enabled', true) && (bool) old('auto_suspension_enabled', true)),
            grace_period_days: '',
            status: 'active',
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
        accessPoints: [],
        customerNames: @json($customerNames),
        customerBillingProfiles: @json($customerBillingProfiles),

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
        ipRoutes: [],
        nextIpRouteKey: 1,

        submitting: false,
        subscriptionNameTouched: @js(filled(old('name'))),

        // IP validation state
        ipValidation: {
            isValid: false,
            isError: false,
            isChecking: false,
            message: '',
            errorType: null // 'format', 'out_of_range', 'in_use'
        },

        // PPPoE username validation state
        pppoeValidation: {
            isValid: false,
            isError: false,
            isChecking: false,
            message: '',
            errorType: null // 'taken'
        },
        validationErrors: {},

        get validationErrorsList() {
            return Object.values(this.validationErrors).flat();
        },

        hasValidationError(field) {
            return Array.isArray(this.validationErrors[field]) && this.validationErrors[field].length > 0;
        },

        validationError(field) {
            return this.hasValidationError(field) ? this.validationErrors[field][0] : null;
        },

        setValidationErrors(errors) {
            this.validationErrors = errors || {};

            if (this.validationErrorsList.length > 0) {
                this.$nextTick(() => window.scrollTo({ top: 0, behavior: 'smooth' }));
            }
        },

        // Computed: Selected IP Pool
        get selectedIpPool() {
            if (!this.form.ip_pool_id) return null;
            return this.ipPools.find(pool => pool.id == this.form.ip_pool_id);
        },

        get selectedOrganizationBilling() {
            return this.customerBillingProfiles[String(this.form.customer_id)] || null;
        },

        init() {
            // Pre-fill customer if provided
            @if($customer)
            this.updateCustomerInfo('{{ $customer->full_name }}', '{{ $customer->id }}');
            @endif
            this.applyOrganizationDefaults();
        },

        handleCustomerChange(event) {
            const selectedOption = event.target.options[event.target.selectedIndex];
            this.updateCustomerInfo(selectedOption?.dataset.name || '', this.form.customer_id);
        },

        async handleRouterChange() {
            this.form.access_point_id = '';
            this.accessPoints = [];

            if (!this.form.router_id) return;

            try {
                const response = await fetch(`{{ url('access-points/by-router') }}/${this.form.router_id}`);
                if (response.ok) {
                    this.accessPoints = await response.json();
                }
            } catch (error) {
                console.error('Error loading access points:', error);
            }
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
            if (!this.subscriptionNameTouched || !this.form.name) {
                this.form.name = name || this.customerNames[String(id)] || '';
            }
            this.applyOrganizationDefaults();
        },

        applyOrganizationDefaults() {
            const defaults = this.selectedOrganizationBilling;

            if (!defaults) return;

            this.form.plan_id = defaults.plan_id;
            this.form.billing_cycle = defaults.billing_cycle;
            this.form.grace_period_days = defaults.grace_period_days;
            this.form.billing_enabled = true;
            this.items[0].discount_type = defaults.discount_type;
            this.items[0].discount_amount = defaults.discount_amount;
            this.items[0].tax_percentage = defaults.tax_percentage;
            this.updatePlanPrice();
            this.updateBillingCycle();
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

        addIpRoute() {
            this.ipRoutes.push({
                key: this.nextIpRouteKey++,
                ip_pool_id: '',
                ip_address: '',
                cidr: 32
            });
        },

        removeIpRoute(index) {
            this.ipRoutes.splice(index, 1);
        },

        routeIpPool(route) {
            if (!route.ip_pool_id) return null;

            return this.ipPools.find(pool => pool.id == route.ip_pool_id);
        },

        availablePrimaryAddresses() {
            if (!this.selectedIpPool || !Array.isArray(this.selectedIpPool.available_addresses)) return [];

            const routeAddresses = this.ipRoutes
                .map(route => route.ip_address)
                .filter(Boolean);

            return this.selectedIpPool.available_addresses.filter(address => !routeAddresses.includes(address.ip_address));
        },

        availableRouteAddresses(route, index) {
            const pool = this.routeIpPool(route);
            if (!pool || !Array.isArray(pool.available_addresses)) return [];

            const selectedAddresses = this.ipRoutes
                .filter((item, itemIndex) => itemIndex !== index)
                .map(item => item.ip_address)
                .filter(Boolean);

            return pool.available_addresses.filter(address => {
                if (address.ip_address === this.form.ip_address) return false;

                return !selectedAddresses.includes(address.ip_address);
            });
        },

        formatCurrency(value) {
            return parseFloat(value || 0).toFixed(2);
        },

        // IP Address validation
        async validateIpAddress() {
            const ip = this.form.ip_address.trim();

            if (!ip) {
                this.ipValidation = {
                    isValid: false,
                    isError: false,
                    isChecking: false,
                    message: '',
                    errorType: null
                };
                return;
            }

            // Check IP format
            const ipPattern = /^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/;
            if (!ipPattern.test(ip)) {
                this.ipValidation = {
                    isValid: false,
                    isError: true,
                    isChecking: false,
                    message: 'Invalid IP address format',
                    errorType: 'format'
                };
                return;
            }

            // Check if within pool range (if pool is selected)
            if (this.selectedIpPool) {
                const inRange = this.isIpInRange(ip, this.selectedIpPool);
                if (!inRange) {
                    this.ipValidation = {
                        isValid: false,
                        isError: true,
                        isChecking: false,
                        message: `IP is not in the ${this.selectedIpPool.cidr_notation} range`,
                        errorType: 'out_of_range'
                    };
                    return;
                }
            }

            // Check if IP is already assigned (API call)
            this.ipValidation.isChecking = true;
            this.ipValidation.message = 'Checking IP availability...';

            try {
                const response = await fetch(`/ipam/check-ip?ip=${encodeURIComponent(ip)}`, {
                    credentials: 'same-origin',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.available === false) {
                    this.ipValidation = {
                        isValid: true, // Still valid, just in use
                        isError: true,
                        isChecking: false,
                        message: `This IP is assigned to ${data.customer || 'another customer'}`,
                        errorType: 'in_use'
                    };
                } else {
                    this.ipValidation = {
                        isValid: true,
                        isError: false,
                        isChecking: false,
                        message: 'IP is available',
                        errorType: null
                    };
                }
            } catch (error) {
                // If API fails, just validate format
                console.warn('Could not check IP availability:', error);
                this.ipValidation = {
                    isValid: true,
                    isError: false,
                    isChecking: false,
                    message: 'IP format is valid',
                    errorType: null
                };
            }
        },

        async validatePppoeUsername() {
            const username = this.form.pppoe_username.trim();

            if (!username) {
                this.pppoeValidation = {
                    isValid: false,
                    isError: false,
                    isChecking: false,
                    message: '',
                    errorType: null
                };
                return;
            }

            // Basic validation: username should be at least 3 characters
            if (username.length < 3) {
                this.pppoeValidation = {
                    isValid: false,
                    isError: true,
                    isChecking: false,
                    message: 'Username must be at least 3 characters',
                    errorType: 'format'
                };
                return;
            }

            this.pppoeValidation.isChecking = true;
            this.pppoeValidation.message = 'Checking username availability...';

            try {
                const response = await fetch(`/subscriptions/check-pppoe-username?username=${encodeURIComponent(username)}`, {
                    credentials: 'same-origin',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.available === false) {
                    this.pppoeValidation = {
                        isValid: false,
                        isError: true,
                        isChecking: false,
                        message: data.message,
                        errorType: 'taken'
                    };
                } else {
                    this.pppoeValidation = {
                        isValid: true,
                        isError: false,
                        isChecking: false,
                        message: 'Username is available',
                        errorType: null
                    };
                }
            } catch (error) {
                // If API fails, just validate format
                console.warn('Could not check username availability:', error);
                this.pppoeValidation = {
                    isValid: true,
                    isError: false,
                    isChecking: false,
                    message: 'Username format is valid',
                    errorType: null
                };
            }
        },

        isIpInRange(ip, pool) {
            const ipNum = this.ipToNumber(ip);
            const networkNum = this.ipToNumber(pool.network_address);
            const cidr = pool.cidr;
            const mask = cidr === 0 ? 0 : ~0 << (32 - cidr);

            return (ipNum & mask) === (networkNum & mask);
        },

        ipToNumber(ip) {
            return ip.split('.').reduce((acc, octet) => (acc << 8) + parseInt(octet, 10), 0) >>> 0;
        },

        suggestNextIp(network, cidr) {
            // Simple suggestion: increment the last octet of the network address
            // This is a placeholder - in production, query the database for the real next available IP
            const parts = network.split('.');
            if (parts.length === 4) {
                const lastOctet = parseInt(parts[3], 10) + 1;
                if (lastOctet <= 254) {
                    return `${parts[0]}.${parts[1]}.${parts[2]}.${lastOctet}`;
                }
            }
            return null;
        },

        async submit() {
            this.submitting = true;

            const form = document.querySelector('form');

            // Build form data from Alpine.js state
            const formData = new FormData();

            // Add basic fields
            if (this.form.customer_id) formData.append('customer_id', this.form.customer_id);
            if (this.form.name) formData.append('name', this.form.name);
            formData.append('service_type', this.form.service_type || 'hotspot');
            if (this.form.plan_id) formData.append('plan_id', this.form.plan_id);
            if (this.form.router_id) formData.append('router_id', this.form.router_id);
            if (this.form.access_point_id) formData.append('access_point_id', this.form.access_point_id);
            if (this.form.site) formData.append('site', this.form.site);

            // Connection type and IP management
            formData.append('connection_type', this.form.connection_type || 'pppoe');
            if (this.form.mac_address) formData.append('mac_address', this.form.mac_address);
            if (this.form.ip_management) formData.append('ip_management', this.form.ip_management);
            if (this.form.ip_pool_id) formData.append('ip_pool_id', this.form.ip_pool_id);
            if (this.form.ip_address) formData.append('ip_address', this.form.ip_address);
            if (this.form.ip_management === 'system') {
                this.ipRoutes.forEach((route, index) => {
                    if (!route.ip_pool_id && !route.ip_address) return;

                    formData.append(`ip_routes[${index}][ip_pool_id]`, route.ip_pool_id || '');
                    formData.append(`ip_routes[${index}][ip_address]`, route.ip_address || '');
                    formData.append(`ip_routes[${index}][cidr]`, route.cidr || '32');
                });
            }
            if (this.form.pppoe_username) formData.append('pppoe_username', this.form.pppoe_username);
            if (this.form.pppoe_password) formData.append('pppoe_password', this.form.pppoe_password);

            if (this.form.billing_cycle) formData.append('billing_cycle', this.form.billing_cycle);
            formData.append('billing_enabled', this.form.billing_enabled ? '1' : '0');
            formData.append('auto_suspension_enabled', this.form.billing_enabled && this.form.auto_suspension_enabled ? '1' : '0');
            if (this.form.grace_period_days !== '') formData.append('grace_period_days', this.form.grace_period_days);
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
                    this.setValidationErrors(data.errors);
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
