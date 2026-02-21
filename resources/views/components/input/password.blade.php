@props([
    'id' => null,
    'name' => null,
    'label' => null,
    'placeholder' => null,
    'required' => false,
    'minlength' => null,
    'xModel' => null,
    'showToggle' => false,
])

<div class="space-y-2 mb-4">
    @if($label)
    <label for="{{ $id }}" class="block text-sm font-medium text-gray-700">
        {{ $label }}
        @if($required)<span class="text-red-600">*</span>@endif
    </label>
    @endif

    <div class="relative">
        <input
            type="password"
            id="{{ $id }}"
            name="{{ $name }}"
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            @if($required) required @endif
            @if($minlength) minlength="{{ $minlength }}" @endif
            @if($xModel) x-model="{{ $xModel }}" @endif
            class="px-4 py-3 w-full bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition"
        >

        @if($showToggle)
        <button
            type="button"
            @click="$el.previousElementSibling.type = $el.previousElementSibling.type === 'password' ? 'text' : 'password'"
            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700 transition"
        >
            <i class="fas fa-eye"></i>
        </button>
        @endif
    </div>
</div>
