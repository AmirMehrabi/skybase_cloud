@props([
    'id' => null,
    'name' => null,
    'label' => null,
    'value' => null,
    'required' => false,
    'rows' => 3,
    'placeholder' => null,
    'xModel' => null,
])

<div class="mb-4">
    @if($label)
    <label for="{{ $id }}" class="block text-sm font-medium text-gray-700">
        {{ $label }}
        @if($required)<span class="text-red-500">*</span>@endif
    </label>
    @endif

    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        @if($required) required @endif
        @if($xModel) x-model="{{ $xModel }}" @endif
        @error($name)
            class="mt-1 block w-full rounded-md border border-red-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
        @else
            class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
        @enderror
    >{{ old($name, $value) }}</textarea>

    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
