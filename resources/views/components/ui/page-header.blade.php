@props(['title', 'description' => null, 'icon' => null, 'eyebrow' => null])
<div {{ $attributes->class(['mb-7 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between']) }}>
    <div class="flex items-start gap-4">
        @if($icon)<span class="flex size-12 shrink-0 items-center justify-center rounded-2xl border border-teal-900/10 bg-teal-50 text-teal-900"><x-ui.icon :name="$icon" class="size-6" /></span>@endif
        <div>
            @if($eyebrow)<p class="mb-1 text-xs font-semibold uppercase tracking-widest text-teal-700">{{ $eyebrow }}</p>@endif
            <h1 class="text-2xl font-semibold tracking-tight text-slate-950">{{ $title }}</h1>
            @if($description)<p class="mt-1.5 max-w-2xl text-sm leading-6 text-slate-500">{{ $description }}</p>@endif
        </div>
    </div>
    @if(!$slot->isEmpty())<div class="flex shrink-0 flex-wrap items-center gap-3">{{ $slot }}</div>@endif
</div>
