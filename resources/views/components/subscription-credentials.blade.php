@props(['subscription'])

@if(config('security.password_encryption', false))
    <code>••••••••</code>
@else
    <div x-data="{
        password: null,
        currentPassword: '',
        error: '',
        submitting: false,
        hideTimeout: null,
        open() {
            this.reset();
            this.$refs.dialog.showModal();
        },
        reset() {
            clearTimeout(this.hideTimeout);
            this.password = null;
            this.currentPassword = '';
            this.error = '';
        },
        async reveal() {
            if (this.submitting) { return; }
            this.error = '';
            this.password = null;
            this.submitting = true;
            try {
                const response = await fetch(@js(route('subscriptions.credentials', $subscription)), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token()) },
                    cache: 'no-store',
                    body: JSON.stringify({ current_password: this.currentPassword })
                });
                const data = await response.json();
                if (!this.$refs.dialog.open) { return; }
                if (!response.ok) {
                    this.error = data.errors?.current_password?.[0] || data.message || 'Unable to reveal credentials.';
                    return;
                }
                this.password = data.password;
                clearTimeout(this.hideTimeout);
                this.hideTimeout = setTimeout(() => { this.password = null; }, 30000);
            } catch (error) {
                if (this.$refs.dialog.open) {
                    this.error = 'Unable to reveal credentials. Please try again.';
                }
            } finally {
                this.currentPassword = '';
                this.submitting = false;
            }
        }
    }">
        <x-ui.button variant="secondary" class="w-full" @click="open()" x-bind:disabled="submitting">
            Reveal PPP password
        </x-ui.button>

        <dialog x-ref="dialog" aria-labelledby="credential-dialog-title-{{ $subscription->id }}"
            class="m-auto max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-3xl bg-white p-0 text-gray-900 shadow-2xl backdrop:bg-slate-950/60"
            @close="reset()">
            <form @submit.prevent="reveal()" class="flex flex-col gap-5 p-6" :aria-busy="submitting">
                <div>
                    <h3 id="credential-dialog-title-{{ $subscription->id }}" class="text-lg font-semibold">Reveal PPP password</h3>
                    <p class="mt-1 text-sm text-gray-500">Confirm your password to reveal the PPP password. It will be hidden again after 30 seconds.</p>
                </div>

                <template x-if="password === null">
                    <div>
                        <x-input.password id="credential-current-password-{{ $subscription->id }}" name="current_password" label="Your password" xModel="currentPassword" required />
                    </div>
                </template>

                <p x-show="error" x-text="error" role="alert" class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700"></p>
                <code x-show="password !== null" x-text="password" x-cloak class="block break-all rounded-xl border border-gray-200 bg-gray-50 p-3 text-sm"></code>

                <div class="flex justify-end gap-3">
                    <x-ui.button variant="secondary" @click="$refs.dialog.close()">Close</x-ui.button>
                    <x-ui.button type="submit" x-show="password === null" x-bind:disabled="submitting">
                        <span x-text="submitting ? 'Verifying…' : 'Reveal PPP password'"></span>
                    </x-ui.button>
                </div>
            </form>
        </dialog>
    </div>
@endif
