@php($editing = isset($workOrder))
<form
    method="POST"
    action="{{ $editing ? route('work-orders.update', $workOrder) : route('work-orders.store') }}"
    class="space-y-6"
    x-data="{
        selectedCustomerId: @js((string) old('customer_id', $workOrder->customer_id ?? request('customer_id', ''))),
        selectedSubscriptionId: @js((string) old('subscription_id', $workOrder->subscription_id ?? '')),
        subscriptionsByCustomer: @js($customers->mapWithKeys(fn($customer) => [(string) $customer->id => $customer->subscriptions->map(fn($subscription) => ['id' => (string) $subscription->id, 'label' => ($subscription->pppoe_username ?: $subscription->subscription_code).' — '.$customer->full_name])->values()])),
        get availableSubscriptions() { return this.subscriptionsByCustomer[this.selectedCustomerId] || []; },
        changeCustomer() {
            if (!this.availableSubscriptions.some(subscription => subscription.id === this.selectedSubscriptionId)) {
                this.selectedSubscriptionId = '';
            }
        },
        selectedTeamId: @js((string) old('assigned_team_id', $workOrder->assigned_team_id ?? '')),
        selectedUserId: @js((string) old('assigned_user_id', $workOrder->assigned_user_id ?? '')),
        teamMembers: @js($teams->mapWithKeys(fn($team) => [(string) $team->id => $team->users->map(fn($user) => ['id' => (string) $user->id, 'name' => $user->name])->values()])),
        get availableMembers() { return this.teamMembers[this.selectedTeamId] || []; },
        changeTeam() {
            if (!this.availableMembers.some(member => member.id === this.selectedUserId)) {
                this.selectedUserId = '';
            }
        }
    }"
>
    @csrf
    @if($editing) @method('PUT') @endif
    <x-form.validation-summary />
    @if(!$editing && request('ticket_id'))
        <input type="hidden" name="source_ticket_id" value="{{ request('ticket_id') }}">
    @endif

    <section class="border border-slate-900/10 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-bold text-slate-950">Customer and scope</h2>
        <div class="mt-5 grid gap-5 md:grid-cols-2">
            <x-input.select id="customer_id" name="customer_id" label="Customer" x-model="selectedCustomerId" x-change="changeCustomer" :value="old('customer_id', $workOrder->customer_id ?? request('customer_id'))" :options="$customers->mapWithKeys(fn($customer) => [$customer->id => $customer->full_name])->all()" required />
            <div class="mb-4">
                <label for="subscription_id" class="block text-sm font-medium text-slate-700">Existing subscription (when applicable)</label>
                <select id="subscription_id" name="subscription_id" x-model="selectedSubscriptionId" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-slate-950 shadow-sm focus:border-transparent focus:ring-2 focus:ring-emerald-600 sm:text-sm @error('subscription_id') border-red-500 @enderror">
                    <option value="">Select a customer first</option>
                    <template x-if="selectedCustomerId && availableSubscriptions.length === 0">
                        <option value="">No subscriptions for this customer</option>
                    </template>
                    <template x-for="subscription in availableSubscriptions" :key="subscription.id">
                        <option :value="subscription.id" x-text="subscription.label"></option>
                    </template>
                </select>
                @error('subscription_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <x-input.select id="type" name="type" label="Work type" :value="old('type', isset($workOrder) ? $workOrder->type->value : 'new_installation')" :options="collect($types)->mapWithKeys(fn($type) => [$type->value => str($type->value)->replace('_', ' ')->headline()->toString()])->all()" required />
            <x-input.select id="priority" name="priority" label="Priority" :value="old('priority', isset($workOrder) ? $workOrder->priority->value : 'normal')" :options="collect($priorities)->mapWithKeys(fn($priority) => [$priority->value => ucfirst($priority->value)])->all()" required />
            <div class="md:col-span-2"><x-input.text id="title" name="title" label="Title" :value="old('title', $workOrder->title ?? '')" required /></div>
            <div class="md:col-span-2"><x-input.textarea id="description" name="description" label="Scope and instructions" :value="old('description', $workOrder->description ?? '')" rows="5" /></div>
        </div>
    </section>

    <section class="border border-slate-900/10 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-bold text-slate-950">Assignment</h2>
        <p class="mt-1 text-sm text-slate-600">Place the work order in the responsible team queue and optionally assign a specific member.</p>
        <div class="mt-5 grid gap-5 md:grid-cols-2">
            <div class="mb-4">
                <label for="assigned_team_id" class="block text-sm font-medium text-slate-700">Responsible team <span class="text-red-500">*</span></label>
                <select id="assigned_team_id" name="assigned_team_id" x-model="selectedTeamId" @change="changeTeam" required class="mt-1 block w-full rounded-lg border bg-white px-3 py-3 text-slate-950 shadow-sm focus:border-transparent focus:ring-2 focus:ring-emerald-600 sm:text-sm @error('assigned_team_id') border-red-500 @else border-slate-300 @enderror">
                    <option value="">Select team</option>
                    @foreach($teams as $team)
                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                    @endforeach
                </select>
                @error('assigned_team_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="mb-4">
                <label for="assigned_user_id" class="block text-sm font-medium text-slate-700">Assigned member</label>
                <select id="assigned_user_id" name="assigned_user_id" x-model="selectedUserId" :disabled="!selectedTeamId" class="mt-1 block w-full rounded-lg border bg-white px-3 py-3 text-slate-950 shadow-sm focus:border-transparent focus:ring-2 focus:ring-emerald-600 disabled:bg-slate-100 disabled:text-slate-400 sm:text-sm @error('assigned_user_id') border-red-500 @else border-slate-300 @enderror">
                    <option value="">Leave in team queue</option>
                    <template x-for="member in availableMembers" :key="member.id">
                        <option :value="member.id" x-text="member.name"></option>
                    </template>
                </select>
                <p class="mt-1 text-xs text-slate-500">Only active members of the selected team are available.</p>
                @error('assigned_user_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
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
