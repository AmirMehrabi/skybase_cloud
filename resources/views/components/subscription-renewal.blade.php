@props(['subscription'])

<div x-data="{
    preview: null,
    loading: false,
    submitting: false,
    confirmed: false,
    error: null,
    async open() {
        this.$refs.dialog.showModal();
        await this.loadPreview();
    },
    close() {
        if (!this.submitting) {
            this.$refs.dialog.close();
        }
    },
    async loadPreview() {
        this.loading = true;
        this.preview = null;
        this.confirmed = false;
        this.error = null;
        try {
            const response = await fetch(@js(route('subscriptions.renewal-preview', $subscription)), {
                headers: { Accept: 'application/json' },
                cache: 'no-store',
            });
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || 'Unable to load the renewal preview.');
            }
            this.preview = data;
        } catch (error) {
            this.error = error.message || 'Unable to load the renewal preview. Please try again.';
        } finally {
            this.loading = false;
        }
    },
    async apply() {
        if (this.submitting || !this.confirmed || !this.preview?.token) {
            return;
        }
        this.submitting = true;
        this.error = null;
        try {
            const response = await fetch(@js(route('subscriptions.renew', $subscription)), {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': @js(csrf_token()),
                },
                body: JSON.stringify({ renewal_token: this.preview.token, confirm_renewal: this.confirmed }),
            });
            const data = await response.json();
            if (!response.ok) {
                throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Unable to renew the subscription.');
            }
            window.location.assign(data.redirect);
        } catch (error) {
            this.error = error.message || 'Unable to renew the subscription. Review a fresh preview before retrying.';
            this.preview.token = null;
            this.submitting = false;
        }
    },
    money(amount) {
        return '$' + Number(amount || 0).toFixed(2);
    },
}">
    <x-ui.button variant="success" class="w-full py-3" @click="open()">Renew</x-ui.button>

    <dialog x-ref="dialog" aria-labelledby="renewal-title"
        class="m-auto max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-3xl bg-white p-0 text-gray-900 shadow-2xl backdrop:bg-slate-950/60"
        @cancel="if (submitting) { $event.preventDefault(); }">
        <form @submit.prevent="apply()" class="flex flex-col gap-5 p-6" :aria-busy="loading || submitting">
            <div>
                <h3 id="renewal-title" class="text-lg font-semibold">Renew service</h3>
                <p class="mt-1 text-sm text-gray-500">The new service cycle starts immediately when you apply the renewal.</p>
            </div>
            <p x-show="loading" role="status" class="text-sm text-gray-500">Loading renewal preview…</p>
            <p x-show="error" x-text="error" role="alert" class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700"></p>
            <template x-if="preview">
                <div class="flex flex-col gap-4">
                    <dl class="grid grid-cols-2 gap-3 text-sm">
                        <dt class="text-gray-500">Plan / cycle</dt>
                        <dd class="text-right font-medium" x-text="preview.plan + ' / ' + preview.billing_cycle"></dd>
                        <dt class="text-gray-500">Current expiry</dt>
                        <dd class="text-right" x-text="preview.current_expiry || 'No expiry set'"></dd>
                        <dt class="text-gray-500">Remaining data</dt>
                        <dd class="text-right" x-text="preview.remaining_bytes === null ? 'Unlimited / not configured' : (preview.remaining_bytes / 1073741824).toFixed(2) + ' GB'"></dd>
                        <dt class="text-gray-500">New expiry</dt>
                        <dd class="text-right font-medium" x-text="preview.new_expiry"></dd>
                        <dt class="text-gray-500">Next billing date</dt>
                        <dd class="text-right" x-text="preview.next_billing_date"></dd>
                        <dt class="text-gray-500">Renewal charge</dt>
                        <dd class="text-right font-medium" x-text="money(preview.charge)"></dd>
                        <dt class="text-gray-500">Existing unpaid balance</dt>
                        <dd class="text-right" x-text="money(preview.outstanding)"></dd>
                        <dt class="font-medium">Total outstanding after renewal</dt>
                        <dd class="text-right font-semibold" x-text="money(preview.total_outstanding)"></dd>
                    </dl>
                    <p class="text-xs text-gray-500">Expiry and billing follow calendar cycles in {{ config('app.timezone') }}. The new expiry is at the end of the last service day. Only recurring items are charged, including their discounts and taxes.</p>
                    <p x-show="!preview.billing_enabled" class="rounded-xl border border-blue-200 bg-blue-50 p-3 text-sm text-blue-700">Billing is disabled for this service. No invoice will be generated.</p>
                    <p x-show="preview.early" class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">This replaces your remaining service period and starts a fresh data allowance. Unused time, data, bonuses, and exemptions are forfeited without credit.</p>
                    <template x-for="warning in preview.warnings" :key="warning">
                        <p x-text="warning" class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800"></p>
                    </template>
                    <p x-show="preview.blocker" x-text="preview.blocker" role="alert" class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700"></p>
                    <div x-show="!preview.blocker">
                        <x-input.checkbox id="confirm_renewal" name="confirm_renewal" xModel="confirmed"
                            label="I confirm the charge and replacement of the current service and data cycle." />
                    </div>
                </div>
            </template>
            <div class="flex flex-wrap justify-end gap-3">
                <x-ui.button variant="secondary" @click="close()" x-bind:disabled="submitting">Cancel</x-ui.button>
                <x-ui.button variant="secondary" x-show="error" @click="loadPreview()" x-bind:disabled="loading || submitting">Refresh preview</x-ui.button>
                <x-ui.button type="submit" variant="success" x-bind:disabled="loading || submitting || !confirmed || !preview?.token">
                    <span x-text="submitting ? 'Renewing…' : (preview?.billing_enabled ? 'Renew & generate invoice' : 'Apply renewal')">Apply renewal</span>
                </x-ui.button>
            </div>
        </form>
    </dialog>
</div>
