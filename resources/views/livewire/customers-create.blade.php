@section('title', 'Create New Customer')

<div class="space-y-6 pb-24">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Create New Customer</h1>
            <p class="text-sm text-gray-500 mt-1">Add a new subscriber to your ISP network</p>
        </div>
    </div>

    <!-- Section 1: Basic Information -->
    <x-ui.card title="Basic Information">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Customer Type -->
            <div class="lg:col-span-3">
                <x-ui.input.radio
                    label="Customer Type"
                    name="customer_type"
                    :options="['individual' => 'Individual', 'business' => 'Business']"
                    wire:model.live="customerType"
                />
            </div>

            <!-- First Name -->
            @if($customerType === 'individual')
                <x-ui.input.text
                    label="First Name"
                    name="first_name"
                    wire:model="firstName"
                    required
                />
            @endif

            <!-- Last Name -->
            @if($customerType === 'individual')
                <x-ui.input.text
                    label="Last Name"
                    name="last_name"
                    wire:model="lastName"
                    required
                />
            @endif

            <!-- Company Name -->
            @if($customerType === 'business')
                <x-ui.input.text
                    label="Company Name"
                    name="company_name"
                    wire:model="companyName"
                    required
                />
            @endif

            <!-- Customer Code (Auto Generated) -->
            <x-ui.input.text
                label="Customer Code"
                name="customer_code"
                :value="'CUS-2024-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT)"
                readonly
            />

            <!-- National ID -->
            @if($customerType === 'individual')
                <x-ui.input.text
                    label="National ID / SSN"
                    name="national_id"
                    wire:model="nationalId"
                />
            @endif

            <!-- Registration Number -->
            @if($customerType === 'business')
                <x-ui.input.text
                    label="Registration Number"
                    name="registration_number"
                    wire:model="registrationNumber"
                />
            @endif

            <!-- Date of Birth -->
            @if($customerType === 'individual')
                <x-ui.input.text
                    label="Date of Birth"
                    type="date"
                    name="date_of_birth"
                    wire:model="dateOfBirth"
                />
            @endif
        </div>
    </x-ui.card>

    <!-- Section 2: Contact Information -->
    <x-ui.card title="Contact Information">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <x-ui.input.text
                label="Email Address"
                type="email"
                name="email"
                wire:model="email"
                required
            />

            <x-ui.input.text
                label="Phone Number"
                name="phone"
                wire:model="phone"
                hint="Landline or primary contact number"
            />

            <x-ui.input.text
                label="Mobile Number"
                name="mobile"
                wire:model="mobile"
                required
            />

            <x-ui.input.text
                label="Secondary Phone"
                name="secondary_phone"
                wire:model="secondaryPhone"
            />

            <x-ui.input.text
                label="WhatsApp Number"
                name="whatsapp_number"
                wire:model="whatsappNumber"
                hint="For notifications and support"
            />
        </div>
    </x-ui.card>

    <!-- Section 3: Address Information -->
    <x-ui.card title="Address Information">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <x-ui.input.textarea
                label="Address Line 1"
                name="address_line1"
                wire:model="addressLine1"
                :rows="2"
                required
            />

            <x-ui.input.textarea
                label="Address Line 2"
                name="address_line2"
                wire:model="addressLine2"
                :rows="2"
            />

            <x-ui.input.text
                label="City"
                name="city"
                wire:model="city"
                required
            />

            <x-ui.input.text
                label="State / Province"
                name="state"
                wire:model="state"
            />

            <x-ui.input.text
                label="Postal Code"
                name="postal_code"
                wire:model="postalCode"
            />

            <x-ui.input.select
                label="Country"
                name="country"
                :options="['United States' => 'United States', 'Canada' => 'Canada', 'United Kingdom' => 'United Kingdom', 'Germany' => 'Germany', 'France' => 'France', 'Australia' => 'Australia', 'Other' => 'Other']"
                wire:model="country"
            />

            <x-ui.input.text
                label="Latitude"
                name="latitude"
                wire:model="latitude"
                hint="For location tracking"
            />

            <x-ui.input.text
                label="Longitude"
                name="longitude"
                wire:model="longitude"
            />

            <!-- Map Placeholder -->
            <div class="lg:col-span-3">
                <label class="block text-sm font-medium text-gray-700 mb-2">Location Map</label>
                <div class="w-full h-48 bg-gray-100 rounded-xl border-2 border-dashed border-gray-300 flex items-center justify-center">
                    <div class="text-center">
                        <svg class="w-12 h-12 text-gray-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <p class="text-sm text-gray-500 mt-2">Map integration placeholder</p>
                        <p class="text-xs text-gray-400">Enter coordinates to pin location</p>
                    </div>
                </div>
            </div>
        </div>
    </x-ui.card>

    <!-- Section 4: Service Assignment -->
    <x-ui.card title="Service Assignment">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <x-ui.input.select
                label="Service Plan"
                name="plan"
                :options="['Fiber 50 Mbps' => 'Fiber 50 Mbps - $29.99/mo', 'Fiber 100 Mbps' => 'Fiber 100 Mbps - $49.99/mo', 'Fiber 200 Mbps' => 'Fiber 200 Mbps - $69.99/mo', 'Fiber 500 Mbps' => 'Fiber 500 Mbps - $99.99/mo', 'Fiber 1 Gbps' => 'Fiber 1 Gbps - $149.99/mo']"
                wire:model="plan"
                required
            />

            <x-ui.input.select
                label="Site / Location"
                name="site"
                :options="['Downtown Hub' => 'Downtown Hub', 'Business Park' => 'Business Park', 'North Tower' => 'North Tower', 'South Station' => 'South Station', 'West Station' => 'West Station', 'East Center' => 'East Center']"
                wire:model="site"
                required
            />

            <x-ui.input.select
                label="Router / NAS"
                name="router"
                :options="['Mikrotik-01' => 'Mikrotik-01 (Downtown)', 'Mikrotik-02' => 'Mikrotik-02 (Business Park)', 'Mikrotik-03' => 'Mikrotik-03 (North)', 'Mikrotik-04' => 'Mikrotik-04 (South)', 'Mikrotik-05' => 'Mikrotik-05 (West)', 'Cisco-01' => 'Cisco-01 (West)', 'Cisco-02' => 'Cisco-02 (Business)', 'Cisco-03' => 'Cisco-03 (North)']"
                wire:model="router"
                required
            />

            <x-ui.input.select
                label="IP Pool"
                name="ip_pool"
                :options="['192.168.1.0/24' => '192.168.1.0/24 (Downtown)', '192.168.2.0/24' => '192.168.2.0/24 (Business)', '192.168.3.0/24' => '192.168.3.0/24 (West)', '192.168.4.0/24' => '192.168.4.0/24 (North)', '192.168.5.0/24' => '192.168.5.0/24 (South)', '192.168.6.0/24' => '192.168.6.0/24 (East)']"
                wire:model="ipPool"
            />

            <div class="flex items-center pt-6">
                <x-ui.input.checkbox
                    label="Assign Static IP Address"
                    name="static_ip"
                    wire:model="staticIp"
                />
            </div>

            <x-ui.input.text
                label="MAC Address"
                name="mac_address"
                wire:model="macAddress"
                hint="Format: AA:BB:CC:DD:EE:FF"
                placeholder="AA:BB:CC:DD:EE:FF"
            />

            <x-ui.input.text
                label="PPPoE Username"
                name="pppoe_username"
                wire:model="pppoeUsername"
                hint="Auto-generated or custom"
            >
                <div class="flex gap-2">
                    <input type="text" wire:model="pppoeUsername" class="flex-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
                    <button type="button" wire:click="generatePPPoE" class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium">
                        Generate
                    </button>
                </div>
            </x-ui.input.text>

            <x-ui.input.text
                label="PPPoE Password"
                type="password"
                name="pppoe_password"
                wire:model="pppoePassword"
            >
                <div class="flex gap-2">
                    <input type="password" wire:model="pppoePassword" class="flex-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
                    <button type="button" wire:click="generatePPPoE" class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium">
                        Generate
                    </button>
                </div>
            </x-ui.input.text>

            <x-ui.input.text
                label="Activation Date"
                type="date"
                name="activation_date"
                wire:model="activationDate"
            />

            <x-ui.input.select
                label="Billing Cycle"
                name="billing_cycle"
                :options="['monthly' => 'Monthly', 'quarterly' => 'Quarterly (3 months)', 'yearly' => 'Yearly (12 months)']"
                wire:model="billingCycle"
            />
        </div>
    </x-ui.card>

    <!-- Section 5: Financial Settings -->
    <x-ui.card title="Financial Settings">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <x-ui.input.text
                label="Initial Balance"
                type="number"
                step="0.01"
                name="initial_balance"
                wire:model="initialBalance"
                hint="Positive = debit owed, Negative = credit balance"
            />

            <x-ui.input.text
                label="Credit Limit"
                type="number"
                step="0.01"
                name="credit_limit"
                wire:model="creditLimit"
                hint="Maximum allowed debt amount"
            />

            <div class="flex items-center pt-6">
                <x-ui.input.checkbox
                    label="Tax Exempt"
                    name="tax_exempt"
                    wire:model="taxExempt"
                />
            </div>

            <x-ui.input.text
                label="Discount Percentage"
                type="number"
                step="0.01"
                min="0"
                max="100"
                name="discount"
                wire:model="discount"
                hint="Apply to all invoices"
            />

            <div class="lg:col-span-2">
                <x-ui.input.textarea
                    label="Notes"
                    name="notes"
                    wire:model="notes"
                    :rows="3"
                    hint="Internal notes about this customer"
                />
            </div>
        </div>
    </x-ui.card>

    <!-- Section 6: Status -->
    <x-ui.card title="Status & Activation">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <x-ui.input.select
                label="Initial Status"
                name="status"
                :options="['pending' => 'Pending Activation', 'active' => 'Active', 'suspended' => 'Suspended']"
                wire:model="status"
            />

            <div class="flex items-center pt-6">
                <x-ui.input.checkbox
                    label="Auto-activate service on save"
                    name="auto_activate"
                    wire:model="autoActivate"
                    hint="Service will be activated immediately"
                />
            </div>
        </div>
    </x-ui.card>

    <!-- Sticky Bottom Action Bar -->
    <div class="fixed bottom-0 right-0 left-0 lg:left-64 bg-white border-t border-gray-200 shadow-lg p-4 z-40">
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('customers.index') }}">
                <x-ui.button variant="secondary" size="md">
                    Cancel
                </x-ui-button>
            </a>
            <x-ui.button variant="secondary" wire:click="saveDraft" size="md">
                Save Draft
            </x-ui.button>
            <x-ui.button variant="primary" wire:click="save" size="md">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Create & Activate
            </x-ui.button>
        </div>
    </div>
</div>
