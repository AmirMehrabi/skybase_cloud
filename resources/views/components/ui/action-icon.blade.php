@props([
    'as' => 'a',
    'href' => null,
    'icon' => 'view',
    'label' => null,
    'variant' => null,
    'type' => 'button',
])

@php
    $variant ??= $icon;

    $colors = [
        'view' => 'hover:text-blue-600 hover:bg-blue-50',
        'edit' => 'hover:text-green-600 hover:bg-green-50',
        'delete' => 'hover:text-red-600 hover:bg-red-50',
        'suspend' => 'hover:text-yellow-600 hover:bg-yellow-50',
        'activate' => 'hover:text-emerald-600 hover:bg-emerald-50',
        'cancel' => 'hover:text-red-600 hover:bg-red-50',
        'payment' => 'hover:text-emerald-600 hover:bg-emerald-50',
        'invoice' => 'hover:text-purple-600 hover:bg-purple-50',
        'download' => 'hover:text-purple-600 hover:bg-purple-50',
        'users' => 'hover:text-indigo-600 hover:bg-indigo-50',
        'clone' => 'hover:text-slate-600 hover:bg-slate-100',
        'more' => 'hover:text-gray-600 hover:bg-gray-100',
    ];

    $classes = 'p-1.5 text-gray-400 rounded-lg transition-colors '.($colors[$variant] ?? $colors['view']);
@endphp

@if($as === 'button')
    <button type="{{ $type }}" title="{{ $label }}" aria-label="{{ $label }}" {{ $attributes->merge(['class' => $classes]) }}>
        @include('components.ui.partials.action-icon-svg', ['icon' => $icon])
    </button>
@else
    <a @if($href) href="{{ $href }}" @endif title="{{ $label }}" aria-label="{{ $label }}" {{ $attributes->merge(['class' => $classes]) }}>
        @include('components.ui.partials.action-icon-svg', ['icon' => $icon])
    </a>
@endif
