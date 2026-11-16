<section class="mt-6 space-y-4 border-t border-gray-200 pt-5" aria-label="Sync selected students">
    <form class="space-y-4" @submit.prevent="loadPreview()">
        <p class="text-sm text-gray-600">Select up to 500 students per sync.</p>
        <div class="grid gap-4 md:grid-cols-2">
            <x-ui.input.select name="sync_plan" label="Plan" :options="$plans" required x-model="plan" @change="preview = null" x-bind:disabled="busy" />
            <x-ui.input.select name="sync_router" label="Router" :options="$routers" required x-model="router" @change="pool = ''; preview = null" x-bind:disabled="busy" />
            <x-ui.input.select name="sync_ip_management" label="IP management" :options="['router' => 'Router Managed', 'system' => 'System Managed']" required x-model="management" @change="preview = null" x-bind:disabled="busy" />
            <div x-cloak x-show="management === 'system'">
                <x-ui.input.select name="sync_pool" label="IP pool" :options="$pools->pluck('name', 'id')" x-model="pool" @change="preview = null"
                    x-effect="filterPools($el, router)" x-bind:required="management === 'system'" x-bind:disabled="busy || management !== 'system'" />
            </div>
        </div>
        <fieldset class="grid gap-4 md:grid-cols-2">
            <legend class="mb-2 text-sm font-medium text-gray-900">PPPoE field mapping</legend>
            <x-ui.input.select name="sync_username_field" label="Username field" :options="$credentialOptions" :value="$defaultCredentialFields['username']"
                required x-model="credentialFields.username" @change="preview = null" x-bind:disabled="busy" />
            <x-ui.input.select name="sync_password_field" label="Password field" :options="$credentialOptions" :value="$defaultCredentialFields['password']"
                required x-model="credentialFields.password" @change="preview = null" x-bind:disabled="busy" />
        </fieldset>
        <x-ui.button type="submit" variant="brand" x-bind:disabled="busy || !selected.length">Preview sync</x-ui.button>
    </form>

    <p role="alert" class="text-sm text-red-700" x-show="error" x-text="error"></p>
    <template x-if="preview">
        <div class="space-y-3 text-sm">
            <p x-text="preview.ready + ' of ' + preview.rows.length + ' selected students ready to sync'"></p>
            <p x-text="[preview.settings.plan, preview.settings.router, preview.settings.pool].filter(Boolean).join(' · ')"></p>
            <p x-show="preview.available !== null" x-text="preview.needed + ' new IP assignments needed · ' + preview.available + ' addresses available'"></p>
            <div class="space-y-2">
                <template x-for="row in preview.rows" :key="row.index">
                    <div class="rounded-lg border border-gray-200 p-3">
                        <p class="font-medium" x-text="studentLabel(row.index)"></p>
                        <p x-text="row.message || row.customer_action + ' · ' + row.subscription_action"></p>
                        <template x-if="row.candidates?.length">
                            <div>
                                <x-ui.input.select name="sync_subscription" aria-label="Subscription to sync" :options="['0' => 'Create new subscription']"
                                    x-bind:id="'sync-subscription-' + row.index" x-model="choices[row.index]"
                                    @change="loadPreview()" x-bind:disabled="busy">
                                    <template x-for="candidate in row.candidates" :key="candidate.id">
                                        <option :value="candidate.id" x-text="candidate.name + ' (' + candidate.subscription_code + ')'"></option>
                                    </template>
                                </x-ui.input.select>
                            </div>
                        </template>
                        <p x-show="row.current" x-text="'Current · ' + describe(row.current)"></p>
                        <p x-show="row.proposed" x-text="'Proposed · ' + describe(row.proposed)"></p>
                        <p x-show="row.credentials_changed">PPPoE credentials will be set from the selected fields.</p>
                        <p x-show="row.session_notice" class="text-amber-700">This change may interrupt active sessions.</p>
                    </div>
                </template>
            </div>
            <x-ui.button type="button" variant="brand" x-bind:disabled="busy || !preview.preview_token || (run && ['pending', 'processing'].includes(run.status))" @click="start()">Confirm sync</x-ui.button>
        </div>
    </template>

    <template x-if="run">
        <div class="space-y-2 text-sm">
            <p role="status" x-text="run.status.replaceAll('_', ' ') + ' · ' + run.outcomes.length + ' of ' + run.total + ' processed'"></p>
            <ul class="space-y-2">
                <template x-for="row in run.outcomes" :key="row.index">
                    <li>
                        <span class="font-medium" x-text="studentLabel(row.index)"></span>
                        <span x-text="row.message"></span>
                        <a class="text-blue-700 underline" x-show="row.customer_url" :href="row.customer_url">Customer</a>
                        <a class="text-blue-700 underline" x-show="row.subscription_url" :href="row.subscription_url">Subscription</a>
                    </li>
                </template>
            </ul>
            <x-ui.button type="button" x-show="pollFailed" @click="error = ''; poll()">Refresh progress</x-ui.button>
        </div>
    </template>
