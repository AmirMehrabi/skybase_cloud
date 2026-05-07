@props([
    'id' => null,
    'name' => null,
    'label' => null,
    'value' => null,
    'checked' => false,
    'help' => null,
    'xModel' => null,
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
                @if($xModel) x-model="{{ $xModel }}" @endif
                class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-600"
            >
        </div>
        <div class="ml-3 text-sm">
            @if($label)
            <label for="{{ $id }}" class="font-medium text-slate-700">{{ $label }}</label>
            @endif
            @if($help)
            <p class="text-slate-500">{{ $help }}</p>
            @endif
        </div>
    </div>

    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
