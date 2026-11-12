<form action="{{ route('settings.update.payment-gateway') }}" method="POST" class="space-y-6">
    @csrf @method('PUT')
    <x-ui.card title="PayFast South Africa" subtitle="Accept invoice payments through your own PayFast merchant account.">
        <div class="space-y-5">
            <x-ui.input.toggle name="enabled" label="Enable PayFast" description="Allow customers to pay outstanding invoices in their portal." :checked="old('enabled', $gatewaySettings['enabled'])" />
            <x-ui.input.text name="label" label="Checkout label" :value="old('label', $gatewaySettings['label'])" :error="$errors->first('label')" />
            <x-ui.input.select name="environment" label="Mode" :options="['sandbox' => 'Sandbox — test payments', 'live' => 'Live — real payments']" :value="old('environment', $gatewaySettings['environment'])" />
            <p class="text-sm text-slate-500">Invoices must be billed in ZAR. Minimum payment: R5. Sandbox payments do not settle real invoices.</p>
            @error('enabled') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </x-ui.card>
    @foreach(['sandbox' => 'Sandbox credentials', 'live' => 'Live credentials'] as $mode => $title)
        <x-ui.card :title="$title" subtitle="Use the matching credentials and passphrase from your PayFast account. Leave secret fields blank to preserve saved values.">
            <div class="grid gap-5 md:grid-cols-3">
                <x-ui.input.text :name="$mode.'[merchant_id]'" label="Merchant ID" :value="old($mode.'.merchant_id', $gatewaySettings[$mode]['merchant_id'] ?? '')" :error="$errors->first($mode.'.merchant_id')" />
                <x-ui.input.password :name="$mode.'[merchant_key]'" label="Merchant key" autocomplete="new-password" :placeholder="filled($gatewaySettings[$mode]['merchant_key'] ?? null) ? 'Saved — leave blank to keep' : 'Enter merchant key'" :error="$errors->first($mode.'.merchant_key')" />
                <x-ui.input.password :name="$mode.'[passphrase]'" label="Passphrase" autocomplete="new-password" :placeholder="filled($gatewaySettings[$mode]['passphrase'] ?? null) ? 'Saved — leave blank to keep' : 'Enter passphrase'" :error="$errors->first($mode.'.passphrase')" />
            </div>
        </x-ui.card>
    @endforeach
    <x-ui.card title="Payment methods" subtitle="Leave all unchecked to offer every method available on your PayFast account. Selected methods must also be enabled in PayFast.">
        <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3">
            @foreach(\App\Services\PayfastGateway::METHODS as $code => $label)
                <x-ui.input.checkbox name="methods[]" :checked-only="true" :id="'method-'.$code" :value="$code" :label="$label" :checked="in_array($code, old('methods', $gatewaySettings['methods']))" />
            @endforeach
        </div>
    </x-ui.card>
    <x-ui.card title="Recent payment attempts" subtitle="Sandbox transactions never settle real invoices. Payments marked Review need reconciliation against the invoice.">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead><tr><th class="px-3 py-2">Reference</th><th class="px-3 py-2">Invoice</th><th class="px-3 py-2">Amount</th><th class="px-3 py-2">Mode</th><th class="px-3 py-2">Status</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($gatewayAttempts as $attempt)
                        <tr><td class="px-3 py-3"><span class="block text-xs">{{ $attempt->reference }}</span><span class="text-xs text-slate-500">{{ $attempt->provider_reference }}</span></td><td class="px-3 py-3"><a class="text-teal-800 underline" href="{{ route('billing.invoices.show', $attempt->invoice_id) }}">#{{ $attempt->invoice_id }}</a></td><td class="px-3 py-3">R{{ number_format($attempt->amount_minor / 100, 2) }}</td><td class="px-3 py-3 capitalize">{{ $attempt->environment }}</td><td class="px-3 py-3 capitalize">{{ str_replace('_', ' ', $attempt->status) }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="px-3 py-6 text-center text-slate-500">No payment attempts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
    <div class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white px-5 py-4">
        <p class="text-xs text-slate-500">Changes apply to this workspace.</p>
        <x-ui.button type="submit" variant="brand">Save payment gateway</x-ui.button>
    </div>
</form>
