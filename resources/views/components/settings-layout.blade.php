@props(['section'])
@php
$sections = App\Support\SettingsNavigation::sections();
$current = $sections[$section];
@endphp
<div class="mx-auto max-w-7xl">
    <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('dashboard')], ['label' => 'Settings', 'url' => route('settings.index')], ['label' => $current['title']]]" />
    <x-ui.page-header :title="$current['title']" :description="$current['description']" :icon="$current['icon']" :eyebrow="$current['group']">
        <a href="{{ route('settings.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50"><x-ui.icon name="arrow-left" class="size-4" />All settings</a>
    </x-ui.page-header>
    <div class="grid items-start gap-7 lg:grid-cols-[220px_minmax(0,1fr)]">
        <aside class="min-w-0 lg:sticky lg:top-6">
            <nav aria-label="Settings sections" class="flex gap-1 overflow-x-auto rounded-xl border border-slate-200 bg-white p-2 lg:block lg:space-y-1 lg:overflow-visible">
                @foreach($sections as $key => $entry)
                    @if(auth()->user()->canAccessRoute($entry['route']))
                        @if($loop->first || $entry['group'] !== ($previousGroup ?? null))<p class="hidden px-3 pb-2 pt-4 text-[10px] font-semibold uppercase tracking-widest text-slate-400 lg:block">{{ $entry['group'] }}</p>@endif
                        <a href="{{ route($entry['route'], $entry['parameters']) }}" @if($key === $section) aria-current="page" @endif @class(['flex shrink-0 items-center gap-2.5 rounded-lg px-3 py-2.5 text-xs font-medium transition', 'bg-teal-50 text-teal-900 ring-1 ring-inset ring-teal-900/10' => $key === $section, 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' => $key !== $section])><x-ui.icon :name="$entry['icon']" class="size-4" />{{ $entry['title'] }}</a>
                        @php($previousGroup = $entry['group'])
                    @endif
                @endforeach
            </nav>
        </aside>
        <div class="min-w-0 space-y-6">
            @if($errors->any())<div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800"><p class="mb-2 font-semibold">Please check the following fields</p>@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
            {{ $slot }}
        </div>
    </div>
</div>
