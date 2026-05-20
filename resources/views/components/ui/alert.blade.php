@props([
    'type' => 'success',
    'message' => null,
])

@php
    $styles = match ($type) {
        'error' => 'bg-red-50 border-red-200 text-red-800',
        'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
        'info' => 'bg-blue-50 border-blue-200 text-blue-800',
        default => 'bg-green-50 border-green-200 text-green-800',
    };
@endphp

@if(filled($message))
    <div {{ $attributes->merge(['class' => "rounded-xl border px-4 py-3 text-sm font-medium {$styles}"]) }}>
        {{ $message }}
    </div>
@endif
