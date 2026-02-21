@props([
    'title' => null,
    'description' => null,
])

<div class="bg-white shadow rounded-lg mb-6">
    @if($title || $description)
    <div class="px-6 py-4 border-b border-gray-200">
        @if($title)
        <h3 class="text-lg font-medium text-gray-900">{{ $title }}</h3>
        @endif
        @if($description)
        <p class="mt-1 text-sm text-gray-500">{{ $description }}</p>
        @endif
    </div>
    @endif

    <div class="p-6">
        {{ $slot }}
    </div>
</div>
