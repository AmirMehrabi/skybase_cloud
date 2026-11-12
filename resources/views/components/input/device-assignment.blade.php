@props(['customers', 'subscriptions', 'customerId' => null, 'subscriptionId' => null])
<div class="space-y-4" x-data="{ customer: @js((string) old('customer_id', $customerId)), subscription: @js((string) old('subscription_id', $subscriptionId)), subscriptions: @js($subscriptions) }">
    <div class="space-y-1.5"><label for="device-customer" class="block text-sm font-medium text-gray-700">Customer</label>
        <select name="customer_id" id="device-customer" x-model="customer" @change="subscription = ''" class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"><option value="">Unassigned</option>@foreach($customers as $id=>$label)<option value="{{ $id }}">{{ $label }}</option>@endforeach</select>
        @error('customer_id')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div class="space-y-1.5"><label for="device-subscription" class="block text-sm font-medium text-gray-700">Subscription (optional)</label>
        <select name="subscription_id" id="device-subscription" x-model="subscription" :disabled="!customer" class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-50"><option value="">No subscription</option><template x-for="item in subscriptions.filter(item => String(item.customer_id) === customer)" :key="item.id"><option :value="item.id" x-text="item.label"></option></template></select>
        <p class="text-xs text-gray-500">Only subscriptions belonging to the selected customer are shown.</p>
        @error('subscription_id')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>
