@props([
    'tabs' => [],
    'defaultTab' => null,
])

@php
$defaultTab ??= array_key_first($tabs) ?? null;
@endphp

<div x-data="{ activeTab: '{{ $defaultTab }}' }">
    <!-- Tab Navigation -->
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8 overflow-x-auto" aria-label="Tabs">
            @foreach($tabs as $key => $label)
                <button
                    @click="activeTab = '{{ $key }}'"
                    :class="activeTab === '{{ $key }}' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
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

@script
<script>
    // Expose activeTab to child components
    document.addEventListener('alpine:init', () => {
        Alpine.store('tabs', {
            activeTab: '{{ $defaultTab }}'
        });
    });
</script>
@endscript
