@props(['title', 'icon', 'id', 'active' => false, 'items' => []])
@php($visibleItems = collect($items)->filter(fn($item) => auth()->user()->canAccessRoute($item['route'])))
@if($visibleItems->isNotEmpty())
<li x-data="{ open: @js($active) }">
    <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-controls="{{ $id }}" @class(['flex w-full items-center gap-3 rounded-lg border px-3 py-2 text-sm font-medium transition', 'border-white/15 bg-white/[0.12] text-white shadow-sm' => $active, 'border-transparent text-teal-50/85 hover:border-white/10 hover:bg-white/10 hover:text-white' => !$active])>
        <x-ui.icon :name="$icon" /><span>{{ $title }}</span><x-ui.icon name="chevron-down" class="ml-auto size-4 transition-transform" x-bind:class="{ 'rotate-180': open }" />
    </button>
    <div id="{{ $id }}" x-show="open" x-cloak class="ml-5 mt-1 space-y-1 border-l border-white/15 pl-4">
        @foreach($visibleItems as $item)
            <a href="{{ route($item['route'], $item['parameters'] ?? []) }}" @if($item['active'] ?? false) aria-current="page" @endif @class(['flex items-center rounded-lg px-3 py-2 text-xs font-medium transition', 'bg-white/10 text-white' => $item['active'] ?? false, 'text-teal-50/65 hover:bg-white/5 hover:text-white' => !($item['active'] ?? false)])>{{ $item['label'] }}</a>
        @endforeach
    </div>
</li>
@endif
