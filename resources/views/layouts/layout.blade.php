@php
    $isHomePage = request()->is('/');
    $isFeaturesPage = request()->is('features');
    $isPricingPage = request()->is('pricing');
    $isContactPage = request()->is('contact');
    $isChangelogPage = request()->is('changelog');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('meta_description', 'SkyBase Cloud - Cloud Control Plane for MikroTik ISPs.')">
    <meta name="keywords" content="@yield('meta_keywords', 'ISP management software, MikroTik management, Radius server')">
    <meta name="author" content="SkyBase Cloud">

    <meta property="og:title" content="@yield('og_title', 'SkyBase Cloud')">
    <meta property="og:description" content="@yield('og_description', 'SkyBase Cloud - Cloud Control Plane for MikroTik ISPs.')">
    <meta property="og:type" content="website">
    <meta property="og:url" content="@yield('og_url', url()->current())">
    <meta property="og:image" content="{{ asset('images/og-image.png') }}">

    <title>@yield('title', 'SkyBase Cloud')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    <style>
        [x-cloak] {
            display: none !important;
        }

        body {
            font-family: 'Manrope', sans-serif;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: 'Space Grotesk', sans-serif;
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="@yield('body_class', 'bg-white')">
    <nav
        x-data="{ mobileMenuOpen: false }"
        x-on:keydown.escape.window="mobileMenuOpen = false"
        class="sticky top-0 z-50 border-b border-slate-900/10 bg-[#fffaf0]/90 backdrop-blur-xl"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex min-h-16 items-center justify-between gap-4 py-3">
                <a href="{{ url('/') }}" class="flex items-center gap-3" aria-label="SkyBase Cloud home">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#0d2f35] shadow-[0_12px_30px_rgba(13,47,53,0.18)]">
                        <img src="{{ asset('assets/images/logo/logo-black.png') }}" class="max-w-8 brightness-0 invert" alt="SkyBase Cloud logo mark">
                    </span>
                    <span class="leading-none">
                        <span class="block text-lg font-bold tracking-tight text-slate-950">SkyBase</span>
                        <span class="block text-[11px] font-bold uppercase tracking-[0.22em] text-emerald-700">Cloud ISP</span>
                    </span>
                </a>

                <div class="hidden items-center gap-3 md:flex">
                    <a href="{{ url('/') }}" class="rounded-lg border px-4 py-2 text-sm font-bold transition {{ $isHomePage ? 'border-[#0d2f35] bg-[#0d2f35] text-white shadow-[0_10px_24px_rgba(13,47,53,0.18)]' : 'border-slate-900/10 bg-white/80 text-slate-700 shadow-sm hover:border-[#0d2f35]/25 hover:bg-[#fbf7ed] hover:text-slate-950' }}">Home</a>
                    <a href="{{ url('/features') }}" class="rounded-lg border px-4 py-2 text-sm font-bold transition {{ $isFeaturesPage ? 'border-[#0d2f35] bg-[#0d2f35] text-white shadow-[0_10px_24px_rgba(13,47,53,0.18)]' : 'border-slate-900/10 bg-white/80 text-slate-700 shadow-sm hover:border-[#0d2f35]/25 hover:bg-[#fbf7ed] hover:text-slate-950' }}">Features</a>
                    <a href="{{ url('/pricing') }}" class="rounded-lg border px-4 py-2 text-sm font-bold transition {{ $isPricingPage ? 'border-[#0d2f35] bg-[#0d2f35] text-white shadow-[0_10px_24px_rgba(13,47,53,0.18)]' : 'border-slate-900/10 bg-white/80 text-slate-700 shadow-sm hover:border-[#0d2f35]/25 hover:bg-[#fbf7ed] hover:text-slate-950' }}">Pricing</a>
                    <a href="{{ route('changelog') }}" class="rounded-lg border px-4 py-2 text-sm font-bold transition {{ $isChangelogPage ? 'border-[#0d2f35] bg-[#0d2f35] text-white shadow-[0_10px_24px_rgba(13,47,53,0.18)]' : 'border-slate-900/10 bg-white/80 text-slate-700 shadow-sm hover:border-[#0d2f35]/25 hover:bg-[#fbf7ed] hover:text-slate-950' }}">Changelog</a>
                    <a href="{{ route('contact.show') }}" class="rounded-lg border px-4 py-2 text-sm font-bold transition {{ $isContactPage ? 'border-[#0d2f35] bg-[#0d2f35] text-white shadow-[0_10px_24px_rgba(13,47,53,0.18)]' : 'border-slate-900/10 bg-white/80 text-slate-700 shadow-sm hover:border-[#0d2f35]/25 hover:bg-[#fbf7ed] hover:text-slate-950' }}">Contact</a>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('auth.login') }}" class="hidden rounded-lg border border-slate-900/10 bg-white/80 px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:border-[#0d2f35]/25 hover:bg-[#fbf7ed] hover:text-slate-950 lg:inline-flex">Login</a>
                    <a href="{{ route('auth.register') }}" class="hidden rounded-lg border border-[#f5c542] bg-[#f5c542] px-5 py-2.5 text-sm font-bold text-slate-950 shadow-[0_12px_30px_rgba(245,197,66,0.28)] transition hover:-translate-y-0.5 hover:bg-[#ffd95d] sm:inline-flex">Start Trial</a>
                    <button
                        type="button"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-900/10 bg-white text-slate-800 shadow-sm transition hover:bg-[#f6f1e8] md:hidden"
                        x-on:click="mobileMenuOpen = ! mobileMenuOpen"
                        x-bind:aria-expanded="mobileMenuOpen.toString()"
                        aria-controls="mobile-navigation"
                        aria-label="Toggle navigation menu"
                    >
                        <svg x-show="! mobileMenuOpen" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                        </svg>
                        <svg x-show="mobileMenuOpen" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18" />
                        </svg>
                    </button>
                </div>
            </div>

            <div
                id="mobile-navigation"
                x-show="mobileMenuOpen"
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-2"
                x-on:click.outside="mobileMenuOpen = false"
                class="pb-4 md:hidden"
            >
                <div class="overflow-hidden rounded-[1.75rem] border border-slate-900/10 bg-white shadow-xl">
                    <div class="grid grid-cols-2 gap-2 p-3">
                        <a x-on:click="mobileMenuOpen = false" href="{{ url('/') }}" class="rounded-lg border px-4 py-3 text-sm font-bold transition {{ $isHomePage ? 'border-[#0d2f35] bg-[#0d2f35] text-white' : 'border-slate-900/10 bg-[#f6f1e8] text-slate-700' }}">Home</a>
                        <a x-on:click="mobileMenuOpen = false" href="{{ url('/features') }}" class="rounded-lg border px-4 py-3 text-sm font-bold transition {{ $isFeaturesPage ? 'border-[#0d2f35] bg-[#0d2f35] text-white' : 'border-slate-900/10 bg-[#f6f1e8] text-slate-700' }}">Features</a>
                        <a x-on:click="mobileMenuOpen = false" href="{{ url('/pricing') }}" class="rounded-lg border px-4 py-3 text-sm font-bold transition {{ $isPricingPage ? 'border-[#0d2f35] bg-[#0d2f35] text-white' : 'border-slate-900/10 bg-[#f6f1e8] text-slate-700' }}">Pricing</a>
                        <a x-on:click="mobileMenuOpen = false" href="{{ route('changelog') }}" class="rounded-lg border px-4 py-3 text-sm font-bold transition {{ $isChangelogPage ? 'border-[#0d2f35] bg-[#0d2f35] text-white' : 'border-slate-900/10 bg-[#f6f1e8] text-slate-700' }}">Changelog</a>
                        <a x-on:click="mobileMenuOpen = false" href="{{ route('contact.show') }}" class="rounded-lg border px-4 py-3 text-sm font-bold transition {{ $isContactPage ? 'border-[#0d2f35] bg-[#0d2f35] text-white' : 'border-slate-900/10 bg-[#f6f1e8] text-slate-700' }}">Contact</a>
                    </div>
                    <div class="border-t border-slate-900/10 bg-[#0d2f35] p-4 text-white">
                        <p class="text-sm font-bold">Ready to try SkyBase?</p>
                        <p class="mt-1 text-sm leading-6 text-teal-50/75">Start a tenant, compare pricing, or sign in to your dashboard.</p>
                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <a x-on:click="mobileMenuOpen = false" href="{{ route('auth.register') }}" class="inline-flex items-center justify-center rounded-lg border border-[#f5c542] bg-[#f5c542] px-4 py-3 text-sm font-bold text-slate-950">Start Trial</a>
                            <a x-on:click="mobileMenuOpen = false" href="{{ route('auth.login') }}" class="inline-flex items-center justify-center rounded-lg border border-white/20 px-4 py-3 text-sm font-bold text-white">Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    @yield('content')

    <footer class="bg-slate-950 py-16 text-slate-400">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-12 md:grid-cols-4">
                <div>
                    <h3 class="mb-4 text-2xl font-bold text-white">SkyBase Cloud</h3>
                    <p class="text-lg leading-relaxed">
                        Cloud-based ISP management platform for MikroTik networks. Simplify billing, authentication, and monitoring.
                    </p>
                </div>

                <div>
                    <h4 class="mb-3 font-semibold text-white">Product</h4>
                    <ul class="space-y-2 text-lg">
                        <li><a href="{{ url('/features') }}" class="hover:text-white">Features</a></li>
                        <li><a href="{{ url('/pricing') }}" class="hover:text-white">Pricing</a></li>
                        <li><a href="{{ route('auth.register') }}" class="hover:text-white">Start Free</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="mb-3 font-semibold text-white">Company</h4>
                    <ul class="space-y-2 text-lg">
                        <li><a href="{{ url('/') }}#about" class="hover:text-white">About</a></li>
                        <li><a href="{{ route('contact.show') }}" class="hover:text-white">Contact</a></li>
                        <li><a href="#" class="hover:text-white">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-white">Terms of Service</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="mb-3 font-semibold text-white">Resources</h4>
                    <ul class="space-y-2 text-lg">
                        <li><a href="#" class="hover:text-white">ISP Management Guide</a></li>
                        <li><a href="#" class="hover:text-white">MikroTik Integration</a></li>
                        <li><a href="#" class="hover:text-white">Radius Authentication</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-white/10 pt-8">
                <div class="flex flex-col items-center justify-between gap-4 md:flex-row">
                    <div class="text-lg">
                        <a href="mailto:support@skybase.app" class="hover:text-white">support@skybase.app</a>
                        <span class="mx-2">.</span>
                        <a href="https://skybase.app" class="hover:text-white">skybase.app</a>
                    </div>
                    <p class="text-lg">&copy; 2026 SkyBase Cloud. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
