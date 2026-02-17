@section('title', 'Edit Customer')

<div class="space-y-6 pb-24">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('customers.index') }}" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit Customer</h1>
                <p class="text-sm text-gray-500 mt-1">CUS-2024-0001 • Last updated: Feb 20, 2024 at 2:30 PM</p>
            </div>
        </div>
        <x-ui-badge :status="$status">
            {{ ucfirst($status) }}
        </x-ui-badge>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Main Form (3 columns) -->
        <div class="lg:col-span-3 space-y-6">
            <!-- Section 1: Basic Information -->
            <x-ui-card title="Basic Information">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-3">
                        <x-ui.input.radio
                            label="Customer Type"
                            name="customer_type"
                            :options="['individual' => 'Individual', 'business' => 'Business']"
                            wire:model.live="customerType"
                        />
                    </div>

                    @if($customerType === 'individual')
                        <x-ui.input.text label="First Name" name="first_name" wire:model="firstName" required />
                        <x-ui.input.text label="Last Name" name="last_name" wire:model="lastName" required />
                    @endif

                    @if($customerType === 'business')
                        <x-ui.input.text label="Company Name" name="company_name" wire:model="companyName" required />
                    @endif

                    <x-ui.input.text label="National ID" name="national_id" wire:model="nationalId" />
                </div>
            </x-ui-card>

            <!-- Section 2: Contact Information -->
            <x-ui-card title="Contact Information">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-ui.input.text label="Email" type="email" name="email" wire:model="email" required />
                    <x-ui.input.text label="Phone" name="phone" wire:model="phone" />
                    <x-ui.input.text label="Mobile" name="mobile" wire:model="mobile" required />
                    <x-ui.input.text label="WhatsApp" name="whatsapp" wire:model="whatsappNumber" />
                </div>
            </x-ui-card>

            <!-- Section 3: Service Assignment -->
            <x-ui-card title="Service Assignment">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-ui.input.select
                        label="Service Plan"
                        name="plan"
                        :options="['Fiber 50 Mbps' => 'Fiber 50 Mbps', 'Fiber 100 Mbps' => 'Fiber 100 Mbps', 'Fiber 200 Mbps' => 'Fiber 200 Mbps', 'Fiber 500 Mbps' => 'Fiber 500 Mbps', 'Fiber 1 Gbps' => 'Fiber 1 Gbps']"
                        wire:model="plan"
                        required
                    />

                    <x-ui.input.select
                        label="Site"
                        name="site"
                        :options="['Downtown Hub' => 'Downtown Hub', 'Business Park' => 'Business Park', 'North Tower' => 'North Tower']"
                        wire:model="site"
                        required
                    />

                    <x-ui.input.select
                        label="Router"
                        name="router"
                        :options="['Mikrotik-01' => 'Mikrotik-01', 'Mikrotik-02' => 'Mikrotik-02', 'Cisco-01' => 'Cisco-01']"
                        wire:model="router"
                        required
                    />

                    <x-ui.input.text
                        label="MAC Address"
                        name="mac_address"
                        wire:model="macAddress"
                        placeholder="AA:BB:CC:DD:EE:FF"
                    />

                    <x-ui.input.text label="PPPoE Username" name="pppoe_username" wire:model="pppoeUsername" />

                    <x-ui.input.text label="PPPoE Password" type="password" name="pppoe_password" wire:model="pppoePassword" />
                </div>
            </x-ui-card>

            <!-- Section 4: Financial Settings -->
            <x-ui-card title="Financial Settings">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-ui.input.text label="Initial Balance" type="number" step="0.01" name="initial_balance" wire:model="initialBalance" />
                    <x-ui.input.text label="Credit Limit" type="number" step="0.01" name="credit_limit" wire:model="creditLimit" />
                    <div class="flex items-center pt-6">
                        <x-ui.input.checkbox label="Tax Exempt" name="tax_exempt" wire:model="taxExempt" />
                    </div>
                </div>
            </x-ui-card>

            <!-- Section 5: Status -->
            <x-ui-card title="Status">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-ui.input.select
                        label="Status"
                        name="status"
                        :options="['active' => 'Active', 'suspended' => 'Suspended', 'terminated' => 'Terminated', 'pending' => 'Pending']"
                        wire:model="status"
                    />
                </div>
            </x-ui-card>
        </div>

        <!-- Quick Actions Sidebar (1 column) -->
        <div class="lg:col-span-1">
            <x-ui-card title="Quick Actions" :padding="'p-4'">
                <div class="space-y-3">
                    <button wire:click="suspendService" class="w-full flex items-center gap-3 px-4 py-3 text-left rounded-xl hover:bg-yellow-50 transition-colors group">
                        <div class="w-10 h-10 rounded-lg bg-yellow-100 text-yellow-600 flex items-center justify-center group-hover:bg-yellow-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Suspend Service</p>
                            <p class="text-xs text-gray-500">Temporarily disable</p>
                        </div>
                    </button>

                    <button wire:click="resetPPPoE" class="w-full flex items-center gap-3 px-4 py-3 text-left rounded-xl hover:bg-blue-50 transition-colors group">
                        <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center group-hover:bg-blue-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Reset PPPoE</p>
                            <p class="text-xs text-gray-500">Generate new password</p>
                        </div>
                    </button>

                    <a href="#" class="w-full flex items-center gap-3 px-4 py-3 text-left rounded-xl hover:bg-green-50 transition-colors group">
                        <div class="w-10 h-10 rounded-lg bg-green-100 text-green-600 flex items-center justify-center group-hover:bg-green-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Create Invoice</p>
                            <p class="text-xs text-gray-500">Generate new invoice</p>
                        </div>
                    </a>

                    <a href="#" class="w-full flex items-center gap-3 px-4 py-3 text-left rounded-xl hover:bg-purple-50 transition-colors group">
                        <div class="w-10 h-10 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center group-hover:bg-purple-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Create Ticket</p>
                            <p class="text-xs text-gray-500">Open support ticket</p>
                        </div>
                    </a>
                </div>
            </x-ui-card>

            <x-ui-card title="Account Info" :padding="'p-4'">
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Customer Code</span>
                        <span class="font-medium text-gray-900">CUS-2024-0001</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Created</span>
                        <span class="font-medium text-gray-900">Jan 15, 2024</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Activated</span>
                        <span class="font-medium text-gray-900">Jan 16, 2024</span>
                    </div>
                </div>
            </x-ui-card>
        </div>
    </div>

    <!-- Sticky Bottom Action Bar -->
    <div class="fixed bottom-0 right-0 left-0 lg:left-64 bg-white border-t border-gray-200 shadow-lg p-4 z-40">
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('customers.show', $customerId) }}">
                <x-ui-button variant="secondary" size="md">
                    Cancel
                </x-ui-button>
            </a>
            <x-ui.button variant="primary" wire:click="update" size="md">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Save Changes
            </x-ui.button>
        </div>
    </div>
</div>
