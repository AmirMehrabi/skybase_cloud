<form action="{{ route('settings.update.billing-tax') }}" method="POST" class="space-y-6">
    @csrf @method('PUT')
    <x-ui.card title="Default tax" subtitle="Set the tax applied to future subscription items and invoices. Customer and organization exemptions still apply.">
        <div class="space-y-5">
            <x-ui.input.toggle name="tax_enabled" label="Enable tax calculation" description="Apply your default rate to eligible subscription items." :checked="old('tax_enabled', $taxSettings['enabled'] ?? false)" />
            <div class="grid gap-5 md:grid-cols-2">
                <x-ui.input.text name="tax_name" label="Tax label" :value="old('tax_name', $taxSettings['name'] ?? 'Tax')" :error="$errors->first('tax_name')" />
                <x-ui.input.text type="number" name="tax_percentage" label="Default tax percentage (%)" step="0.01" min="0" max="100" :value="old('tax_percentage', $taxSettings['percentage'] ?? 0)" :error="$errors->first('tax_percentage')" />
            </div>
            <x-ui.input.toggle name="show_tax_id_on_invoice" label="Show tax ID on invoices" description="Use the tax identity saved in Company & localization." :checked="old('show_tax_id_on_invoice', $taxSettings['show_tax_id_on_invoice'] ?? false)" />
            <x-ui.input.textarea name="invoice_note" label="Invoice tax note" :value="old('invoice_note', $taxSettings['invoice_note'] ?? '')" :error="$errors->first('invoice_note')" rows="3" />
        </div>
    </x-ui.card>
    <x-ui.card title="Existing subscriptions" subtitle="Choose whether this change should also update existing subscription items.">
        <x-ui.input.toggle name="sync_existing_subscription_items" label="Update existing subscription items" description="Recalculate taxes on eligible existing items using these settings." :checked="old('sync_existing_subscription_items', false)" />
    </x-ui.card>
    <div class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white px-5 py-4"><p class="text-xs text-slate-500">Changes apply to this workspace.</p><x-ui.button type="submit" variant="brand">Save billing settings</x-ui.button></div>
</form>
