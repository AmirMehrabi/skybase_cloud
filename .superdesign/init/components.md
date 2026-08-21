# Shared UI Components

Framework: Laravel 13 Blade with Alpine.js. Styling: Tailwind CSS v4. Components are custom Blade primitives.

## Text Input

Path: `resources/views/components/ui/input/text.blade.php`

```blade
@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'readonly' => false,
    'disabled' => false,
    'error' => null,
    'hint' => null,
])

@php
$id ??= $name;
@endphp

<div class="space-y-1.5">
    @if($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-gray-700">
            {{ $label }}
            @if($required) <span class="text-red-500">*</span>@endif
        </label>
    @endif

    <input
        type="{{ $type }}"
        id="{{ $id }}"
        name="{{ $name }}"
        value="{{ $value }}"
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        @if($required) required @endif
        @if($readonly) readonly @endif
        @if($disabled) disabled @endif
        {{ $attributes->merge(['class' => 'block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border' . ($error ? ' border-red-500 focus:border-red-500 focus:ring-red-500' : '')]) }}
    />

    @if($error)
        <p class="text-sm text-red-600">{{ $error }}</p>
    @elseif($hint)
        <p class="text-sm text-gray-500">{{ $hint }}</p>
    @endif
</div>
```

## Textarea

Path: `resources/views/components/ui/input/textarea.blade.php`

```blade
@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'value' => null,
    'placeholder' => null,
    'rows' => 3,
    'required' => false,
    'readonly' => false,
    'disabled' => false,
    'error' => null,
    'hint' => null,
])

@php
$id ??= $name;
@endphp

<div class="space-y-1.5">
    @if($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-gray-700">
            {{ $label }}
            @if($required) <span class="text-red-500">*</span>@endif
        </label>
    @endif

    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        @if($required) required @endif
        @if($readonly) readonly @endif
        @if($disabled) disabled @endif
        {{ $attributes->merge(['class' => 'block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border resize-none' . ($error ? ' border-red-500 focus:border-red-500 focus:ring-red-500' : '')]) }}
    >{{ $value }}</textarea>

    @if($error)
        <p class="text-sm text-red-600">{{ $error }}</p>
    @elseif($hint)
        <p class="text-sm text-gray-500">{{ $hint }}</p>
    @endif
</div>
```

## Select

Path: `resources/views/components/ui/input/select.blade.php`

```blade
@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'options' => [],
    'value' => null,
    'placeholder' => 'Select an option',
    'required' => false,
    'disabled' => false,
    'error' => null,
    'hint' => null,
])

@php
$id ??= $name;
@endphp

<div class="space-y-1.5">
    @if($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-gray-700">
            {{ $label }}
            @if($required) <span class="text-red-500">*</span>@endif
        </label>
    @endif

    <select
        id="{{ $id }}"
        name="{{ $name }}"
        @if($required) required @endif
        @if($disabled) disabled @endif
        {{ $attributes->merge(['class' => 'block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white' . ($error ? ' border-red-500 focus:border-red-500 focus:ring-red-500' : '')]) }}
    >
        <option value="">{{ $placeholder }}</option>
        @foreach($options as $key => $option)
            <option value="{{ $key }}" @if((string)$value === (string)$key) selected @endif>
                {{ $option }}
            </option>
        @endforeach
    </select>

    @if($error)
        <p class="text-sm text-red-600">{{ $error }}</p>
    @elseif($hint)
        <p class="text-sm text-gray-500">{{ $hint }}</p>
    @endif
</div>
```

