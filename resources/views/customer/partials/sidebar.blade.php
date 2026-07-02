@php
    $currentRoute = request()->route()?->getName() ?? '';
    $items = [
        ['route' => 'customer.dashboard', 'match' => ['customer.dashboard', 'customer.dashboard.redirect'], 'label' => 'Dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
        ['route' => 'customer.subscriptions.index', 'match' => ['customer.subscriptions.'], 'label' => 'Subscriptions', 'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
        ['route' => 'customer.invoices.index', 'match' => ['customer.invoices.'], 'label' => 'Invoices', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        ['route' => 'customer.support.index', 'match' => ['customer.support.'], 'label' => 'Support', 'icon' => 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z'],
        ['route' => 'customer.profile.show', 'match' => ['customer.profile.'], 'label' => 'Profile', 'icon' => 'M5.121 17.804A9 9 0 1118.88 17.804M15 11a3 3 0 11-6 0 3 3 0 016 0z'],
    ];
@endphp

<ul class="space-y-1">
    @foreach($items as $item)
        @php
            $active = collect($item['match'])->contains(fn (string $match) => str_ends_with($match, '.') ? str_starts_with($currentRoute, $match) : $currentRoute === $match);
        @endphp
        <li>
            <a href="{{ route($item['route']) }}" class="flex items-center gap-3 rounded-lg border px-3 py-2 text-sm font-medium transition {{ $active ? 'border-white/15 bg-white/[0.12] text-white shadow-sm' : 'border-transparent text-teal-50/85 hover:border-white/10 hover:bg-white/10 hover:text-white' }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"></path>
                </svg>
                <span>{{ $item['label'] }}</span>
            </a>
        </li>
    @endforeach
</ul>
