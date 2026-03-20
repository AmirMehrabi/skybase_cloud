@extends('layouts.admin')

@section('title', 'Create New Customer')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="space-y-6 pb-24" x-data="customerCreate()" x-cloak>
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Create New Customer</h1>
            <p class="text-sm text-gray-500 mt-1">Add a new subscriber to your ISP network</p>
        </div>
    </div>

    <form action="{{ route('customers.store') }}" method="POST" @submit.prevent="submit">
        @csrf

        <!-- Section 1: Basic Information -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-3">
                    <x-ui.input.radio
                        label="Customer Type"
                        name="customer_type"
                        :options="{'individual': 'Individual', 'business': 'Business'}"
                        value="individual"
                        x-model="form.customer_type"
                        required
                    />
                </div>

                <template x-if="form.customer_type === 'individual'">
                    <div class="flex gap-6 w-full">
                        <div class="flex-1">
                            <x-ui.input.text
                                label="First Name"
                                name="first_name"
                                x-model="form.first_name"
                                :error="getError('first_name')"
                                required
                            />
                        </div>
                        <div class="flex-1">
                            <x-ui.input.text
                                label="Last Name"
                                name="last_name"
                                x-model="form.last_name"
                                :error="getError('last_name')"
                                required
                            />
                        </div>
                    </div>
                </template>

                <template x-if="form.customer_type === 'business'">
                    <div class="lg:col-span-2">
                        <x-ui.input.text
                            label="Company Name"
                            name="company_name"
                            x-model="form.company_name"
                            :error="getError('company_name')"
                            required
                        />
                    </div>
                </template>

                <div>
                    <x-ui.input.text
                        label="Customer Code"
                        name="customer_code"
                        :value="generatedCustomerCode"
                        readonly
                    />
                </div>

                <template x-if="form.customer_type === 'individual'">
                    <div>
                        <x-ui.input.text
                            label="National ID / SSN"
                            name="national_id"
                            x-model="form.national_id"
                        />
                    </div>
                </template>
            </div>
        </div>

        <!-- Section 2: Contact Information -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Contact Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <x-ui.input.text
                        label="Email Address"
                        name="email"
                        type="email"
                        x-model="form.email"
                        :error="getError('email')"
                        required
                    />
                </div>
                <div>
                    <x-ui.input.text
                        label="Phone Number"
                        name="phone"
                        x-model="form.phone"
                    />
                </div>
                <div>
                    <x-ui.input.text
                        label="Mobile Number"
                        name="mobile"
                        x-model="form.mobile"
                        :error="getError('mobile')"
                        required
                    />
                </div>
                <div>
                    <x-ui.input.text
                        label="WhatsApp Number"
                        name="whatsapp"
                        x-model="form.whatsapp"
                    />
                </div>
            </div>
        </div>

        <!-- Section 3: Address Information -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Address Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                    <x-ui.input.text
                        label="Address Line 1"
                        name="address_line1"
                        x-model="form.address_line1"
                        :error="getError('address_line1')"
                        required
                    />
                </div>
                <div>
                    <x-ui.input.text
                        label="Address Line 2"
                        name="address_line2"
                        x-model="form.address_line2"
                    />
                </div>
                <div>
                    <x-ui.input.text
                        label="City"
                        name="city"
                        x-model="form.city"
                        :error="getError('city')"
                        required
                    />
                </div>
                <div>
                    <x-ui.input.text
                        label="State / Province"
                        name="state"
                        x-model="form.state"
                    />
                </div>
                <div>
                    <x-ui.input.text
                        label="Postal Code"
                        name="postal_code"
                        x-model="form.postal_code"
                    />
                </div>
                <div>
                    <x-ui.input.select
                        label="Country"
                        name="country"
                        :options="{
                            'United States': 'United States',
                            'Canada': 'Canada',
                            'United Kingdom': 'United Kingdom',
                            'Germany': 'Germany',
                            'France': 'France',
                            'Australia': 'Australia'
                        }"
                        x-model="form.country"
                        required
                    />
                </div>
            </div>
        </div>

        <!-- Section 4: Service Assignment -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Service Assignment</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <x-ui.input.select
                        label="Service Plan"
                        name="plan_id"
                        :options="@json($plans->pluck('name', 'id')->map(fn($name, $id) => $name . ' - $' . $plans->find($id)->price . '/' . $plans->find($id)->billing_cycle))"
                        x-model="form.plan_id"
                        :error="getError('plan_id')"
                        required
                    />
                </div>
                <div>
                    <x-ui.input.select
                        label="Router / NAS"
                        name="router_id"
                        :options="@json($routers->pluck('name', 'id')->map(fn($name, $id) => $name . ' (' . $routers->find($id)->vendor . ' ' . $routers->find($id)->model . ')'))"
                        x-model="form.router_id"
                        :error="getError('router_id')"
                        required
                    />
                </div>
                <div>
                    <x-ui.input.text
                        label="PPPoE Username"
                        name="pppoe_username"
                        x-model="form.pppoe_username"
                    />
                </div>
                <div>
                    <x-ui.input.password
                        label="PPPoE Password"
                        name="pppoe_password"
                        x-model="form.pppoe_password"
                    />
                </div>
                <div>
                    <x-ui.input.select
                        label="Billing Cycle"
                        name="billing_cycle"
                        :options="{
                            'monthly': 'Monthly',
                            'quarterly': 'Quarterly (3 months)',
                            'yearly': 'Yearly (12 months)'
                        }"
                        x-model="form.billing_cycle"
                        required
                    />
                </div>
            </div>
        </div>

        <!-- Section 5: Financial Settings -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Financial Settings</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <x-ui.input.text
                        label="Initial Balance"
                        name="balance"
                        type="number"
                        step="0.01"
                        x-model="form.balance"
                        hint="Positive = debit owed, Negative = credit balance"
                    />
                </div>
                <div>
                    <x-ui.input.text
                        label="Credit Limit"
                        name="credit_limit"
                        type="number"
                        step="0.01"
                        x-model="form.credit_limit"
                        hint="Maximum allowed debt amount"
                    />
                </div>
                <div class="flex items-center pt-6">
                    <x-ui.input.checkbox
                        label="Tax Exempt"
                        name="tax_exempt"
                        x-model="form.tax_exempt"
                    />
                </div>
            </div>
        </div>

        <!-- Section 6: Status -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Status & Activation</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <x-ui.input.select
                        label="Initial Status"
                        name="status"
                        :options="{
                            'pending': 'Pending Activation',
                            'active': 'Active',
                            'suspended': 'Suspended'
                        }"
                        x-model="form.status"
                        required
                    />
                </div>
                <div class="flex items-center pt-6">
                    <x-ui.input.checkbox
                        label="Auto-activate service on save"
                        name="auto_activate"
                        x-model="form.auto_activate"
                    />
                </div>
            </div>
        </div>

        <!-- Sticky Bottom Action Bar -->
        <div class="fixed bottom-0 right-0 left-0 lg:left-64 bg-white border-t border-gray-200 shadow-lg p-4 z-40">
            <div class="flex items-center justify-end gap-3">
                <a href="/customers" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">
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
                    <span x-text="submitting ? 'Creating...' : 'Create Customer'"></span>
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function customerCreate() {
    return {
        form: {
            customer_type: 'individual',
            first_name: '',
            last_name: '',
            company_name: '',
            national_id: '',
            email: '',
            phone: '',
            mobile: '',
            whatsapp: '',
            address_line1: '',
            address_line2: '',
            city: '',
            state: '',
            postal_code: '',
            country: 'United States',
            plan_id: '',
            router_id: '',
            pppoe_username: '',
            pppoe_password: '',
            billing_type: 'postpaid',
            billing_cycle: 'monthly',
            balance: '0',
            credit_limit: '0',
            tax_exempt: false,
            status: 'pending',
            auto_activate: false
        },
        errors: {},
        submitting: false,

        get generatedCustomerCode() {
            const prefix = 'CUS';
            const timestamp = new Date().toISOString().slice(2, 10).replace(/-/g, '');
            const random = Math.random().toString(36).substring(2, 6).toUpperCase();
            return `${prefix}-${timestamp}-${random}`;
        },

        getError(field) {
            return this.errors[field] || null;
        },

        clearError(field) {
            delete this.errors[field];
        },

        async submit() {
            this.submitting = true;
            this.errors = {};

            const formData = new FormData(document.querySelector('form'));
            formData.append('auto_activate', this.form.auto_activate ? '1' : '0');
            formData.append('tax_exempt', this.form.tax_exempt ? '1' : '0');

            try {
                const response = await fetch('{{ route('customers.store') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.ok) {
                    window.location.href = '/customers';
                } else if (response.status === 422 && data.errors) {
                    this.errors = data.errors;
                } else {
                    alert(data.message || 'An error occurred while creating the customer.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while creating the customer.');
            } finally {
                this.submitting = false;
            }
        }
    };
}
</script>
@endpush
@endsection
