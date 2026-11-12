@props(['legacy' => false])

<div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <label for="concurrent_devices" class="block text-sm font-semibold text-gray-900">Concurrent Devices</label>
            <p id="concurrent_devices_help" class="mt-1 text-xs leading-5 text-gray-500">Devices that can connect at the same time using these subscription credentials. Multiple devices are supported for Hotspot access.</p>
            @if($legacy)
                <p class="mt-1 text-xs text-gray-500">Existing router limits are preserved until you change this setting.</p>
            @endif
        </div>
        <input type="number" id="concurrent_devices" name="concurrent_devices" x-model.number="form.concurrent_devices" min="1" max="256" step="1" required aria-describedby="concurrent_devices_help" class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:w-28">
    </div>
    @error('concurrent_devices')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    <p x-show="typeof validationError === 'function' && validationError('concurrent_devices')" x-text="typeof validationError === 'function' ? validationError('concurrent_devices') : ''" class="mt-2 text-sm text-red-600" x-cloak></p>
</div>
