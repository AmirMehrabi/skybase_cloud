@props([
    'title' => null,
    'value' => null,
    'icon' => null,
    'trend' => null,
    'color' => 'blue',
])

@php
$colors = [
    'blue' => 'bg-blue-50 text-blue-600 border-blue-100',
    'green' => 'bg-green-50 text-green-600 border-green-100',
    'yellow' => 'bg-yellow-50 text-yellow-600 border-yellow-100',
    'red' => 'bg-red-50 text-red-600 border-red-100',
    'purple' => 'bg-purple-50 text-purple-600 border-purple-100',
    'indigo' => 'bg-indigo-50 text-indigo-600 border-indigo-100',
];

$colorClass = $colors[$color] ?? $colors['blue'];
@endphp

<div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
    <div class="flex items-center justify-between">
        <div class="flex-1">
            <p class="text-sm font-medium text-gray-500">{{ $title }}</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $value }}</p>
            @if($trend)
                <p class="text-sm mt-2 {{ $trend['positive'] ? 'text-green-600' : 'text-red-600' }}">
                    @if($trend['positive'])
                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                        </svg>
                    @else
                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                        </svg>
                    @endif
                    {{ $trend['value'] }}
                </p>
            @endif
        </div>
        @if($icon)
            <div class="w-14 h-14 rounded-xl {{ $colorClass }} flex items-center justify-center">
                {{ $icon }}
            </div>
        @endif
    </div>
</div>
