@props([
    'id' => null,
    'name' => null,
    'label' => null,
    'value' => null,
    'options' => [],
    'placeholder' => null,
    'required' => false,
    'xModel' => null,
])

@php
    $options = $options instanceof \Illuminate\Support\Collection ? $options->toArray() : $options;
    $isList = array_is_list($options);
@endphp

<div class="mb-4">
    @if($label)
    <label for="{{ $id }}" class="block text-sm font-medium text-slate-700">
        {{ $label }}
        @if($required)<span class="text-red-500">*</span>@endif
    </label>
    @endif

    <select
        id="{{ $id }}"
        name="{{ $name }}"
        @if($required) required @endif
        @if($xModel) x-model="{{ $xModel }}" @endif
        @error($name)
            class="mt-1 block w-full rounded-lg border border-red-500 bg-white px-3 py-3 text-slate-950 shadow-sm focus:border-transparent focus:ring-2 focus:ring-emerald-600 sm:text-sm"
        @else
            class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-slate-950 shadow-sm focus:border-transparent focus:ring-2 focus:ring-emerald-600 sm:text-sm"
        @enderror
    >
        @if($placeholder)
        <option value="">{{ $placeholder }}</option>
        @endif

        @foreach($options as $optionValue => $optionLabel)
        @if($isList)
            @php
                $optionValue = $optionLabel;
            @endphp
        @endif
        <option value="{{ $optionValue }}" {{ (string) old($name, $value) === (string) $optionValue ? 'selected' : '' }}>{{ $optionLabel }}</option>
        @endforeach
    </select>

    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
