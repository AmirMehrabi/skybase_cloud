@props([
    'id' => null,
    'name' => null,
    'label' => null,
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'autofocus' => false,
    'min' => null,
    'max' => null,
    'step' => null,
    'xModel' => null,
    'help' => null,
])

<div class="mb-4">
    @if($label)
    <label for="{{ $id }}" class="block text-sm font-medium text-gray-700">
        {{ $label }}
        @if($required)<span class="text-red-500">*</span>@endif
    </label>
    @endif

    <div class="relative">
        <input
            type="number"
            id="{{ $id }}"
            name="{{ $name }}"
            value="{{ old($name, $value) }}"
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            @if($required) required @endif
            @if($autofocus) autofocus @endif
            @if($min) min="{{ $min }}" @endif
            @if($max) max="{{ $max }}" @endif
            @if($step) step="{{ $step }}" @endif
            @if($xModel) x-model="{{ $xModel }}" @endif
            @error($name)
                class="px-4 py-3 w-full bg-white border border-red-500 rounded-lg text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition"
            @else
                class="px-4 py-3 w-full bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition"
            @enderror
        >
    </div>

    @if($help)
        <p class="mt-1 text-sm text-gray-500">{{ $help }}</p>
    @endif

    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
