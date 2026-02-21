@props([
    'id' => null,
    'name' => null,
    'label' => null,
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'autofocus' => false,
    'icon' => null,
    'xModel' => null,
])

<div class="space-y-2 mb-4">
    @if($label)
    <label for="{{ $id }}" class="block text-sm font-medium text-gray-700">
        {{ $label }}
        @if($required)<span class="text-red-600">*</span>@endif
    </label>
    @endif

    <div class="relative">
        @if($icon)
        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
            <i class="fas fa-{{ $icon }}"></i>
        </span>
        @endif

        <input
            type="email"
            id="{{ $id }}"
            name="{{ $name }}"
            value="{{ old($name, $value) }}"
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            @if($required) required @endif
            @if($autofocus) autofocus @endif
            @if($xModel) x-model="{{ $xModel }}" @endif
            class="{{ $icon ? 'pl-10 pr-4' : 'px-4' }} py-3 w-full bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition"
        >
    </div>
</div>
