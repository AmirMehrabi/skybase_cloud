@props([
    'value' => 0,
    'max' => 100,
    'color' => 'blue',
    'size' => 'md',
    'showLabel' => false,
])

@php
$colors = [
    'blue' => 'bg-blue-500',
    'green' => 'bg-green-500',
    'yellow' => 'bg-yellow-500',
    'red' => 'bg-red-500',
    'purple' => 'bg-purple-500',
    'indigo' => 'bg-indigo-500',
];

$sizes = [
    'sm' => 'h-1.5',
    'md' => 'h-2.5',
    'lg' => 'h-4',
];

$colorClass = $colors[$color] ?? $colors['blue'];
$sizeClass = $sizes[$size] ?? $sizes['md'];
$percentage = min(max(($value / $max) * 100, 0), 100);
@endphp

<div class="w-full">
    @if($showLabel)
        <div class="flex justify-between mb-1">
            <span class="text-sm font-medium text-gray-700">{{ $slot }}</span>
            <span class="text-sm font-medium text-gray-700">{{ $percentage }}%</span>
        </div>
    @endif
    <div class="w-full bg-gray-200 rounded-full {{ $sizeClass }}">
        <div class="{{ $colorClass }} {{ $sizeClass }} rounded-full transition-all duration-500" style="width: {{ $percentage }}%"></div>
    </div>
</div>
