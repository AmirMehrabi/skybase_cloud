@props([
    'id' => null,
    'name' => null,
    'label' => null,
    'checked' => false,
    'help' => null,
])

<div>
    <div class="flex items-start">
        <div class="flex items-center h-5">
            <input
                type="checkbox"
                id="{{ $id }}"
                name="{{ $name }}"
                value="1"
                {{ $checked ? 'checked' : '' }}
                class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded"
            >
        </div>
        <div class="ml-3 text-sm">
            @if($label)
            <label for="{{ $id }}" class="font-medium text-gray-700">{{ $label }}</label>
            @endif
            @if($help)
            <p class="text-gray-500">{{ $help }}</p>
            @endif
        </div>
    </div>
</div>
