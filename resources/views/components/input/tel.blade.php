@props([
    'id' => null,
    'name' => null,
    'label' => null,
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'xModel' => null,
    'compact' => false,
])

@php
    $id ??= $name;
    $hasError = $errors->has($name);
    $inputClasses = $compact
        ? 'block w-full rounded-lg shadow-sm sm:text-sm py-2 px-3 border '.($hasError
            ? 'border-red-500 focus:border-red-500 focus:ring-red-500'
            : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500')
        : 'w-full rounded-lg border bg-white px-4 py-3 text-slate-950 placeholder-slate-500 transition focus:border-transparent focus:outline-none focus:ring-2 focus:ring-emerald-600 '.($hasError
            ? 'border-red-500'
            : 'border-slate-300');
@endphp

<div @class(['space-y-2 mb-4' => ! $compact])>
    @if($label)
        <label for="{{ $id }}" @class([
            'block text-sm font-medium',
            'text-gray-700 mb-1' => $compact,
            'text-slate-700' => ! $compact,
        ])>
            {{ $label }}
            @if($required)<span class="text-red-500">*</span>@endif
        </label>
    @endif

    <input
        type="tel"
        id="{{ $id }}"
        name="{{ $name }}"
        value="{{ old($name, $value) }}"
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        @if($required) required @endif
        @if($xModel) x-model="{{ $xModel }}" @endif
        class="{{ $inputClasses }}"
    >

    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
