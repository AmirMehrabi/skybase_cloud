@extends('layouts.admin')

@section('title', 'New Support Ticket')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-950">New Support Ticket</h1>
        <p class="text-sm text-slate-600">Create a customer-visible ticket on behalf of a customer.</p>
    </div>

    <form method="POST" action="{{ route('support.tickets.store') }}" enctype="multipart/form-data" class="rounded-2xl border border-slate-900/10 bg-white p-6 shadow-sm" x-data="supportTicketCreate(@js($subscriptions), @js((string) old('subscription_id', request('subscription_id'))), @js($teamAgents))">
        @csrf
        <x-form.validation-summary />

        <section class="border-b border-slate-100 pb-6">
            <div class="mb-4 flex items-center gap-3"><span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#0d2f35] text-sm font-bold text-white">1</span><div><h2 class="font-semibold text-slate-950">Customer and service</h2><p class="text-xs text-slate-500">Select the service this conversation is about.</p></div></div>
            <div class="grid gap-4 md:grid-cols-2">
                <div class="relative" @click.outside="open = false">
                    <label for="subscription_search" class="block text-sm font-medium text-slate-700">Subscription <span class="text-red-500">*</span></label>
                    <input type="hidden" name="subscription_id" :value="selectedSubscriptionId">
                    <input id="subscription_search" type="text" x-model="search" @focus="open = true" @input="open = true; selectedSubscriptionId = ''" @keydown.escape="open = false" @keydown.down.prevent="focusFirstResult()" role="combobox" aria-controls="subscription_results" :aria-expanded="open" autocomplete="off" placeholder="Search by customer name or PPPoE username" required class="mt-1 block w-full rounded-lg border bg-white px-3 py-3 text-slate-950 shadow-sm focus:border-transparent focus:ring-2 focus:ring-emerald-600 sm:text-sm @error('subscription_id') border-red-500 @else border-slate-300 @enderror">
                    <div id="subscription_results" x-show="open" x-cloak class="absolute z-20 mt-1 max-h-72 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-xl">
                        <template x-for="subscription in filteredSubscriptions" :key="subscription.id">
                            <button type="button" @click="selectSubscription(subscription)" class="block w-full border-b border-slate-100 px-4 py-3 text-left last:border-0 hover:bg-slate-50 focus:bg-slate-50 focus:outline-none">
                                <span class="block text-sm font-semibold text-slate-900" x-text="subscription.pppoe_username || subscription.label"></span>
                                <span class="block text-xs text-slate-500" x-text="subscription.customer_name + ' · ' + (subscription.plan_name || 'Service')"></span>
                            </button>
                        </template>
                        <p x-show="filteredSubscriptions.length === 0" class="px-4 py-3 text-sm text-slate-500">No matching subscriptions found.</p>
                    </div>
                    <div class="mt-3 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600" x-show="selectedCustomerName" x-cloak><span class="font-semibold text-slate-700" x-text="selectedCustomerName"></span><span class="mx-1 text-slate-300">·</span><span x-text="selectedSubscriptionLabel"></span></div>
                    @error('subscription_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="ticket_team_id" class="block text-sm font-medium text-slate-700">Team <span class="text-red-500">*</span></label>
                    <select id="ticket_team_id" name="ticket_team_id" x-model="selectedTeamId" @change="selectedAssigneeId = ''" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-slate-950 shadow-sm focus:border-transparent focus:ring-2 focus:ring-emerald-600 sm:text-sm @error('ticket_team_id') border-red-500 @enderror">
                        <option value="">Select team</option>
                        @foreach($teams as $team)<option value="{{ $team->id }}" @selected(old('ticket_team_id') == $team->id)>{{ $team->name }}</option>@endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">The team receives ownership when no specific agent is selected.</p>
                    @error('ticket_team_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="border-b border-slate-100 py-6">
            <div class="mb-4 flex items-center gap-3"><span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#0d2f35] text-sm font-bold text-white">2</span><div><h2 class="font-semibold text-slate-950">Describe the issue</h2><p class="text-xs text-slate-500">Write the first message the customer will see.</p></div></div>
            <x-input.text id="subject" name="subject" label="Subject" required />
            <x-tickets.markdown-composer id="message" name="message" label="Opening message" hint="This message will be sent as a public reply to the customer." rows="7" required />
        </section>

        <section class="py-6">
            <div class="mb-4 flex items-center gap-3"><span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#0d2f35] text-sm font-bold text-white">3</span><div><h2 class="font-semibold text-slate-950">Route and add context</h2><p class="text-xs text-slate-500">Set urgency and give the team any helpful files.</p></div></div>
            <div class="grid gap-4 md:grid-cols-2">
                <div><x-input.select id="priority" name="priority" label="Priority" :value="old('priority', 'normal')" :options="['low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent']" required /><div class="-mt-2 text-xs text-slate-500">Use urgent only when service impact is immediate.</div></div>
                <div><label class="block text-sm font-medium text-slate-700" for="assigned_user_id">Assignee</label><select id="assigned_user_id" name="assigned_user_id" x-model="selectedAssigneeId" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-slate-950 shadow-sm focus:border-transparent focus:ring-2 focus:ring-emerald-600 sm:text-sm"><option value="">Automatic selection</option><template x-for="agent in availableAgents" :key="agent.id"><option :value="agent.id" x-text="agent.name"></option></template></select><p class="mt-1 text-xs text-slate-500">Leave automatic unless a specific agent should own the first response.</p>@error('assigned_user_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            </div>
            <div class="mt-4"><label class="block text-sm font-medium text-slate-700" for="attachments">Attachments</label><input id="attachments" name="attachments[]" type="file" multiple class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"><p class="mt-1 text-xs text-slate-500">Up to 5 files, 10 MB each.</p></div>
        </section>

        <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end"><a href="{{ route('support.tickets.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a><button class="rounded-lg bg-[#0d2f35] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#123f3d]">Create ticket</button></div>
    </form>
</div>

@push('scripts')
<script>
    function supportTicketCreate(subscriptions, initialSubscriptionId, teamAgents) {
        return {
            open: false,
            search: '',
            selectedSubscriptionId: initialSubscriptionId || '',
            selectedCustomerName: '',
            selectedSubscriptionLabel: '',
            selectedTeamId: '{{ old('ticket_team_id') }}',
            selectedAssigneeId: '{{ old('assigned_user_id') }}',
            teamAgents: teamAgents,
            get availableAgents() { return this.teamAgents[this.selectedTeamId] || []; },
            get filteredSubscriptions() {
                const search = this.search.trim().toLocaleLowerCase();
                if (search === '') return subscriptions;
                return subscriptions.filter((subscription) => [subscription.customer_name, subscription.pppoe_username, subscription.label, subscription.plan_name].some((value) => String(value || '').toLocaleLowerCase().includes(search)));
            },
            init() {
                const subscription = subscriptions.find((item) => String(item.id) === String(this.selectedSubscriptionId));
                if (subscription) this.selectSubscription(subscription);
            },
            selectSubscription(subscription) {
                this.selectedSubscriptionId = String(subscription.id);
                this.selectedCustomerName = subscription.customer_name;
                this.selectedSubscriptionLabel = subscription.pppoe_username || subscription.label;
                this.search = subscription.pppoe_username || subscription.label;
                this.open = false;
            },
            focusFirstResult() { this.open = true; this.$nextTick(() => document.querySelector('#subscription_results button')?.focus()); },
        };
    }
</script>
@endpush
@endsection
