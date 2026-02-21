@props([
    'id' => null,
    'name' => null,
    'label' => null,
    'options' => [],
    'placeholder' => null,
    'xModel' => null,
])

<div class="space-y-2 mb-4">
    @if($label)
    <label for="{{ $id }}" class="block text-sm font-medium text-gray-700">
        {{ $label }}
    </label>
    @endif

    <select
        id="{{ $id }}"
        name="{{ $name }}"
        @if($xModel) x-model="{{ $xModel }}" @endif
        class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition"
    >
        @if($placeholder)
        <option value="">{{ $placeholder }}</option>
        @endif

        @foreach($options as $value => $label)
        <option value="{{ $value }}" {{ old($name) == $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
</div>
