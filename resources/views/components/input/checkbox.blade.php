@props([
    'id' => null,
    'name' => null,
    'label' => null,
    'value' => null,
    'required' => false,
    'xModel' => null,
])

<div>
    <label class="flex items-start gap-2">
        <input
            type="checkbox"
            id="{{ $id }}"
            name="{{ $name }}"
            value="{{ $value }}"
            @if($required) required @endif
            @if($xModel) x-model="{{ $xModel }}" @endif
            class="mt-1 w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-600"
        >
        <span class="text-sm text-gray-700">
            {!! $label !!}
        </span>
    </label>
</div>
