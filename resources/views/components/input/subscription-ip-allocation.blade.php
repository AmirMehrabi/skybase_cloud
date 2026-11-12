@props(['originalPoolId' => '', 'ownedAddresses' => []])

<div x-data="subscriptionNetworkFields({ form, pools: ipPools, originalPoolId: @js((string) $originalPoolId), ownedAddresses: @js($ownedAddresses) })" class="mt-4" x-show="multipleDevices" x-cloak>
    <fieldset :disabled="!multipleDevices" class="min-w-0">
        <legend class="text-sm font-semibold text-gray-900">IP Allocation</legend>
        <p class="mt-1 text-xs leading-5 text-gray-500">Each connection receives its own IP. No MAC address or device registration is needed.</p>
        <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
            @foreach(['dynamic_pool' => ['Automatic from pool', 'Assign an available IP when a device connects.'], 'reserved_set' => ['Reserved subscription IPs', 'Keep a set of addresses exclusively for this subscription.']] as $mode => [$label, $help])
                <label class="cursor-pointer">
                    <input type="radio" name="ip_allocation" value="{{ $mode }}" x-model="networkForm.ip_allocation" @change="chooseAllocation('{{ $mode }}')" class="peer sr-only">
                    <div class="h-full rounded-xl border-2 border-gray-200 bg-white p-4 transition hover:border-gray-300 peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-focus-visible:ring-2 peer-focus-visible:ring-blue-500 peer-focus-visible:ring-offset-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-semibold text-gray-900">{{ $label }}</span>
                            @if($mode === 'dynamic_pool')<span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">Recommended</span>@endif
                        </div>
                        <p class="mt-1 text-xs leading-5 text-gray-500">{{ $help }}</p>
                    </div>
                </label>
            @endforeach
        </div>
        <fieldset x-show="networkForm.ip_allocation === 'reserved_set'" :disabled="!multipleDevices || networkForm.ip_allocation !== 'reserved_set'" class="mt-4 min-w-0 rounded-xl border border-gray-200 bg-white p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-900">Reserved IP addresses</p>
                    <p class="mt-1 text-xs text-gray-500"><span x-text="reservedCount"></span> of <span x-text="networkForm.concurrent_devices"></span> selected. Any device may receive any free address in this set.</p>
                </div>
                <button type="button" @click="fillRemainingAddresses()" :disabled="!networkPool" class="inline-flex items-center justify-center rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100 disabled:cursor-not-allowed disabled:opacity-50">Fill remaining automatically</button>
            </div>
            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <template x-for="index in reservationSlots" :key="index">
                    <div>
                        <label :for="`reserved_ip_${index}`" class="mb-1 block text-xs font-medium text-gray-700" x-text="`Address ${index + 1}`"></label>
                        <select :id="`reserved_ip_${index}`" name="reserved_ip_addresses[]" x-model="networkForm.reserved_ip_addresses[index]" required class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 font-mono text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select IP address</option>
                            <template x-for="address in addressesForSlot(index)" :key="address.ip_address">
                                <option :value="address.ip_address" :selected="address.ip_address === networkForm.reserved_ip_addresses[index]" x-text="address.ip_address"></option>
                            </template>
                        </select>
                    </div>
                </template>
            </div>
            <p x-show="networkPool && networkAddresses.length < Number(networkForm.concurrent_devices)" class="mt-3 text-xs text-amber-700" role="status">This pool does not have enough available addresses for the selected device limit.</p>
        </fieldset>
        @foreach(['ip_allocation', 'reserved_ip_addresses', 'reserved_ip_addresses.*'] as $field)
            @error($field)<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        @endforeach
        <div class="mt-4 rounded-lg border border-blue-100 bg-blue-50 px-3 py-3 text-sm text-blue-900" aria-live="polite">
            <p class="font-medium" x-text="networkSummary"></p>
            <p class="mt-1 text-xs leading-5 text-blue-700">When the limit is reached, new logins are declined. Devices already connected stay online.</p>
        </div>
        @unless(\App\Services\SubscriptionIpAllocationService::isConfiguredForTenant((string) tenant_id()))
            <p class="mt-3 text-xs leading-5 text-amber-700">Multiple-device access needs activation on your authentication server. You can save these settings, but logins will remain disabled until it is connected.</p>
        @endunless
    </fieldset>
</div>