</section>

@push('scripts')
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    function golestanSync(searchToken, pools, count) {
        const labels = @js(array_map(fn (array $row): string => trim(($row['STDNO'] ?? '').' · '.($row['firstname'] ?? '').' '.($row['name'] ?? '')), $result['records']));
        const plans = @js($plans);
        const routers = @js($routers);

        return {
            credentialFields: @js($defaultCredentialFields),
            selected: [], choices: {}, addresses: {}, plan: '', router: '', pool: '', management: '',
            preview: null, run: null, statusUrl: null, error: '', busy: false, pollFailed: false,
            selectAll() {
                this.selected = Array.from({ length: count }, (_, index) => String(index));
                this.preview = null;
            },
            filterPools(select, router) {
                for (const option of select.options) {
                    if (!option.value) continue;
                    const pool = pools.find(pool => String(pool.id) === option.value);
                    const compatible = router && (pool.all || pool.routers.some(id => String(id) === router));
                    option.hidden = !compatible;
                    option.disabled = !compatible;
                }
            },
            studentLabel(index) {
                return labels[index];
            },
            describe(settings) {
                if (!settings) return '';
                return [
                    plans[settings.plan_id] || (settings.plan_id ? 'Plan #' + settings.plan_id : null),
                    routers[settings.router_id] || (settings.router_id ? 'Router #' + settings.router_id : null),
                    pools.find(pool => pool.id === settings.ip_pool_id)?.name || (settings.ip_pool_id ? 'Pool #' + settings.ip_pool_id : null),
                    settings.ip_management === 'router' ? 'Router Managed' : null,
                    settings.ip_address,
                ].filter(Boolean).join(' · ');
            },
            selectedValues(values) {
                return Object.fromEntries(this.selected.filter(index => values[index] !== undefined && values[index] !== '').map(index => [index, values[index]]));
            },
            async request(url, method = 'GET', data = null) {
                const headers = { 'Accept': 'application/json' };
                if (method === 'POST') {
                    headers['Content-Type'] = 'application/json';
                    headers['X-CSRF-TOKEN'] = @js(csrf_token());
                }
                const response = await fetch(url, { method, headers, body: data === null ? null : JSON.stringify(data) });
                const result = await response.json();
                if (!response.ok) throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || 'The sync request failed.');
                return result;
            },
            async loadPreview() {
                this.busy = true;
                this.error = '';
                try {
                    this.preview = await this.request(@js(route('golestan.sync.preview')), 'POST', {
                        search_token: searchToken, rows: this.selected, plan_id: this.plan, router_id: this.router,
                        ip_management: this.management, ip_pool_id: this.pool, subscriptions: this.selectedValues(this.choices),
                        addresses: this.selectedValues(this.addresses), credential_fields: this.credentialFields,
                    });
                    this.error = this.preview.error || '';
                } catch (error) {
                    this.error = error.message;
                    this.preview = null;
                } finally {
                    this.busy = false;
                }
            },
            async start() {
                this.busy = true;
                this.error = '';
                try {
                    const response = await this.request(@js(route('golestan.sync.start')), 'POST', { preview_token: this.preview.preview_token });
                    this.statusUrl = response.status_url;
                    this.run = { status: 'pending', total: this.preview.rows.length, outcomes: [] };
                    this.preview = null;
                    await this.poll();
                } catch (error) {
                    this.error = error.message;
                } finally {
                    this.busy = false;
                }
            },
            async poll() {
                this.pollFailed = false;
                try {
                    this.run = await this.request(this.statusUrl);
                    if (['pending', 'processing'].includes(this.run.status)) setTimeout(() => this.poll(), 2000);
                } catch (error) {
                    this.error = error.message;
                    this.pollFailed = true;
                }
            },
        };
    }
</script>
@endpush
