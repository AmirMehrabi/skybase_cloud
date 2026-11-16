@extends('layouts.admin')
@section('title', 'SBUK ERP synchronization')
@section('content')
<div class="mx-auto max-w-5xl space-y-6" x-data="sbukSyncPage(@js($activeRun ? route('integrations.sbuk-erp.status', $activeRun) : null))">
    <x-ui.card title="SBUK ERP — Personnel synchronization" subtitle="Manual import from Shahid Bahonar University of Kerman">
        <div class="mb-5 rounded-lg border p-4 text-sm {{ $enabled && $configured ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-amber-200 bg-amber-50 text-amber-900' }}">
            ERP connection: {{ $enabled && $configured ? 'Configured' : 'Not configured' }}
            @unless($enabled && $configured)<p class="mt-1">Set the server-side SBUK ERP environment values before fetching a preview.</p>@endunless
        </div>
        <form class="space-y-5" @submit.prevent="preview($event.currentTarget)">
            <div class="grid gap-4 md:grid-cols-3">
                <x-ui.input.select name="plan_id" label="Service" :options="$plans->pluck('name', 'id')->all()" required />
                <x-ui.input.select name="router_id" label="Router" :options="$routers->pluck('name', 'id')->all()" required />
                <x-ui.input.select name="ip_pool_id" label="IPAM pool" :options="$ipPools->mapWithKeys(fn ($pool) => [$pool->id => $pool->name.' · '.$pool->available_ips.' available'])->all()" required />
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <x-ui.input.select name="pppoe_username_source" label="PPPoE username source" :options="['mobile' => 'Mobile', 'national_id' => 'National ID']" value="mobile" required x-bind:disabled="loading || activeRun" @change="clearPreview()" />
                <x-ui.input.select name="pppoe_password_source" label="PPPoE password source" :options="['mobile' => 'Mobile', 'national_id' => 'National ID']" value="national_id" required x-bind:disabled="loading || activeRun" @change="clearPreview()" />
            </div>
            <p class="text-sm text-gray-500">Imports create missing customer accounts and non-billable subscriptions. New subscriptions remain pending until an administrator completes the connection profile and activation. Existing subscriptions are preserved; missing ERP records never cause removals.</p>
            <div class="flex flex-wrap gap-3">
                <x-ui.button type="submit" variant="brand" x-bind:disabled="loading || activeRun"><span x-text="loading ? 'Fetching preview…' : 'Fetch & preview'">Fetch & preview</span></x-ui.button>
                <x-ui.button type="button" variant="secondary" x-show="previewData" x-cloak x-bind:disabled="loading || activeRun" @click="start()">Start synchronization</x-ui.button>
            </div>
        </form>
        <p class="mt-4 text-sm text-red-700" role="alert" x-text="error" x-show="error" x-cloak></p>
    </x-ui.card>

    <x-ui.card title="Preview">
        <p class="text-sm text-gray-500" x-show="!previewData">Fetch ERP records to calculate expected changes. No records are changed during preview.</p>
        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4" x-show="previewData" x-cloak>
            <template x-for="item in summaryItems" :key="item.key">
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <dt class="text-sm text-gray-500" x-text="item.label"></dt>
                    <dd class="mt-1 text-2xl font-semibold text-gray-900" x-text="previewData?.[item.key] ?? '—'"></dd>
                </div>
            </template>
        </dl>
        <p class="mt-4 text-sm text-gray-600" x-show="previewData" x-cloak>IP allocations needed: <strong x-text="previewData?.estimated_ip_allocations ?? 0"></strong> · Available in pool: <strong x-text="previewData?.available_ip_addresses ?? 'Unknown'"></strong></p>
        <template x-if="previewData">
            <details class="mt-4" @toggle="recordsOpen = $event.currentTarget.open">
                <summary class="cursor-pointer text-sm font-medium">ERP records (<span x-text="previewRows.length"></span>)</summary>
                <template x-if="recordsOpen">
                    <div class="mt-2 max-h-96 overflow-auto">
                        <p class="text-sm text-gray-500" x-show="!previewRows.length">No ERP records were returned.</p>
                        <table class="w-full text-left text-sm" x-show="previewRows.length">
                            <thead class="border-b text-gray-500">
                                <tr>
                                    <th scope="col" class="p-2">First name</th>
                                    <th scope="col" class="p-2">Last name</th>
                                    <th scope="col" class="p-2">Mobile</th>
                                    <th scope="col" class="p-2">National ID</th>
                                    <th scope="col" class="p-2">ERP index</th>
                                    <th scope="col" class="p-2">Validation issue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(row, index) in previewRows" :key="index">
                                    <tr class="border-b border-gray-100">
                                        <td class="p-2" x-text="row.first_name || '—'"></td>
                                        <td class="p-2" x-text="row.last_name || '—'"></td>
                                        <td class="p-2" x-text="row.mobile || '—'"></td>
                                        <td class="p-2" x-text="row.national_id || '—'"></td>
                                        <td class="p-2" x-text="row.external_index || '—'"></td>
                                        <td class="p-2" x-text="row.error || '—'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>
            </details>
        </template>
    </x-ui.card>

    <x-ui.card title="Synchronization progress" x-show="runData" x-cloak>
        <div class="space-y-2 text-sm text-gray-700">
            <p>Status: <strong x-text="runData?.status"></strong></p>
            <p>Processed: <strong x-text="`${runData?.processed_records ?? 0} / ${runData?.total_records ?? 0}`"></strong></p>
            <p>Customers created / matched: <strong x-text="`${runData?.created_customers ?? 0} / ${runData?.matched_customers ?? 0}`"></strong></p>
            <p>Subscriptions created / skipped: <strong x-text="`${runData?.created_subscriptions ?? 0} / ${runData?.skipped_subscriptions ?? 0}`"></strong></p>
            <p>Failed records: <strong x-text="runData?.failed_records ?? 0"></strong></p>
            <p class="text-amber-800" x-show="runData?.error_summary" x-text="runData?.error_summary"></p>
            <div class="overflow-x-auto" x-show="runData?.records?.length">
                <h4 class="mb-2 font-medium">Record outcomes</h4>
                <table class="w-full text-left text-xs"><thead class="border-b text-gray-500"><tr><th class="py-2 pr-4">ERP row</th><th class="py-2 pr-4">Outcome</th><th class="py-2">Details</th></tr></thead>
                    <tbody><template x-for="record in runData?.records ?? []" :key="record.row_number"><tr class="border-b border-gray-100"><td class="py-2 pr-4" x-text="record.row_number"></td><td class="py-2 pr-4" x-text="record.outcome.replaceAll('_', ' ')"></td><td class="py-2" x-text="record.detail ?? '—'"></td></tr></template></tbody>
                </table>
            </div>
        </div>
    </x-ui.card>

    <x-ui.card title="Recent runs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b text-gray-500"><tr><th class="py-2 pr-4">Started</th><th class="py-2 pr-4">Status</th><th class="py-2 pr-4">Records</th><th class="py-2 pr-4">Created subscriptions</th><th class="py-2">Errors</th></tr></thead>
                <tbody>
                    @forelse($runs as $run)
                        <tr class="border-b border-gray-100"><td class="py-3 pr-4">{{ $run->created_at?->timezone(tenant()->timezone ?? 'UTC')->format('Y-m-d H:i') }}</td><td class="py-3 pr-4">{{ str($run->status)->headline() }}</td><td class="py-3 pr-4">{{ $run->processed_records }} / {{ $run->total_records }}</td><td class="py-3 pr-4">{{ $run->created_subscriptions }}</td><td class="py-3">{{ $run->failed_records }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="py-5 text-gray-500">No synchronization runs yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
<script>
function sbukSyncPage(initialStatusUrl = null) {
    return {
        loading: false, error: '', previewData: null, previewToken: null, activeRun: Boolean(initialStatusUrl), runData: null, poller: null, statusUrl: initialStatusUrl,
        previewRows: [], recordsOpen: false,
        summaryItems: [
            { key: 'total_records', label: 'ERP records' }, { key: 'valid_records', label: 'Valid records' },
            { key: 'new_users', label: 'New customers' }, { key: 'existing_users', label: 'Existing customers' },
            { key: 'new_subscriptions', label: 'New subscriptions' }, { key: 'already_subscribed', label: 'Already subscribed' },
            { key: 'invalid_or_conflicting', label: 'Invalid / conflicting' },
        ],
        async send(url, body) {
            const response = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: JSON.stringify(body) });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'The request could not be completed.');
            return result;
        },
        init() {
            if (this.statusUrl) { this.poll(); this.poller = setInterval(() => this.poll(), 3000); }
        },
        clearPreview() {
            this.error = ''; this.previewData = null; this.previewToken = null;
            this.previewRows = []; this.recordsOpen = false;
        },
        async preview(form) {
            this.clearPreview();
            try {
                const data = Object.fromEntries(new FormData(form).entries());
                this.loading = true;
                const result = await this.send(@js(route('integrations.sbuk-erp.preview')), data);
                this.previewRows = result.rows;
                this.previewData = result.summary; this.previewToken = result.preview_token;
            } catch (error) { this.error = error.message; }
            finally { this.loading = false; }
        },
        async start() {
            this.loading = true; this.error = '';
            try {
                const result = await this.send(@js(route('integrations.sbuk-erp.start')), { preview_token: this.previewToken });
                this.activeRun = true; this.statusUrl = result.status_url; this.poll(); this.poller = setInterval(() => this.poll(), 3000);
            } catch (error) { this.error = error.message; }
            finally { this.loading = false; }
        },
        async poll() {
            try {
                const response = await fetch(this.statusUrl, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('Unable to read synchronization progress.');
                this.runData = await response.json();
                if (['completed', 'completed_with_errors', 'failed'].includes(this.runData.status)) { clearInterval(this.poller); this.activeRun = false; }
            } catch (error) { this.error = error.message; clearInterval(this.poller); }
        },
        destroy() { clearInterval(this.poller); },
    };
}
</script>
@endsection
