@php
    $direction = config('ui.direction', 'ltr');
    $language = config('ui.language', 'en');
    $isRtl = $direction === 'rtl';
    $customer = auth('customer')->user();
    $brandingTenant = tenant() ?? $customer?->tenant;
    $navbarLogoUrl = $brandingTenant?->navbarLogoUrl() ?? asset('assets/images/logo/logo-black.png');
    $hasCustomNavbarLogo = (bool) ($brandingTenant?->brandingAssetUrl('company_logo_dark') ?? $brandingTenant?->brandingAssetUrl('company_logo'));
@endphp

<!DOCTYPE html>
<html lang="{{ $language === 'fa' ? 'fa' : 'en' }}" dir="{{ $direction }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'Customer Portal') - {{ config('app.name', 'SkyBase') }}</title>
        <link rel="icon" href="{{ $brandingTenant?->faviconUrl() ?? asset('favicon.ico') }}">

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif

        <style>
            [x-cloak] { display: none !important; }
        </style>

        @stack('styles')
    </head>
    <body class="bg-[#f6f1e8] text-slate-950" style="direction: {{ $direction }};">
        <div id="sidebar-overlay" class="fixed inset-0 z-40 hidden bg-slate-950/60 lg:hidden"></div>

        <aside id="sidebar" class="fixed top-0 {{ $isRtl ? 'right-0' : 'left-0' }} z-50 h-screen w-64 overflow-hidden bg-[#0d2f35] text-white shadow-[0_35px_90px_rgba(13,47,53,0.28)] transform {{ $isRtl ? 'translate-x-full lg:translate-x-0' : '-translate-x-full lg:translate-x-0' }} transition-transform duration-300">
            <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_20%_15%,rgba(34,197,94,0.24),transparent_28%),radial-gradient(circle_at_80%_10%,rgba(245,158,11,0.18),transparent_30%),linear-gradient(135deg,#09252b_0%,#0d2f35_48%,#123f3d_100%)]"></div>
            <div class="flex h-full flex-col">
                <div class="flex h-[60px] items-center border-b border-white/10 bg-white/[0.03] px-6 backdrop-blur-xl">
                    <a href="{{ route('customer.dashboard') }}" class="flex items-center gap-3 text-white">
                        @if($hasCustomNavbarLogo)
                            <img src="{{ $navbarLogoUrl }}" class="max-h-9 max-w-36 object-contain" alt="{{ $brandingTenant?->company_name ?? config('app.name', 'SkyBase') }} logo">
                        @else
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/15">
                                <img src="{{ $navbarLogoUrl }}" class="max-w-6 brightness-0 invert" alt="SkyBase Cloud logo mark">
                            </span>
                            <span class="leading-none">
                                <span class="block text-base font-bold tracking-tight">{{ config('app.name', 'SkyBase') }}</span>
                                <span class="block text-[10px] font-bold uppercase tracking-[0.18em] text-emerald-100/80">Customer Portal</span>
                            </span>
                        @endif
                    </a>
                </div>

                <nav class="flex-1 overflow-y-auto px-4 py-6">
                    @include('customer.partials.sidebar')
                </nav>
            </div>
        </aside>

        <div id="main-content-wrapper" class="{{ $isRtl ? 'pr-0 lg:pr-64' : 'pl-0 lg:pl-64' }}">
            <header class="fixed top-0 z-30 h-[60px] border-b border-slate-900/10 bg-[#fffaf0]/90 shadow-sm backdrop-blur-xl {{ $isRtl ? 'right-0 left-0 lg:right-64' : 'right-0 left-0 lg:left-64' }}">
                <div class="flex h-full items-center gap-3 px-6">
                    <div class="flex items-center gap-2">
                        <button id="mobile-menu-button" class="rounded-lg p-2 text-slate-600 hover:bg-[#fbf7ed] hover:text-slate-950 lg:hidden" type="button">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>

                        <x-notifications.dropdown guard="customer" />

                        <div class="relative">
                            <button id="user-menu-button" type="button" class="flex items-center gap-2 rounded-lg p-2 text-slate-700 hover:bg-[#fbf7ed]">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#0d2f35] text-sm font-semibold text-white shadow-[0_10px_24px_rgba(13,47,53,0.18)]">
                                    {{ strtoupper(substr($customer?->full_name ?? 'C', 0, 1)) }}
                                </span>
                                <span class="hidden max-w-40 truncate text-sm font-medium md:block">{{ $customer?->full_name }}</span>
                                <svg class="hidden h-4 w-4 text-slate-500 md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>

                            <div id="user-menu" class="absolute {{ $isRtl ? 'right-0' : 'left-0' }} z-50 mt-2 hidden w-56 rounded-xl border border-slate-900/10 bg-white py-1 shadow-xl">
                                <div class="border-b border-slate-900/10 px-4 py-3">
                                    <p class="truncate text-sm font-semibold text-slate-950">{{ $customer?->full_name }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ $customer?->email }}</p>
                                </div>
                                <form method="POST" action="{{ route('customer.logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-slate-700 hover:bg-[#fbf7ed]">Logout</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="min-w-0 flex-1 px-4">
                        <h1 class="truncate text-base font-semibold text-slate-950">@yield('page_title', 'Dashboard')</h1>
                    </div>
                </div>
            </header>

            <main class="min-h-screen px-4 pb-8 pt-[76px] sm:px-6 md:px-10">
                <div class="mb-4 space-y-3">
                    <x-ui.alert type="success" :message="session('success')" />
                    <x-ui.alert type="error" :message="session('error')" />
                </div>

                @yield('content')
            </main>
        </div>

        <script>
            const isRtl = document.documentElement.dir === 'rtl' || document.body.style.direction === 'rtl';
            const translateClass = isRtl ? 'translate-x-full' : '-translate-x-full';
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebar-overlay');
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const userMenuButton = document.getElementById('user-menu-button');
            const userMenu = document.getElementById('user-menu');

            function setSidebarState() {
                if (window.innerWidth >= 1024) {
                    sidebar.classList.remove(translateClass);
                    sidebarOverlay.classList.add('hidden');
                    return;
                }

                sidebar.classList.add(translateClass);
                sidebarOverlay.classList.add('hidden');
            }

            function toggleSidebar() {
                if (window.innerWidth < 1024) {
                    sidebar.classList.toggle(translateClass);
                    sidebarOverlay.classList.toggle('hidden');
                }
            }

            setSidebarState();
            window.addEventListener('resize', setSidebarState);
            mobileMenuButton?.addEventListener('click', toggleSidebar);
            sidebarOverlay?.addEventListener('click', toggleSidebar);

            userMenuButton?.addEventListener('click', (event) => {
                event.stopPropagation();
                userMenu.classList.toggle('hidden');
            });

            document.addEventListener('click', (event) => {
                if (userMenu && ! userMenuButton.contains(event.target) && ! userMenu.contains(event.target)) {
                    userMenu.classList.add('hidden');
                }
            });
        </script>

        @stack('scripts')
    </body>
</html>
