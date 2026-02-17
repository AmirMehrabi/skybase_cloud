@extends('layouts.admin')

@section('title', 'Edit Subscription')

@php
$subscription = [
    'id' => 1,
    'subscription_code' => 'SUB-2024-001',
    'customer_id' => 'cust-001',
    'customer_name' => 'Acme Corporation',
    'plan_id' => 'business-100',
    'plan_name' => 'Fiber Business 100',
    'billing_cycle' => 'monthly',
    'monthly_price' => 89.99,
    'installation_fee' => 150.00,
    'discount_percentage' => 10,
    'tax_percentage' => 8,
    'site' => 'Downtown Office',
    'router_id' => 'mikrotik-3011',
    'router_name' => 'MikroTik RouterBOARD-3011',
    'ip_pool_id' => 'pool-192',
    'ip_address' => '192.168.1.100',
    'mac_address' => 'AA:BB:CC:DD:EE:01',
    'pppoe_username' => 'acme_corp_001',
    'pppoe_password' => '********',
    'contract_start' => '2024-01-15',
    'contract_end' => '2025-01-15',
    'termination_fee' => 250.00,
    'auto_renew' => true,
    'contract_notes' => 'Priority support included. 24/7 monitoring.',
    'start_date' => '2024-01-15',
    'next_billing_date' => '2025-03-15',
    'invoice_day' => '15',
    'prorated_billing' => false,
    'auto_send_invoice' => true,
    'data_quota' => 1000,
    'speed_download' => 100,
    'speed_upload' => 50,
    'burst_mode' => true,
    'throttle_over_quota' => true,
    'status' => 'active',
];

function getStatusBadgeClass($status)
{
    $classes = [
        'active' => 'bg-green-100 text-green-800 border-green-200',
        'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
        'suspended' => 'bg-red-100 text-red-800 border-red-200',
        'cancelled' => 'bg-gray-100 text-gray-800 border-gray-200',
        'expired' => 'bg-gray-100 text-gray-800 border-gray-200',
    ];

    return $classes[$status] ?? 'bg-gray-100 text-gray-800 border-gray-200';
}
@endphp

