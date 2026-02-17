@props([
    'tabs' => [],
    'activeTab' => null,
])

@php
$activeTab ??= $tabs[array_key_first($tabs)] ?? null;
@endphp

<div>
    <!-- Tab Navigation -->
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            @foreach($tabs as $key => $label)
                <button
                    wire:click="$set('activeTab', '{{ $key }}')"
                    class="{{ $activeTab === $key ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>

    <!-- Tab Content -->
    <div class="mt-6">
        {{ $slot }}
    </div>
</div>
