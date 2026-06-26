<form action="{{ route('settings.update.billing-tax') }}" method="POST" class="space-y-6">
    @csrf
    @method('PUT')

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Tax Settings</h3>
            <p class="mt-1 text-sm text-gray-500">Configure the default tenant tax used for future subscription items and invoices.</p>
        </div>

        <div class="space-y-6">
            <div class="flex items-center justify-between rounded-lg border border-gray-200 p-4">
                <div>
                    <label for="tax_enabled" class="text-sm font-medium text-gray-700">Enable tax calculation</label>
                    <p class="mt-1 text-xs text-gray-500">When enabled, normal customers use this default rate unless they are tax exempt or belong to a billing-enabled organization.</p>
                </div>
                <input type="hidden" name="tax_enabled" value="0">
                <input id="tax_enabled" type="checkbox" name="tax_enabled" value="1" @checked(old('tax_enabled', $taxSettings['enabled'] ?? false)) class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <label for="tax_name" class="block text-sm font-medium text-gray-700 mb-1">Tax Label</label>
                    <input id="tax_name" type="text" name="tax_name" value="{{ old('tax_name', $taxSettings['name'] ?? 'Tax') }}" class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('tax_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="tax_percentage" class="block text-sm font-medium text-gray-700 mb-1">Default Tax Percentage</label>
                    <div class="flex gap-2">
                        <input id="tax_percentage" type="number" step="0.01" min="0" max="100" name="tax_percentage" value="{{ old('tax_percentage', $taxSettings['percentage'] ?? 0) }}" class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <span class="inline-flex items-center rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm text-gray-600">%</span>
                    </div>
                    @error('tax_percentage')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex items-center justify-between rounded-lg border border-gray-200 p-4">
                <div>
                    <label for="show_tax_id_on_invoice" class="text-sm font-medium text-gray-700">Show tax ID on invoices</label>
                    <p class="mt-1 text-xs text-gray-500">Uses the tenant tax ID from General settings when invoice templates display tax identity.</p>
                </div>
                <input type="hidden" name="show_tax_id_on_invoice" value="0">
                <input id="show_tax_id_on_invoice" type="checkbox" name="show_tax_id_on_invoice" value="1" @checked(old('show_tax_id_on_invoice', $taxSettings['show_tax_id_on_invoice'] ?? false)) class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            </div>

            <div>
                <label for="invoice_note" class="block text-sm font-medium text-gray-700 mb-1">Invoice Tax Note</label>
                <textarea id="invoice_note" name="invoice_note" rows="3" class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('invoice_note', $taxSettings['invoice_note'] ?? '') }}</textarea>
                @error('invoice_note')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4">
                <input id="sync_existing_subscription_items" type="checkbox" name="sync_existing_subscription_items" value="1" class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <div>
                    <label for="sync_existing_subscription_items" class="text-sm font-medium text-amber-900">Apply to existing active subscriptions for future invoices</label>
                    <p class="mt-1 text-xs leading-5 text-amber-800">This updates subscription items and subscription totals only. Existing invoice items and issued invoice totals are not changed.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
            Save Billing Settings
        </button>
    </div>
</form>