@section('content')
<div x-data="{
    showSuspendModal: false,
    showActivateModal: false,
    showCancelModal: false,
    showResetPasswordModal: false,
    showGenerateInvoiceModal: false
}" class="min-h-screen pb-24">
    <div class="flex gap-6">
        <!-- Main Form Content -->
        <div class="flex-1 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="{{ route('subscriptions.index') }}" class="inline-flex items-center text-gray-500 hover:text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Edit Subscription</h1>
                        <p class="text-sm text-gray-500 mt-1">{{ $subscription['subscription_code'] }}</p>
                    </div>
                </div>
            </div>

            <form action="#" method="POST" class="space-y-6">
                @csrf
                @method('PUT')
                <!-- SECTION 1: Customer & Plan -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Customer & Plan Information</h3>
                        <p class="text-sm text-gray-500 mt-1">Select customer and service plan details</p>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Customer -->
                        <div class="lg:col-span-2">
                            <div class="space-y-1.5">
                                <label for="customer_id" class="block text-sm font-medium text-gray-700">
                                    Customer
                                    <span class="text-red-500">*</span>
                                </label>
                                <select
                                    id="customer_id"
                                    name="customer_id"
                                    required
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white"
                                >
                                    <option value="">Search and select a customer</option>
                                    <option value="cust-001" {{ $subscription['customer_id'] === 'cust-001' ? 'selected' : '' }}>Acme Corporation (CUST-001)</option>
                                    <option value="cust-002" {{ $subscription['customer_id'] === 'cust-002' ? 'selected' : '' }}>Smith Residence (CUST-002)</option>
                                    <option value="cust-003" {{ $subscription['customer_id'] === 'cust-003' ? 'selected' : '' }}>Tech Startup Inc (CUST-003)</option>
                                    <option value="cust-004" {{ $subscription['customer_id'] === 'cust-004' ? 'selected' : '' }}>Downtown Cafe (CUST-004)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Plan -->
                        <div>
                            <div class="space-y-1.5">
                                <label for="plan_id" class="block text-sm font-medium text-gray-700">
                                    Service Plan
                                    <span class="text-red-500">*</span>
                                </label>
                                <select
                                    id="plan_id"
                                    name="plan_id"
                                    required
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white"
                                >
                                    <option value="">Select a plan</option>
                                    <option value="home-25" {{ $subscription['plan_id'] === 'home-25' ? 'selected' : '' }}>Home Fiber 25 - $34.99/mo</option>
                                    <option value="home-50" {{ $subscription['plan_id'] === 'home-50' ? 'selected' : '' }}>Home Fiber 50 - $49.99/mo</option>
                                    <option value="home-100" {{ $subscription['plan_id'] === 'home-100' ? 'selected' : '' }}>Home Fiber 100 - $69.99/mo</option>
                                    <option value="business-100" {{ $subscription['plan_id'] === 'business-100' ? 'selected' : '' }}>Fiber Business 100 - $89.99/mo</option>
                                    <option value="business-200" {{ $subscription['plan_id'] === 'business-200' ? 'selected' : '' }}>Business Fiber 200 - $149.99/mo</option>
                                    <option value="pro-500" {{ $subscription['plan_id'] === 'pro-500' ? 'selected' : '' }}>Fiber Pro 500 - $299.99/mo</option>
                                    <option value="enterprise-1g" {{ $subscription['plan_id'] === 'enterprise-1g' ? 'selected' : '' }}>Enterprise Fiber 1G - $899.99/mo</option>
                                </select>
                            </div>
                        </div>

                        <!-- Billing Cycle -->
                        <div>
                            <div class="space-y-1.5">
                                <label for="billing_cycle" class="block text-sm font-medium text-gray-700">
                                    Billing Cycle
                                    <span class="text-red-500">*</span>
                                </label>
                                <select
                                    id="billing_cycle"
                                    name="billing_cycle"
                                    required
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white"
                                >
                                    <option value="">Select billing cycle</option>
                                    <option value="monthly" {{ $subscription['billing_cycle'] === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                    <option value="quarterly" {{ $subscription['billing_cycle'] === 'quarterly' ? 'selected' : '' }}>Quarterly (3 months)</option>
                                    <option value="yearly" {{ $subscription['billing_cycle'] === 'yearly' ? 'selected' : '' }}>Yearly (12 months)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Price -->
                        <div>
                            <div class="space-y-1.5">
                                <label for="monthly_price" class="block text-sm font-medium text-gray-700">
                                    Monthly Price
                                    <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="number"
                                    id="monthly_price"
                                    name="monthly_price"
                                    step="0.01"
                                    value="{{ $subscription['monthly_price'] }}"
                                    required
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border"
                                />
                            </div>
                        </div>

                        <!-- Installation Fee -->
                        <div>
                            <div class="space-y-1.5">
                                <label for="installation_fee" class="block text-sm font-medium text-gray-700">Installation Fee</label>
                                <input
                                    type="number"
                                    id="installation_fee"
                                    name="installation_fee"
                                    step="0.01"
                                    value="{{ $subscription['installation_fee'] }}"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border"
                                />
                            </div>
                        </div>

                        <!-- Discount -->
                        <div>
                            <div class="space-y-1.5">
                                <label for="discount_percentage" class="block text-sm font-medium text-gray-700">Discount Percentage</label>
                                <input
                                    type="number"
                                    id="discount_percentage"
                                    name="discount_percentage"
                                    step="0.01"
                                    value="{{ $subscription['discount_percentage'] }}"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border"
                                />
                                <p class="text-sm text-gray-500">Applied to monthly price</p>
                            </div>
                        </div>

                        <!-- Tax -->
                        <div>
                            <div class="space-y-1.5">
                                <label for="tax_percentage" class="block text-sm font-medium text-gray-700">Tax Percentage</label>
                                <input
                                    type="number"
                                    id="tax_percentage"
                                    name="tax_percentage"
                                    step="0.01"
                                    value="{{ $subscription['tax_percentage'] }}"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: Network Configuration -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Network Configuration</h3>
                        <p class="text-sm text-gray-500 mt-1">Configure network and connection settings</p>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Site -->
                        <div>
                            <div class="space-y-1.5">
                                <label for="site" class="block text-sm font-medium text-gray-700">
                                    Site/Location
                                    <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="site"
                                    name="site"
                                    value="{{ $subscription['site'] }}"
                                    required
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border"
                                />
                            </div>
                        </div>

                        <!-- Router -->
                        <div>
                            <div class="space-y-1.5">
                                <label for="router_id" class="block text-sm font-medium text-gray-700">
                                    Router/Device
                                    <span class="text-red-500">*</span>
                                </label>
                                <select
                                    id="router_id"
                                    name="router_id"
                                    required
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white"
                                >
                                    <option value="">Select router</option>
                                    <option value="mikrotik-3011" {{ $subscription['router_id'] === 'mikrotik-3011' ? 'selected' : '' }}>MikroTik RouterBOARD-3011</option>
                                    <option value="tp-link-er605" {{ $subscription['router_id'] === 'tp-link-er605' ? 'selected' : '' }}>TP-Link ER605</option>
                                    <option value="cisco-4431" {{ $subscription['router_id'] === 'cisco-4431' ? 'selected' : '' }}>Cisco ISR 4431</option>
                                    <option value="ubiquiti-er12" {{ $subscription['router_id'] === 'ubiquiti-er12' ? 'selected' : '' }}>Ubiquiti EdgeRouter 12</option>
                                    <option value="juniper-mx204" {{ $subscription['router_id'] === 'juniper-mx204' ? 'selected' : '' }}>Juniper MX204</option>
                                </select>
                            </div>
                        </div>

                        <!-- IP Pool -->
                        <div>
                            <div class="space-y-1.5">
                                <label for="ip_pool_id" class="block text-sm font-medium text-gray-700">IP Pool</label>
                                <select
                                    id="ip_pool_id"
                                    name="ip_pool_id"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white"
                                >
                                    <option value="">Select IP pool</option>
                                    <option value="pool-192" {{ $subscription['ip_pool_id'] === 'pool-192' ? 'selected' : '' }}>192.168.1.0/24</option>
                                    <option value="pool-10" {{ $subscription['ip_pool_id'] === 'pool-10' ? 'selected' : '' }}>10.0.0.0/24</option>
                                    <option value="pool-172" {{ $subscription['ip_pool_id'] === 'pool-172' ? 'selected' : '' }}>172.16.0.0/24</option>
                                </select>
                            </div>
                        </div>

                        <!-- Static IP -->
                        <div>
                            <div class="space-y-1.5">
                                <label for="ip_address" class="block text-sm font-medium text-gray-700">Static IP Address</label>
                                <input
                                    type="ip"
                                    id="ip_address"
                                    name="ip_address"
                                    value="{{ $subscription['ip_address'] }}"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border"
                                />
                            </div>
                        </div>

                        <!-- MAC Address -->
                        <div>
                            <div class="space-y-1.5">
                                <label for="mac_address" class="block text-sm font-medium text-gray-700">MAC Address</label>
                                <input
                                    type="text"
                                    id="mac_address"
                                    name="mac_address"
                                    value="{{ $subscription['mac_address'] }}"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border"
                                />
                            </div>
                        </div>

                        <!-- PPPoE Username -->
                        <div>
                            <div class="space-y-1.5">
                                <label for="pppoe_username" class="block text-sm font-medium text-gray-700">
                                    PPPoE Username
                                    <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="pppoe_username"
                                    name="pppoe_username"
                                    value="{{ $subscription['pppoe_username'] }}"
                                    required
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border"
                                />
                            </div>
                        </div>

                        <!-- PPPoE Password -->
                        <div>
                            <div class="space-y-1.5">
                                <label for="pppoe_password" class="block text-sm font-medium text-gray-700">
                                    PPPoE Password
                                    <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="password"
                                    id="pppoe_password"
                                    name="pppoe_password"
                                    value="********"
                                    required
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 3: Contract Information -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Contract Information</h3>
                        <p class="text-sm text-gray-500 mt-1">Set contract terms and conditions</p>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Contract Start Date -->
                        <div>
                            <div class="space-y-1.5">
                                <label for="contract_start" class="block text-sm font-medium text-gray-700">
                                    Contract Start Date
                                    <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="date"
                                    id="contract_start"
                                    name="contract_start"
                                    value="{{ $subscription['contract_start'] }}"
                                    required
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border"
                                />
                            </div>
                        </div>

                        <!-- Contract End Date -->
                        <div>
                            <div class="space-y-1.5">
                                <label for="contract_end" class="block text-sm font-medium text-gray-700">
                                    Contract End Date
                                    <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="date"
                                    id="contract_end"
                                    name="contract_end"
                                    value="{{ $subscription['contract_end'] }}"
                                    required
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border"
                                />
                            </div>
                        </div>

                        <!-- Early Termination Fee -->
                        <div>
                            <div class="space-y-1.5">
                                <label for="termination_fee" class="block text-sm font-medium text-gray-700">Early Termination Fee</label>
                                <input
                                    type="number"
                                    id="termination_fee"
                                    name="termination_fee"
                                    step="0.01"
                                    value="{{ $subscription['termination_fee'] }}"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border"
                                />
                            </div>
                        </div>

                        <!-- Auto Renew -->
                        <div class="flex items-center justify-between pt-6">
                            <div>
                                <label class="text-sm font-medium text-gray-700">Auto Renew Contract</label>
                                <p class="text-xs text-gray-500 mt-1">Automatically renew when contract expires</p>
                            </div>
                            <button type="button" x-data="{ checked: {{ $subscription['auto_renew'] ? 'true' : 'false' }} }" @click="checked = !checked" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" :class="checked ? 'bg-blue-600' : 'bg-gray-200'">
                                <span class="sr-only">Use setting</span>
                                <span aria-hidden="true" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="checked ? 'translate-x-5' : 'translate-x-0'"></span>
                                <input type="hidden" name="auto_renew" :value="checked ? '1' : '0'">
                            </button>
                        </div>

                        <!-- Notes -->
                        <div class="lg:col-span-2">
                            <div class="space-y-1.5">
                                <label for="contract_notes" class="block text-sm font-medium text-gray-700">Contract Notes</label>
                                <textarea
                                    id="contract_notes"
                                    name="contract_notes"
                                    rows="3"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border"
                                >{{ $subscription['contract_notes'] }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 4: Billing Configuration -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Billing Configuration</h3>
                        <p class="text-sm text-gray-500 mt-1">Configure billing and invoice settings</p>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Start Date -->
                        <div>
                            <div class="space-y-1.5">
                                <label for="start_date" class="block text-sm font-medium text-gray-700">
                                    Service Start Date
                                    <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="date"
                                    id="start_date"
                                    name="start_date"
                                    value="{{ $subscription['start_date'] }}"
                                    required
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border"
                                />
                            </div>
                        </div>

                        <!-- Next Billing Date -->
                        <div>
                            <div class="space-y-1.5">
                                <label for="next_billing_date" class="block text-sm font-medium text-gray-700">
                                    Next Billing Date
                                    <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="date"
                                    id="next_billing_date"
                                    name="next_billing_date"
                                    value="{{ $subscription['next_billing_date'] }}"
                                    required
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border"
                                />
                            </div>
                        </div>

                        <!-- Invoice Generation Day -->
                        <div>
                            <div class="space-y-1.5">
                                <label for="invoice_day" class="block text-sm font-medium text-gray-700">Invoice Generation Day</label>
                                <select
                                    id="invoice_day"
                                    name="invoice_day"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white"
                                >
                                    <option value="">Select day</option>
                                    <option value="1" {{ $subscription['invoice_day'] === '1' ? 'selected' : '' }}>1st of the month</option>
                                    <option value="5" {{ $subscription['invoice_day'] === '5' ? 'selected' : '' }}>5th of the month</option>
                                    <option value="10" {{ $subscription['invoice_day'] === '10' ? 'selected' : '' }}>10th of the month</option>
                                    <option value="15" {{ $subscription['invoice_day'] === '15' ? 'selected' : '' }}>15th of the month</option>
                                    <option value="20" {{ $subscription['invoice_day'] === '20' ? 'selected' : '' }}>20th of the month</option>
                                    <option value="25" {{ $subscription['invoice_day'] === '25' ? 'selected' : '' }}>25th of the month</option>
                                    <option value="last" {{ $subscription['invoice_day'] === 'last' ? 'selected' : '' }}>Last day of the month</option>
                                </select>
                            </div>
                        </div>

                        <!-- Prorated Billing -->
                        <div class="flex items-center justify-between pt-6">
                            <div>
                                <label class="text-sm font-medium text-gray-700">Prorated Billing</label>
                                <p class="text-xs text-gray-500 mt-1">Calculate partial month charges</p>
                            </div>
                            <button type="button" x-data="{ checked: {{ $subscription['prorated_billing'] ? 'true' : 'false' }} }" @click="checked = !checked" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" :class="checked ? 'bg-blue-600' : 'bg-gray-200'">
                                <span class="sr-only">Use setting</span>
                                <span aria-hidden="true" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="checked ? 'translate-x-5' : 'translate-x-0'"></span>
                                <input type="hidden" name="prorated_billing" :value="checked ? '1' : '0'">
                            </button>
                        </div>

                        <!-- Send Invoice Automatically -->
                        <div class="flex items-center justify-between pt-6">
                            <div>
                                <label class="text-sm font-medium text-gray-700">Send Invoice Automatically</label>
                                <p class="text-xs text-gray-500 mt-1">Email invoice to customer on billing date</p>
                            </div>
                            <button type="button" x-data="{ checked: {{ $subscription['auto_send_invoice'] ? 'true' : 'false' }} }" @click="checked = !checked" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" :class="checked ? 'bg-blue-600' : 'bg-gray-200'">
                                <span class="sr-only">Use setting</span>
                                <span aria-hidden="true" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="checked ? 'translate-x-5' : 'translate-x-0'"></span>
                                <input type="hidden" name="auto_send_invoice" :value="checked ? '1' : '0'">
                            </button>
                        </div>
                    </div>
                </div>

                <!-- SECTION 5: Data & Quota -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Data & Quota Settings</h3>
                        <p class="text-sm text-gray-500 mt-1">Configure data limits and speeds</p>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Data Quota -->
                        <div>
                            <div class="space-y-1.5">
                                <label for="data_quota" class="block text-sm font-medium text-gray-700">Data Quota (GB)</label>
                                <input
                                    type="number"
                                    id="data_quota"
                                    name="data_quota"
                                    value="{{ $subscription['data_quota'] }}"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border"
                                />
                            </div>
                        </div>

                        <!-- Speed Download -->
                        <div>
                            <div class="space-y-1.5">
                                <label for="speed_download" class="block text-sm font-medium text-gray-700">
                                    Download Speed (Mbps)
                                    <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="number"
                                    id="speed_download"
                                    name="speed_download"
                                    value="{{ $subscription['speed_download'] }}"
                                    required
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border"
                                />
                            </div>
                        </div>

                        <!-- Speed Upload -->
                        <div>
                            <div class="space-y-1.5">
                                <label for="speed_upload" class="block text-sm font-medium text-gray-700">
                                    Upload Speed (Mbps)
                                    <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="number"
                                    id="speed_upload"
                                    name="speed_upload"
                                    value="{{ $subscription['speed_upload'] }}"
                                    required
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border"
                                />
                            </div>
                        </div>

                        <!-- Burst Mode -->
                        <div class="flex items-center justify-between pt-6">
                            <div>
                                <label class="text-sm font-medium text-gray-700">Burst Mode</label>
                                <p class="text-xs text-gray-500 mt-1">Allow temporary speed boost</p>
                            </div>
                            <button type="button" x-data="{ checked: {{ $subscription['burst_mode'] ? 'true' : 'false' }} }" @click="checked = !checked" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" :class="checked ? 'bg-blue-600' : 'bg-gray-200'">
                                <span class="sr-only">Use setting</span>
                                <span aria-hidden="true" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="checked ? 'translate-x-5' : 'translate-x-0'"></span>
                                <input type="hidden" name="burst_mode" :value="checked ? '1' : '0'">
                            </button>
                        </div>

                        <!-- Throttle After Quota -->
                        <div class="flex items-center justify-between pt-6">
                            <div>
                                <label class="text-sm font-medium text-gray-700">Throttle After Quota Exceeded</label>
                                <p class="text-xs text-gray-500 mt-1">Reduce speed when data limit reached</p>
                            </div>
                            <button type="button" x-data="{ checked: {{ $subscription['throttle_over_quota'] ? 'true' : 'false' }} }" @click="checked = !checked" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" :class="checked ? 'bg-blue-600' : 'bg-gray-200'">
                                <span class="sr-only">Use setting</span>
                                <span aria-hidden="true" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="checked ? 'translate-x-5' : 'translate-x-0'"></span>
                                <input type="hidden" name="throttle_over_quota" :value="checked ? '1' : '0'">
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Sidebar - Quick Actions -->
        <div class="w-80 flex-shrink-0 space-y-6">
            <!-- Status Card -->
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                <div class="text-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium border {{ getStatusBadgeClass($subscription['status']) }}">
                        {{ ucfirst($subscription['status']) }}
                    </span>
                    <p class="text-sm text-gray-500 mt-2">{{ $subscription['subscription_code'] }}</p>
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-500">Monthly Price</span>
                            <span class="font-medium text-gray-900">${{ number_format($subscription['monthly_price'], 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Next Billing</span>
                            <span class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($subscription['next_billing_date'])->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
                </div>
                <div class="space-y-2">
                    @if($subscription['status'] === 'active')
                        <button @click="showSuspendModal = true" class="w-full flex items-center gap-3 px-4 py-3 text-left text-sm text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Suspend Service</span>
                        </button>
                    @elseif($subscription['status'] === 'suspended')
                        <button @click="showActivateModal = true" class="w-full flex items-center gap-3 px-4 py-3 text-left text-sm text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Activate Service</span>
                        </button>
                    @endif

                    <button @click="showResetPasswordModal = true" class="w-full flex items-center gap-3 px-4 py-3 text-left text-sm text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                        </svg>
                        <span>Reset PPPoE Password</span>
                    </button>

                    <button @click="showGenerateInvoiceModal = true" class="w-full flex items-center gap-3 px-4 py-3 text-left text-sm text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span>Generate Invoice</span>
                    </button>

                    <div class="border-t border-gray-200 my-2"></div>

                    <button @click="showCancelModal = true" class="w-full flex items-center gap-3 px-4 py-3 text-left text-sm text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        <span>Cancel Subscription</span>
                    </button>
                </div>
            </div>

            <!-- Customer Info -->
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Customer Information</h3>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $subscription['customer_name'] }}</p>
                            <p class="text-xs text-gray-500">{{ $subscription['customer_id'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sticky Action Bar -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg z-40 lg:left-64">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex items-center justify-end gap-4">
                <a href="{{ route('subscriptions.show', $id) }}" class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" form="#" class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                    </svg>
                    Save Changes
                </button>
            </div>
        </div>
    </div>

    <!-- Suspend Confirmation Modal -->
    <div x-show="showSuspendModal" class="relative z-50" style="display: none;">
        <div x-show="showSuspendModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showSuspendModal = false"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                                <h3 class="text-base font-semibold leading-6 text-gray-900">Suspend Service</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">Are you sure you want to suspend this subscription? The service will be temporarily disabled until reactivated.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="button" @click="showSuspendModal = false" class="inline-flex w-full justify-center rounded-lg bg-yellow-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-yellow-500 sm:ml-3 sm:w-auto">Suspend Service</button>
                        <button type="button" @click="showSuspendModal = false" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Activate Confirmation Modal -->
    <div x-show="showActivateModal" class="relative z-50" style="display: none;">
        <div x-show="showActivateModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showActivateModal = false"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                                <h3 class="text-base font-semibold leading-6 text-gray-900">Activate Service</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">Are you sure you want to activate this subscription? The service will be restored immediately.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="button" @click="showActivateModal = false" class="inline-flex w-full justify-center rounded-lg bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 sm:ml-3 sm:w-auto">Activate Service</button>
                        <button type="button" @click="showActivateModal = false" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Subscription Modal -->
    <div x-show="showCancelModal" class="relative z-50" style="display: none;">
        <div x-show="showCancelModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showCancelModal = false"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                                <h3 class="text-base font-semibold leading-6 text-gray-900">Cancel Subscription</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">Are you sure you want to cancel this subscription? This action cannot be undone. The service will be terminated immediately and the customer will be charged early termination fees.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="button" @click="showCancelModal = false" class="inline-flex w-full justify-center rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">Cancel Subscription</button>
                        <button type="button" @click="showCancelModal = false" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Go Back</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div x-show="showResetPasswordModal" class="relative z-50" style="display: none;">
        <div x-show="showResetPasswordModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showResetPasswordModal = false"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                                <h3 class="text-base font-semibold leading-6 text-gray-900">Reset PPPoE Password</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">Generate a new random password for this subscription's PPPoE connection?</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="button" @click="showResetPasswordModal = false" class="inline-flex w-full justify-center rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 sm:ml-3 sm:w-auto">Generate New Password</button>
                        <button type="button" @click="showResetPasswordModal = false" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Generate Invoice Modal -->
    <div x-show="showGenerateInvoiceModal" class="relative z-50" style="display: none;">
        <div x-show="showGenerateInvoiceModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showGenerateInvoiceModal = false"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-purple-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                                <h3 class="text-base font-semibold leading-6 text-gray-900">Generate Invoice</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">Generate a new invoice for this subscription's upcoming billing cycle?</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="button" @click="showGenerateInvoiceModal = false" class="inline-flex w-full justify-center rounded-lg bg-purple-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-500 sm:ml-3 sm:w-auto">Generate Invoice</button>
                        <button type="button" @click="showGenerateInvoiceModal = false" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
