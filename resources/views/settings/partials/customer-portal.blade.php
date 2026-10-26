<form action="{{ route('settings.update.customer-portal') }}" method="POST" class="space-y-6">
    @csrf @method('PUT')
    <x-ui.card title="Billing visibility" subtitle="Control whether customers can see financial details in their portal.">
        <x-ui.input.toggle
            name="customer_portal_show_billing"
            label="Show billing information"
            description="Allow customers to view invoices, balances, subscription prices, and billing dates in their portal."
            :checked="old('customer_portal_show_billing', $showBilling)"
        />
    </x-ui.card>
    <div class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white px-5 py-4">
        <p class="text-xs text-slate-500">Changes apply to this workspace.</p>
        <x-ui.button type="submit" variant="brand">Save portal settings</x-ui.button>
    </div>
</form>
