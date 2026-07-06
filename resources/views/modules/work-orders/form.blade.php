@php($editing = isset($workOrder))
<form method="POST" action="{{ $editing ? route('work-orders.update', $workOrder) : route('work-orders.store') }}" class="space-y-6">
    @csrf
    @if($editing) @method('PUT') @endif
    <x-form.validation-summary />
    @if(!$editing && request('ticket_id'))
        <input type="hidden" name="source_ticket_id" value="{{ request('ticket_id') }}">
    @endif

    <section class="border border-slate-900/10 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-bold text-slate-950">Customer and scope</h2>
        <div class="mt-5 grid gap-5 md:grid-cols-2">
            <x-input.select id="customer_id" name="customer_id" label="Customer" :value="old('customer_id', $workOrder->customer_id ?? request('customer_id'))" :options="$customers->mapWithKeys(fn($customer) => [$customer->id => $customer->full_name])->all()" required />
            <x-input.select id="subscription_id" name="subscription_id" label="Existing subscription (when applicable)" :value="old('subscription_id', $workOrder->subscription_id ?? null)" :options="$subscriptions->mapWithKeys(fn($subscription) => [$subscription->id => ($subscription->pppoe_username ?: $subscription->subscription_code).' — '.$subscription->customer?->full_name])->all()" />
            <x-input.select id="type" name="type" label="Work type" :value="old('type', isset($workOrder) ? $workOrder->type->value : 'new_installation')" :options="collect($types)->mapWithKeys(fn($type) => [$type->value => str($type->value)->replace('_', ' ')->headline()->toString()])->all()" required />
            <x-input.select id="priority" name="priority" label="Priority" :value="old('priority', isset($workOrder) ? $workOrder->priority->value : 'normal')" :options="collect($priorities)->mapWithKeys(fn($priority) => [$priority->value => ucfirst($priority->value)])->all()" required />
            <div class="md:col-span-2"><x-input.text id="title" name="title" label="Title" :value="old('title', $workOrder->title ?? '')" required /></div>
            <div class="md:col-span-2"><x-input.textarea id="description" name="description" label="Scope and instructions" :value="old('description', $workOrder->description ?? '')" rows="5" /></div>
        </div>
    </section>

    <section class="border border-slate-900/10 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-bold text-slate-950">Service location</h2>
        <div class="mt-5 grid gap-5 md:grid-cols-2">
            <div class="md:col-span-2"><x-input.text id="service_address_line1" name="service_address_line1" label="Address" :value="old('service_address_line1', $workOrder->service_address_line1 ?? '')" required /></div>
            <x-input.text id="service_address_line2" name="service_address_line2" label="Address details" :value="old('service_address_line2', $workOrder->service_address_line2 ?? '')" />
            <x-input.text id="service_city" name="service_city" label="City" :value="old('service_city', $workOrder->service_city ?? '')" />
            <x-input.text id="contact_name" name="contact_name" label="On-site contact" :value="old('contact_name', $workOrder->contact_name ?? '')" />
            <x-input.tel id="contact_phone" name="contact_phone" label="Contact phone" :value="old('contact_phone', $workOrder->contact_phone ?? '')" />
        </div>
    </section>

    <div class="flex justify-end gap-3">
        <a href="{{ $editing ? route('work-orders.show', $workOrder) : route('work-orders.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</a>
        <button class="rounded-lg bg-[#0d2f35] px-5 py-2.5 text-sm font-semibold text-white">{{ $editing ? 'Save changes' : 'Create work order' }}</button>
    </div>
</form>
