@extends('layouts.admin')

@section('title', 'New Support Ticket')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-950">New Support Ticket</h1>
        <p class="text-sm text-slate-600">Create a customer-visible ticket on behalf of a customer.</p>
    </div>

    <form method="POST" action="{{ route('support.tickets.store') }}" enctype="multipart/form-data" class="rounded-xl border border-slate-900/10 bg-white p-6 shadow-sm" x-data="supportTicketCreate(@js($subscriptions), @js((string) old('subscription_id', request('subscription_id'))))">
        @csrf
        <x-form.validation-summary />

        <div class="grid gap-4 md:grid-cols-2">
            <div class="relative mb-4" @click.outside="open = false">
                <label for="subscription_search" class="block text-sm font-medium text-slate-700">Subscription <span class="text-red-500">*</span></label>
                <input type="hidden" name="subscription_id" :value="selectedSubscriptionId">
                <input
                    id="subscription_search"
                    type="text"
                    x-model="search"
                    @focus="open = true"
                    @input="open = true; selectedSubscriptionId = ''"
                    @keydown.escape="open = false"
                    @keydown.down.prevent="focusFirstResult()"
                    role="combobox"
                    aria-controls="subscription_results"
                    :aria-expanded="open"
                    autocomplete="off"
                    placeholder="Search by customer name or PPPoE username"
                    required
                    class="mt-1 block w-full rounded-lg border bg-white px-3 py-3 text-slate-950 shadow-sm focus:border-transparent focus:ring-2 focus:ring-emerald-600 sm:text-sm @error('subscription_id') border-red-500 @else border-slate-300 @enderror"
                >
                <div id="subscription_results" x-show="open" x-cloak class="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg">
                    <template x-for="subscription in filteredSubscriptions" :key="subscription.id">
                        <button
                            type="button"
                            @click="selectSubscription(subscription)"
                            class="block w-full border-b border-slate-100 px-4 py-3 text-left last:border-0 hover:bg-slate-50 focus:bg-slate-50 focus:outline-none"
                        >
                            <span class="block text-sm font-semibold text-slate-900" x-text="subscription.pppoe_username || subscription.label"></span>
                            <span class="block text-xs text-slate-500" x-text="subscription.customer_name"></span>
                        </button>
                    </template>
                    <p x-show="filteredSubscriptions.length === 0" class="px-4 py-3 text-sm text-slate-500">No matching subscriptions found.</p>
                </div>
                <p class="mt-1 text-xs text-slate-500" x-show="selectedCustomerName">
                    Customer: <span class="font-medium text-slate-700" x-text="selectedCustomerName"></span>
                </p>
                @error('subscription_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <x-input.select id="ticket_team_id" name="ticket_team_id" label="Team" :options="$teams->pluck('name', 'id')" placeholder="Select team" required />
            <x-input.select id="priority" name="priority" label="Priority" :value="old('priority', 'normal')" :options="['low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent']" required />
        </div>

        <x-input.text id="subject" name="subject" label="Subject" required />
        <x-tickets.markdown-composer id="message" name="message" label="Opening message" rows="7" required />

        <div class="mb-4">
            <label class="block text-sm font-medium text-slate-700" for="attachments">Attachments</label>
            <input id="attachments" name="attachments[]" type="file" multiple class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <p class="mt-1 text-xs text-slate-500">Up to 5 files, 10 MB each.</p>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('support.tickets.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</a>
            <button class="rounded-lg bg-[#0d2f35] px-4 py-2 text-sm font-semibold text-white">Create ticket</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function supportTicketCreate(subscriptions, initialSubscriptionId) {
        return {
            open: false,
            search: '',
            selectedSubscriptionId: initialSubscriptionId || '',
            selectedCustomerName: '',
            get filteredSubscriptions() {
                const search = this.search.trim().toLocaleLowerCase();

                if (search === '') {
                    return subscriptions;
                }

                return subscriptions.filter((subscription) => [
                    subscription.customer_name,
                    subscription.pppoe_username,
                    subscription.label,
                ].some((value) => String(value || '').toLocaleLowerCase().includes(search)));
            },
            init() {
                const subscription = subscriptions.find((item) => String(item.id) === String(this.selectedSubscriptionId));

                if (subscription) {
                    this.selectSubscription(subscription);
                }
            },
            selectSubscription(subscription) {
                this.selectedSubscriptionId = String(subscription.id);
                this.selectedCustomerName = subscription.customer_name;
                this.search = subscription.pppoe_username || subscription.label;
                this.open = false;
            },
            focusFirstResult() {
                this.open = true;
                this.$nextTick(() => document.querySelector('#subscription_results button')?.focus());
            },
        };
    }
</script>
@endpush
@endsection
