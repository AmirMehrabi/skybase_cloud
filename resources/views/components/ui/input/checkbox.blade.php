@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'value' => null,
    'checked' => false,
    'disabled' => false,
    'error' => null,
])

@php
$id ??= $name;
@endphp

<div>
    <label class="flex items-center gap-3 cursor-pointer">
        <input
            type="checkbox"
            id="{{ $id }}"
            name="{{ $name }}"
            value="{{ $value ?? '1' }}"
            @if($checked || $value) checked @endif
            @if($disabled) disabled @endif
            {{ $attributes->merge(['class' => 'h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500']) }}
        />
        @if($label)
            <span class="text-sm text-gray-700">{{ $label }}</span>
        @endif
    </label>

    @if($error)
        <p class="text-sm text-red-600 mt-1">{{ $error }}</p>
    @endif
</div>
