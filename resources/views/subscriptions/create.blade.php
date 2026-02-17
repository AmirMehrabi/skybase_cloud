@extends('layouts.admin')

@section('title', 'Create Subscription')

@section('content')
<div x-data="{
    formStep: 1,
    loading: false
}" class="min-h-screen pb-24">
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('subscriptions.index') }}" class="inline-flex items-center text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Create Subscription</h1>
                    <p class="text-sm text-gray-500 mt-1">Add a new customer subscription</p>
                </div>
            </div>
        </div>

        <!-- Progress Steps -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full bg-blue-600 text-white text-sm font-medium">1</div>
                    <span class="text-sm font-medium text-gray-900">Customer & Plan</span>
                </div>
                <div class="flex-1 h-1 mx-4 bg-gray-200 rounded">
                    <div class="h-full bg-blue-600 rounded" style="width: 100%"></div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-200 text-gray-600 text-sm font-medium">2</div>
                    <span class="text-sm font-medium text-gray-500">Network</span>
                </div>
                <div class="flex-1 h-1 mx-4 bg-gray-200 rounded"></div>
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-200 text-gray-600 text-sm font-medium">3</div>
                    <span class="text-sm font-medium text-gray-500">Contract</span>
                </div>
                <div class="flex-1 h-1 mx-4 bg-gray-200 rounded"></div>
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-200 text-gray-600 text-sm font-medium">4</div>
                    <span class="text-sm font-medium text-gray-500">Billing</span>
                </div>
                <div class="flex-1 h-1 mx-4 bg-gray-200 rounded"></div>
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-200 text-gray-600 text-sm font-medium">5</div>
                    <span class="text-sm font-medium text-gray-500">Data & Quota</span>
                </div>
            </div>
        </div>

        <form action="#" method="POST" class="space-y-6">
            @csrf
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
                                <option value="cust-001">Acme Corporation (CUST-001)</option>
                                <option value="cust-002">Smith Residence (CUST-002)</option>
                                <option value="cust-003">Tech Startup Inc (CUST-003)</option>
                                <option value="cust-004">Downtown Cafe (CUST-004)</option>
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
                                <option value="home-25">Home Fiber 25 - $34.99/mo</option>
                                <option value="home-50">Home Fiber 50 - $49.99/mo</option>
                                <option value="home-100">Home Fiber 100 - $69.99/mo</option>
                                <option value="business-100">Fiber Business 100 - $89.99/mo</option>
                                <option value="business-200">Business Fiber 200 - $149.99/mo</option>
                                <option value="pro-500">Fiber Pro 500 - $299.99/mo</option>
                                <option value="enterprise-1g">Enterprise Fiber 1G - $899.99/mo</option>
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
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly (3 months)</option>
                                <option value="yearly">Yearly (12 months)</option>
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
                                placeholder="0.00"
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
                                placeholder="0.00"
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
                                placeholder="0.00"
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
                                placeholder="0.00"
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
                                placeholder="e.g., Downtown Office, West Street"
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
                                <option value="mikrotik-3011">MikroTik RouterBOARD-3011</option>
                                <option value="tp-link-er605">TP-Link ER605</option>
                                <option value="cisco-4431">Cisco ISR 4431</option>
                                <option value="ubiquiti-er12">Ubiquiti EdgeRouter 12</option>
                                <option value="juniper-mx204">Juniper MX204</option>
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
                                <option value="pool-192">192.168.1.0/24</option>
                                <option value="pool-10">10.0.0.0/24</option>
                                <option value="pool-172">172.16.0.0/24</option>
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
                                placeholder="192.168.1.100"
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
                                placeholder="AA:BB:CC:DD:EE:FF"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border"
                            />
                            <p class="text-sm text-gray-500">Format: XX:XX:XX:XX:XX:XX</p>
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
                                placeholder="Enter PPPoE username"
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
                                placeholder="Enter PPPoE password"
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
                                placeholder="0.00"
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
                        <button type="button" x-data="{ checked: true }" @click="checked = !checked" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" :class="checked ? 'bg-blue-600' : 'bg-gray-200'">
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
                                placeholder="Enter any special terms or conditions..."
                                rows="3"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border"
                            ></textarea>
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
                                <option value="1">1st of the month</option>
                                <option value="5">5th of the month</option>
                                <option value="10">10th of the month</option>
                                <option value="15">15th of the month</option>
                                <option value="20">20th of the month</option>
                                <option value="25">25th of the month</option>
                                <option value="last">Last day of the month</option>
                            </select>
                        </div>
                    </div>

                    <!-- Prorated Billing -->
                    <div class="flex items-center justify-between pt-6">
                        <div>
                            <label class="text-sm font-medium text-gray-700">Prorated Billing</label>
                            <p class="text-xs text-gray-500 mt-1">Calculate partial month charges</p>
                        </div>
                        <button type="button" x-data="{ checked: false }" @click="checked = !checked" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" :class="checked ? 'bg-blue-600' : 'bg-gray-200'">
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
                        <button type="button" x-data="{ checked: true }" @click="checked = !checked" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" :class="checked ? 'bg-blue-600' : 'bg-gray-200'">
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
                                placeholder="1000"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border"
                            />
                            <p class="text-sm text-gray-500">Enter 0 for unlimited</p>
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
                                placeholder="100"
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
                                placeholder="50"
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
                        <button type="button" x-data="{ checked: false }" @click="checked = !checked" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" :class="checked ? 'bg-blue-600' : 'bg-gray-200'">
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
                        <button type="button" x-data="{ checked: true }" @click="checked = !checked" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" :class="checked ? 'bg-blue-600' : 'bg-gray-200'">
                            <span class="sr-only">Use setting</span>
                            <span aria-hidden="true" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="checked ? 'translate-x-5' : 'translate-x-0'"></span>
                            <input type="hidden" name="throttle_over_quota" :value="checked ? '1' : '0'">
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Sticky Action Bar -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg z-40 lg:left-64">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex items-center justify-end gap-4">
                <a href="{{ route('subscriptions.index') }}" class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="button" class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                    </svg>
                    Save Draft
                </button>
                <button type="submit" form="#" class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Create & Activate Subscription
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
