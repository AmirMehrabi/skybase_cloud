@extends('layouts.admin')
@section('title', 'Settings')
@section('content')
<div class="mx-auto max-w-7xl">
    <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('dashboard')], ['label' => 'Settings']]" />
    <x-ui.page-header title="Settings" description="Your workspace, your way. Manage your company, connections and team from one place." icon="settings" />
    <div class="space-y-9">
        @foreach(collect(App\Support\SettingsNavigation::sections())->filter(fn($section) => auth()->user()->canAccessRoute($section['route']))->groupBy('group') as $group => $sections)
            <section aria-labelledby="settings-group-{{ $loop->index }}">
                <div class="mb-4 flex items-center gap-3"><h2 id="settings-group-{{ $loop->index }}" class="text-xs font-semibold uppercase tracking-widest text-slate-500">{{ $group }}</h2><div class="h-px flex-1 bg-slate-200"></div></div>
                <div class="grid auto-rows-fr gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach($sections as $section)<x-settings-card :title="$section['title']" :description="$section['description']" :icon="$section['icon']" :href="route($section['route'], $section['parameters'])" />@endforeach
                </div>
            </section>
        @endforeach
    </div>
</div>
@endsection
