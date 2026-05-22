@php
    $isEdit = isset($organization);
@endphp

<div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Organization Details</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" id="name" value="{{ old('name', $organization->name ?? '') }}" required class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Code</label>
            <input type="text" name="code" id="code" value="{{ old('code', $organization->code ?? '') }}" placeholder="Auto-generated if blank" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
            @error('code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
            <select name="status" id="status" required class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border bg-white">
                <option value="active" @selected(old('status', $organization->status ?? 'active') === 'active')>Active</option>
                <option value="inactive" @selected(old('status', $organization->status ?? 'active') === 'inactive')>Inactive</option>
            </select>
            @error('status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="md:col-span-2">
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea name="description" id="description" rows="3" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">{{ old('description', $organization->description ?? '') }}</textarea>
            @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>
</div>

<div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm" x-data="{ billingEnabled: @js((bool) old('billing_enabled', $organization->billing_enabled ?? false)), discountType: @js(old('default_discount_type', $organization->default_discount_type ?? 'none')) }">
    <div class="flex items-center justify-between gap-4 mb-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">Billing Defaults</h3>
            <p class="text-sm text-gray-500 mt-1">When enabled, these values are enforced on every subscription in this organization.</p>
        </div>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="hidden" name="billing_enabled" value="0">
            <input type="checkbox" name="billing_enabled" value="1" x-model="billingEnabled" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            <span class="text-sm text-gray-700">Billing Enabled</span>
        </label>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" x-show="billingEnabled" x-transition>
        <div>
            <label for="default_plan_id" class="block text-sm font-medium text-gray-700 mb-1">Default Service <span class="text-red-500">*</span></label>
            <select name="default_plan_id" id="default_plan_id" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border bg-white">
                <option value="">Select a service</option>
                @foreach($plans as $plan)
                    <option value="{{ $plan->id }}" @selected((string) old('default_plan_id', $organization->default_plan_id ?? '') === (string) $plan->id)>
                        {{ $plan->name }} - ${{ number_format((float) $plan->price, 2) }}/{{ $plan->billing_cycle }}
                    </option>
                @endforeach
            </select>
            @error('default_plan_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="default_billing_cycle" class="block text-sm font-medium text-gray-700 mb-1">Billing Cycle <span class="text-red-500">*</span></label>
            <select name="default_billing_cycle" id="default_billing_cycle" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border bg-white">
                @foreach(['monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'yearly' => 'Yearly'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('default_billing_cycle', $organization->default_billing_cycle ?? 'monthly') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('default_billing_cycle')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="default_grace_period_days" class="block text-sm font-medium text-gray-700 mb-1">Grace Period <span class="text-red-500">*</span></label>
            <input type="number" min="0" max="365" name="default_grace_period_days" id="default_grace_period_days" value="{{ old('default_grace_period_days', $organization->default_grace_period_days ?? 7) }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
            @error('default_grace_period_days')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="default_discount_type" class="block text-sm font-medium text-gray-700 mb-1">Discount Type</label>
            <select name="default_discount_type" id="default_discount_type" x-model="discountType" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border bg-white">
                <option value="none">None</option>
                <option value="fixed">Fixed</option>
                <option value="percentage">Percentage</option>
            </select>
            @error('default_discount_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="default_discount_amount" class="block text-sm font-medium text-gray-700 mb-1">Discount Amount</label>
            <input type="number" step="0.01" min="0" name="default_discount_amount" id="default_discount_amount" value="{{ old('default_discount_amount', $organization->default_discount_amount ?? 0) }}" :disabled="discountType === 'none'" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border disabled:bg-gray-50">
            @error('default_discount_amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="default_tax_percentage" class="block text-sm font-medium text-gray-700 mb-1">Tax Percentage</label>
            <input type="number" step="0.01" min="0" max="100" name="default_tax_percentage" id="default_tax_percentage" value="{{ old('default_tax_percentage', $organization->default_tax_percentage ?? 0) }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
            @error('default_tax_percentage')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>
</div>
