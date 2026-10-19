@props(['title', 'description', 'icon', 'href'])
<a href="{{ $href }}" class="group flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-teal-700/40 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-teal-700">
    <div class="mb-5 flex items-center justify-between">
        <span class="flex size-11 items-center justify-center rounded-xl bg-slate-50 text-teal-900 ring-1 ring-slate-200/80 transition group-hover:bg-teal-50 group-hover:ring-teal-100"><x-ui.icon :name="$icon" class="size-5" /></span>
        <x-ui.icon name="arrow-up-right" class="size-4 text-slate-300 transition group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:text-teal-700" />
    </div>
    <h3 class="text-base font-semibold text-slate-900">{{ $title }}</h3>
    <p class="mt-2 text-sm leading-6 text-slate-500">{{ $description }}</p>
    <span class="mt-5 inline-flex items-center gap-1.5 text-xs font-semibold text-teal-800">Manage settings <x-ui.icon name="chevron-right" class="size-3.5" /></span>
</a>
