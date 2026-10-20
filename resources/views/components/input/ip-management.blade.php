@props(['value' => null, 'xModel' => 'form.ip_management', 'xChange' => ''])

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <!-- Router Managed -->
    <label class="relative cursor-pointer">
        <input type="radio" name="ip_management" value="router" x-model="{{ $xModel }}" @change="{{ $xChange }}" @checked($value === 'router') class="peer sr-only">
        <div class="p-4 border-2 rounded-xl transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:border-gray-300">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"></path>
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-900">Router Managed</h4>
                    <p class="text-xs text-gray-500 mt-1">Router/NAS handles IP assignment via RADIUS or DHCP. No tracking in SkyBase.</p>
                </div>
            </div>
        </div>
    </label>

    <!-- System Managed -->
    <label class="relative cursor-pointer">
        <input type="radio" name="ip_management" value="system" x-model="{{ $xModel }}" @change="{{ $xChange }}" @checked($value === 'system') class="peer sr-only">
        <div class="p-4 border-2 rounded-xl transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:border-gray-300">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-900">System Managed</h4>
                    <p class="text-xs text-gray-500 mt-1">SkyBase tracks and assigns IPs from IP pools. Full inventory management.</p>
                </div>
            </div>
        </div>
    </label>
</div>
