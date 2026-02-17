@props([
    'status' => 'default',
])

@php
$classes = match($status) {
    'active', 'success', 'online', 'paid' => 'bg-green-100 text-green-800 border-green-200',
    'pending', 'warning' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
    'suspended', 'error', 'danger', 'failed', 'unpaid' => 'bg-red-100 text-red-800 border-red-200',
    'terminated', 'inactive', 'offline' => 'bg-gray-100 text-gray-800 border-gray-200',
    'info', 'building', 'processing' => 'bg-blue-100 text-blue-800 border-blue-200',
    default => 'bg-gray-100 text-gray-800 border-gray-200',
};
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border ' . $classes]) }}>
    {{ $slot }}
</span>
