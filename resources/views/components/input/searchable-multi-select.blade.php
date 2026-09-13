@props([
    'name',
    'label' => null,
    'options' => [],
    'selected' => [],
    'placeholder' => 'Select options',
    'searchPlaceholder' => 'Search...',
    'emptyText' => 'No matching options.',
    'hint' => null,
    'required' => false,
])

@php
    $normalizedOptions = collect($options)->map(fn ($option) => [
        'value' => (string) data_get($option, 'value', data_get($option, 'id')),
        'label' => (string) data_get($option, 'label', data_get($option, 'name')),
        'description' => (string) data_get($option, 'description', data_get($option, 'code', '')),
    ])->values()->all();
    $normalizedSelected = collect($selected)->map(fn ($value) => (string) $value)->values()->all();
@endphp

<div
    x-data="{
        open: false,
        search: '',
        selected: @js($normalizedSelected),
        options: @js($normalizedOptions),
        get filteredOptions() {
            const query = this.search.trim().toLowerCase();

            if (!query) return this.options;

            return this.options.filter(option => `${option.label} ${option.description}`.toLowerCase().includes(query));
        },
        get selectedOptions() {
            return this.selected
                .map(value => this.options.find(option => option.value === value))
                .filter(Boolean);
        },
        toggle(value) {
            this.selected = this.selected.includes(value)
                ? this.selected.filter(selectedValue => selectedValue !== value)
                : [...this.selected, value];
            this.$dispatch('selection-changed', { selected: this.selected });
        },
    }"
    x-modelable="selected"
    {{ $attributes->merge(['class' => 'relative']) }}
    @click.outside="open = false"
>
    @if($label)
        <label class="mb-1 block text-sm font-medium text-gray-700">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <template x-for="value in selected" :key="value">
        <input type="hidden" name="{{ $name }}[]" :value="value">
    </template>

    <button
        type="button"
        @click="open = !open; if (open) $nextTick(() => $refs.search.focus())"
        class="flex min-h-10 w-full items-center justify-between gap-3 rounded-lg border border-gray-300 bg-white px-3 py-2 text-left text-sm shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
        :aria-expanded="open"
    >
        <span class="min-w-0 flex-1">
            <span x-show="selected.length === 0" class="text-gray-500">{{ $placeholder }}</span>
            <span x-show="selected.length > 0" class="flex items-center gap-2">
                <span class="truncate text-gray-900" x-text="selectedOptions.slice(0, 2).map(option => option.label).join(', ')"></span>
                <span x-show="selected.length > 2" class="shrink-0 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700" x-text="`+${selected.length - 2}`"></span>
            </span>
        </span>
        <svg class="h-4 w-4 shrink-0 text-gray-400 transition" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"></path>
        </svg>
    </button>

    <div x-cloak x-show="open" x-transition class="absolute z-50 mt-2 w-full rounded-xl border border-gray-200 bg-white p-2 shadow-xl">
        <div class="relative mb-2">
            <svg class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"></path>
            </svg>
            <input x-ref="search" type="search" x-model="search" placeholder="{{ $searchPlaceholder }}" class="block w-full rounded-lg border border-gray-300 py-2 pl-9 pr-3 text-sm focus:border-blue-500 focus:ring-blue-500">
        </div>

        <div class="max-h-60 overflow-y-auto">
            <template x-for="option in filteredOptions" :key="option.value">
                <button type="button" @click="toggle(option.value)" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left hover:bg-gray-50">
                    <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded border" :class="selected.includes(option.value) ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-300 bg-white'">
                        <svg x-show="selected.includes(option.value)" class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="m5 12 4 4L19 7"></path>
                        </svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-medium text-gray-900" x-text="option.label"></span>
                        <span x-show="option.description" class="block truncate text-xs text-gray-500" x-text="option.description"></span>
                    </span>
                </button>
            </template>
            <p x-show="filteredOptions.length === 0" class="px-3 py-6 text-center text-sm text-gray-500">{{ $emptyText }}</p>
        </div>
    </div>

    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @elseif($hint)
        <p class="mt-1 text-xs text-gray-500">{{ $hint }}</p>
    @enderror
</div>
