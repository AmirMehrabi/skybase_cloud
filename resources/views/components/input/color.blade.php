@props([
    'id' => null,
    'name' => null,
    'label' => null,
    'value' => null,
    'required' => false,
    'xModel' => null,
])

<div>
    @if($label)
    <label for="{{ $id }}" class="block text-sm font-medium text-gray-700">
        {{ $label }}
        @if($required)<span class="text-red-500">*</span>@endif
    </label>
    @endif

    <div class="flex items-center gap-3">
        <input
            type="color"
            id="{{ $id }}"
            name="{{ $name }}"
            value="{{ old($name, $value) }}"
            @if($required) required @endif
            @if($xModel) x-model="{{ $xModel }}" @endif
            class="h-10 w-20 rounded border border-gray-300 cursor-pointer"
        >
        <input
            type="text"
            value="{{ old($name, $value) }}"
            @if($xModel) :value="{{ $xModel }}" @endif
            readonly
            class="min-w-0 flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
        >
    </div>

    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
