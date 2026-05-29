@extends('layouts.admin')

@section('title', 'New Support Ticket')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-950">New Support Ticket</h1>
        <p class="text-sm text-slate-600">Create a customer-visible ticket on behalf of a customer.</p>
    </div>

    <form method="POST" action="{{ route('support.tickets.store') }}" enctype="multipart/form-data" class="rounded-xl border border-slate-900/10 bg-white p-6 shadow-sm" x-data="supportTicketCreate(@js($subscriptionsByCustomer), @js((string) old('customer_id')), @js((string) old('subscription_id')))">
        @csrf
        <x-form.validation-summary />

        <div class="grid gap-4 md:grid-cols-2">
            <div class="mb-4">
                <label for="customer_id" class="block text-sm font-medium text-slate-700">Customer <span class="text-red-500">*</span></label>
                <select id="customer_id" name="customer_id" x-model="customerId" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-slate-950 shadow-sm focus:border-transparent focus:ring-2 focus:ring-emerald-600 sm:text-sm">
                    <option value="">Select customer</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}{{ $customer->email ? ' - '.$customer->email : '' }}</option>
                    @endforeach
                </select>
                @error('customer_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <x-input.select id="ticket_team_id" name="ticket_team_id" label="Team" :options="$teams->pluck('name', 'id')" placeholder="Select team" required />
            <x-input.select id="priority" name="priority" label="Priority" :value="old('priority', 'normal')" :options="['low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent']" required />
            <div class="mb-4" x-show="customerId" x-cloak>
                <label for="subscription_id" class="block text-sm font-medium text-slate-700">Related service</label>
                <select id="subscription_id" name="subscription_id" x-model="subscriptionId" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-slate-950 shadow-sm focus:border-transparent focus:ring-2 focus:ring-emerald-600 sm:text-sm">
                    <option value="">No related service</option>
                    <template x-for="subscription in subscriptionsForCustomer" :key="subscription.id">
                        <option :value="subscription.id" x-text="subscription.label"></option>
                    </template>
                </select>
                <p class="mt-1 text-xs text-slate-500" x-show="subscriptionsForCustomer.length === 0">This customer has no subscriptions.</p>
                @error('subscription_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <x-input.text id="subject" name="subject" label="Subject" required />
        <x-input.textarea id="message" name="message" label="Opening message" rows="7" required />

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
    function supportTicketCreate(subscriptionsByCustomer, initialCustomerId, initialSubscriptionId) {
        return {
            customerId: initialCustomerId || '',
            subscriptionId: initialSubscriptionId || '',
            get subscriptionsForCustomer() {
                return subscriptionsByCustomer[this.customerId] || [];
            },
            init() {
                this.$watch('customerId', () => {
                    if (! this.subscriptionsForCustomer.some((subscription) => String(subscription.id) === String(this.subscriptionId))) {
                        this.subscriptionId = '';
                    }
                });
            },
        };
    }
</script>
@endpush
@endsection
