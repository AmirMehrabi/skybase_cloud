@props([
    'id' => null,
    'name' => null,
    'label' => null,
    'value' => null,
    'placeholder' => null,
    'xModel' => null,
])

<div class="space-y-2 mb-4">
    @if($label)
    <label for="{{ $id }}" class="block text-sm font-medium text-slate-700">
        {{ $label }}
    </label>
    @endif

    <input
        type="tel"
        id="{{ $id }}"
        name="{{ $name }}"
        value="{{ old($name, $value) }}"
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        @if($xModel) x-model="{{ $xModel }}" @endif
        class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-slate-950 placeholder-slate-500 transition focus:border-transparent focus:outline-none focus:ring-2 focus:ring-emerald-600"
    >
</div>
