@extends('layouts.admin')

@section('title', 'Edit Customer')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<x-form.client-validation />

<div class="space-y-6 pb-24" x-data="customerEditForm()" x-cloak>
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('customers.show', $customer) }}" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit Customer</h1>
                <p class="text-sm text-gray-500 mt-1">{{ $customer->customer_code }}</p>
            </div>
        </div>
    </div>

    <x-form.validation-summary :errors="$errors" />

    <form action="{{ route('customers.update', $customer) }}" method="POST" class="space-y-6" @submit="if (!validate()) $event.preventDefault()">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Customer Type <span class="text-red-500">*</span></label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="customer_type" value="individual" x-model="form.customer_type" @change="syncTypeFields" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" required>
                            <span class="text-sm text-gray-700">Individual</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="customer_type" value="business" x-model="form.customer_type" @change="syncTypeFields" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                            <span class="text-sm text-gray-700">Business</span>
                        </label>
                    </div>
                    <p x-show="validator.error('customer_type')" x-text="validator.error('customer_type')" class="mt-1 text-sm text-red-600"></p>
                </div>

                <template x-if="form.customer_type === 'individual'">
                    <div class="contents">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
                            <input type="text" name="first_name" id="first_name" x-model="form.first_name" @input="validator.clear('first_name')" :class="'block w-full rounded-lg shadow-sm sm:text-sm py-2 px-3 border ' + validator.fieldClass('first_name')" required>
                            <p x-show="validator.error('first_name')" x-text="validator.error('first_name')" class="mt-1 text-sm text-red-600"></p>
                        </div>
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
                            <input type="text" name="last_name" id="last_name" x-model="form.last_name" @input="validator.clear('last_name')" :class="'block w-full rounded-lg shadow-sm sm:text-sm py-2 px-3 border ' + validator.fieldClass('last_name')" required>
                            <p x-show="validator.error('last_name')" x-text="validator.error('last_name')" class="mt-1 text-sm text-red-600"></p>
                        </div>
                    </div>
                </template>

                <template x-if="form.customer_type === 'business'">
                    <div class="lg:col-span-2">
                        <label for="company_name" class="block text-sm font-medium text-gray-700 mb-1">Company Name <span class="text-red-500">*</span></label>
                        <input type="text" name="company_name" id="company_name" x-model="form.company_name" @input="validator.clear('company_name')" :class="'block w-full rounded-lg shadow-sm sm:text-sm py-2 px-3 border ' + validator.fieldClass('company_name')" required>
                        <p x-show="validator.error('company_name')" x-text="validator.error('company_name')" class="mt-1 text-sm text-red-600"></p>
                    </div>
                </template>

                <div>
                    <label for="customer_code" class="block text-sm font-medium text-gray-700 mb-1">Customer Code</label>
                    <input type="text" id="customer_code" value="{{ $customer->customer_code }}" readonly class="block w-full rounded-lg border-gray-300 bg-gray-50 sm:text-sm py-2 px-3 border">
                </div>

                <div>
                    <label for="organization_id" class="block text-sm font-medium text-gray-700 mb-1">Organization</label>
                    <select name="organization_id" id="organization_id" x-model="form.organization_id" @change="validator.clear('organization_id')" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border bg-white">
                        <option value="">Unassigned</option>
                        @foreach($organizations as $organization)
                            <option value="{{ $organization->id }}">{{ $organization->name }}</option>
                        @endforeach
                    </select>
                    <p x-show="validator.error('organization_id')" x-text="validator.error('organization_id')" class="mt-1 text-sm text-red-600"></p>
                </div>

                <template x-if="form.customer_type === 'individual'">
                    <div>
                        <label for="national_id" class="block text-sm font-medium text-gray-700 mb-1">National ID / SSN</label>
                        <input type="text" name="national_id" id="national_id" x-model="form.national_id" @input="validator.clear('national_id')" :class="'block w-full rounded-lg shadow-sm sm:text-sm py-2 px-3 border ' + validator.fieldClass('national_id')">
                        <p x-show="validator.error('national_id')" x-text="validator.error('national_id')" class="mt-1 text-sm text-red-600"></p>
                    </div>
                </template>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Contact Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <template x-for="field in contactFields" :key="field.name">
                    <div>
                        <label :for="field.name" class="block text-sm font-medium text-gray-700 mb-1">
                            <span x-text="field.label"></span>
                            <span x-show="field.required" class="text-red-500">*</span>
                        </label>
                        <input :type="field.type" :name="field.name" :id="field.name" x-model="form[field.name]" @input="validator.clear(field.name)" :class="'block w-full rounded-lg shadow-sm sm:text-sm py-2 px-3 border ' + validator.fieldClass(field.name)" :required="field.required">
                        <p x-show="validator.error(field.name)" x-text="validator.error(field.name)" class="mt-1 text-sm text-red-600"></p>
                    </div>
                </template>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-1">Portal Access</h3>
            <p class="text-sm text-gray-500 mb-4">Leave both fields empty to keep the current customer portal password.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Portal Password</label>
                    <input type="password" name="password" id="password" autocomplete="new-password" x-model="form.password" @input="validator.clear('password')" :class="'block w-full rounded-lg shadow-sm sm:text-sm py-2 px-3 border ' + validator.fieldClass('password')">
                    <p x-show="validator.error('password')" x-text="validator.error('password')" class="mt-1 text-sm text-red-600"></p>
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Portal Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" autocomplete="new-password" x-model="form.password_confirmation" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Address Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <template x-for="field in addressFields" :key="field.name">
                    <div :class="field.wide ? 'lg:col-span-2' : ''">
                        <label :for="field.name" class="block text-sm font-medium text-gray-700 mb-1">
                            <span x-text="field.label"></span>
                            <span x-show="field.required" class="text-red-500">*</span>
                        </label>
                        <input type="text" :name="field.name" :id="field.name" x-model="form[field.name]" @input="validator.clear(field.name)" :class="'block w-full rounded-lg shadow-sm sm:text-sm py-2 px-3 border ' + validator.fieldClass(field.name)" :required="field.required">
                        <p x-show="validator.error(field.name)" x-text="validator.error(field.name)" class="mt-1 text-sm text-red-600"></p>
                    </div>
                </template>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Financial Settings</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <label for="billing_type" class="block text-sm font-medium text-gray-700 mb-1">Billing Type <span class="text-red-500">*</span></label>
                    <select name="billing_type" id="billing_type" x-model="form.billing_type" @change="validator.clear('billing_type')" :class="'block w-full rounded-lg shadow-sm sm:text-sm py-2 px-3 border bg-white ' + validator.fieldClass('billing_type')" required>
                        <option value="prepaid">Prepaid</option>
                        <option value="postpaid">Postpaid</option>
                    </select>
                    <p x-show="validator.error('billing_type')" x-text="validator.error('billing_type')" class="mt-1 text-sm text-red-600"></p>
                </div>
                <div>
                    <label for="balance" class="block text-sm font-medium text-gray-700 mb-1">Balance</label>
                    <input type="number" step="0.01" name="balance" id="balance" x-model="form.balance" @input="validator.clear('balance')" :class="'block w-full rounded-lg shadow-sm sm:text-sm py-2 px-3 border ' + validator.fieldClass('balance')">
                    <p class="text-xs text-gray-500 mt-1">Positive = debit owed, Negative = credit balance</p>
                    <p x-show="validator.error('balance')" x-text="validator.error('balance')" class="mt-1 text-sm text-red-600"></p>
                </div>
                <div>
                    <label for="credit_limit" class="block text-sm font-medium text-gray-700 mb-1">Credit Limit</label>
                    <input type="number" step="0.01" name="credit_limit" id="credit_limit" x-model="form.credit_limit" @input="validator.clear('credit_limit')" :class="'block w-full rounded-lg shadow-sm sm:text-sm py-2 px-3 border ' + validator.fieldClass('credit_limit')">
                    <p class="text-xs text-gray-500 mt-1">Maximum allowed debt amount</p>
                    <p x-show="validator.error('credit_limit')" x-text="validator.error('credit_limit')" class="mt-1 text-sm text-red-600"></p>
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                    <select name="status" id="status" x-model="form.status" @change="validator.clear('status')" :class="'block w-full rounded-lg shadow-sm sm:text-sm py-2 px-3 border bg-white ' + validator.fieldClass('status')" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                    <p x-show="validator.error('status')" x-text="validator.error('status')" class="mt-1 text-sm text-red-600"></p>
                </div>
                <div class="lg:col-span-3 flex flex-wrap items-center gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="billing_enabled" value="0">
                        <input type="checkbox" name="billing_enabled" value="1" x-model="form.billing_enabled" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Billing Enabled</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="tax_exempt" value="0">
                        <input type="checkbox" name="tax_exempt" value="1" x-model="form.tax_exempt" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Tax Exempt</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="fixed bottom-0 right-0 left-0 lg:left-64 bg-white border-t border-gray-200 shadow-lg p-4 z-40">
            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('customers.show', $customer) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Save Changes
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function customerEditForm() {
    return {
        validator: standardFormValidator(
            @js(\App\Support\Validation\ClientValidationRules::customer(includeStatus: true)),
            @js($errors->toArray())
        ),
        form: {
            customer_type: @js(old('customer_type', $customer->customer_type ?? 'individual')),
            organization_id: @js(old('organization_id', $customer->organization_id)),
            first_name: @js(old('first_name', $customer->first_name)),
            last_name: @js(old('last_name', $customer->last_name)),
            company_name: @js(old('company_name', $customer->company_name)),
            national_id: @js(old('national_id', $customer->national_id)),
            email: @js(old('email', $customer->email)),
            phone: @js(old('phone', $customer->phone)),
            mobile: @js(old('mobile', $customer->mobile)),
            whatsapp: @js(old('whatsapp', $customer->whatsapp)),
            address_line1: @js(old('address_line1', $customer->address_line1)),
            address_line2: @js(old('address_line2', $customer->address_line2)),
            city: @js(old('city', $customer->city)),
            state: @js(old('state', $customer->state)),
            postal_code: @js(old('postal_code', $customer->postal_code)),
            country: @js(old('country', $customer->country ?? 'United States')),
            billing_type: @js(old('billing_type', $customer->billing_type ?? 'postpaid')),
            billing_enabled: @js((bool) old('billing_enabled', $customer->billing_enabled)),
            balance: @js(old('balance', $customer->balance)),
            credit_limit: @js(old('credit_limit', $customer->credit_limit)),
            tax_exempt: @js((bool) old('tax_exempt', $customer->tax_exempt)),
            status: @js(old('status', $customer->status ?? 'active')),
            password: '',
            password_confirmation: '',
        },
        contactFields: [
            { name: 'email', label: 'Email Address', type: 'email', required: true },
            { name: 'phone', label: 'Phone Number', type: 'text', required: false },
            { name: 'mobile', label: 'Mobile Number', type: 'text', required: true },
            { name: 'whatsapp', label: 'WhatsApp Number', type: 'text', required: false },
        ],
        addressFields: [
            { name: 'address_line1', label: 'Address Line 1', required: true, wide: true },
            { name: 'address_line2', label: 'Address Line 2', required: false, wide: false },
            { name: 'city', label: 'City', required: true, wide: false },
            { name: 'state', label: 'State / Province', required: false, wide: false },
            { name: 'postal_code', label: 'Postal Code', required: false, wide: false },
            { name: 'country', label: 'Country', required: true, wide: false },
        ],

        syncTypeFields() {
            ['first_name', 'last_name', 'company_name', 'national_id'].forEach(field => this.validator.clear(field));
        },

        validate() {
            return this.validator.validate(this.form);
        },
    };
}
</script>
@endpush
@endsection
