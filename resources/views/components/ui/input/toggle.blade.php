@props(['name', 'label', 'description' => null, 'checked' => false, 'disabled' => false, 'id' => null])
@php($id ??= 'toggle-'.preg_replace('/[^a-zA-Z0-9_-]/', '-', $name))
<label for="{{ $id }}" {{ $attributes->class(['flex cursor-pointer items-center justify-between gap-5 rounded-xl border border-slate-200 bg-white p-4 transition hover:border-slate-300', 'cursor-default' => $disabled]) }}>
    <span class="min-w-0"><span class="block text-sm font-medium text-slate-800">{{ $label }}</span>@if($description)<span class="mt-1 block text-xs leading-5 text-slate-500">{{ $description }}</span>@endif</span>
    <span class="relative inline-flex shrink-0">
        <x-ui.input.hidden :name="$name" :value="$disabled && $checked ? '1' : '0'" />
        <input id="{{ $id }}" name="{{ $name }}" type="checkbox" value="1" class="peer sr-only" @checked($checked) @disabled($disabled)>
        <span class="h-6 w-10 rounded-full bg-slate-200 transition peer-checked:bg-teal-800 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-4 peer-focus-visible:outline-teal-700 peer-disabled:opacity-50"></span>
        <span class="pointer-events-none absolute left-1 top-1 size-4 rounded-full bg-white shadow-sm transition peer-checked:translate-x-4 peer-disabled:opacity-70"></span>
    </span>
</label>
