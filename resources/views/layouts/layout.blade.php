@php
    $isHomePage = request()->is('/');
    $isFeaturesPage = request()->is('features');
    $isPricingPage = request()->is('pricing');
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
        x-data="{ mobileMenuOpen: false, isCompact: window.scrollY > 24 }"
        x-init="window.addEventListener('scroll', () => { isCompact = window.scrollY > 24; }, { passive: true })"
        x-on:keydown.escape.window="mobileMenuOpen = false"
        class="sticky top-0 z-50 border-b border-gray-200/80 bg-white/95 backdrop-blur"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div
                class="flex items-center justify-between gap-4 transition-all duration-200"
                x-bind:class="isCompact ? 'min-h-16 py-2.5' : 'min-h-20 py-4'"
            >
                <div class="flex items-center gap-3">
                    <a href="{{ url('/') }}" class="text-3xl font-bold text-gray-900">
                        <img
                            src="{{ asset('assets/images/logo/logo-black.png') }}"
                            class="transition-all duration-200"
                            x-bind:class="isCompact ? 'max-w-30' : 'max-w-36'"
                            alt="SkyBase Cloud logo"
                        >
                    </a>
                    <div class="hidden rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-gray-500 lg:inline-flex">
                        Cloud ISP Platform
                    </div>
                </div>

                <div class="hidden items-center rounded-full border border-gray-200 bg-gray-50/80 p-1 md:flex">
                    <a href="{{ url('/') }}" class="rounded-full px-4 py-2 text-sm font-semibold transition-colors {{ $isHomePage ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">Home</a>
                    <a href="{{ url('/features') }}" class="rounded-full px-4 py-2 text-sm font-semibold transition-colors {{ $isFeaturesPage ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">Features</a>
                    <a href="{{ url('/pricing') }}" class="rounded-full px-4 py-2 text-sm font-semibold transition-colors {{ $isPricingPage ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">Pricing</a>
                    <a href="{{ url('/') }}#docs" class="rounded-full px-4 py-2 text-sm font-semibold text-gray-600 transition-colors hover:text-gray-900">Docs</a>
                    <a href="{{ url('/') }}#about" class="rounded-full px-4 py-2 text-sm font-semibold text-gray-600 transition-colors hover:text-gray-900">About</a>
                    <a href="{{ url('/') }}#contact" class="rounded-full px-4 py-2 text-sm font-semibold text-gray-600 transition-colors hover:text-gray-900">Contact</a>
                </div>

                <div class="flex items-center gap-4">
                    <a href="{{ route('auth.login') }}" class="hidden items-center justify-center rounded-full border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition-colors hover:border-gray-400 hover:bg-gray-50 lg:inline-flex">Login</a>
                    <a href="{{ route('auth.register') }}" class="hidden items-center justify-center rounded-full bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-blue-700 sm:inline-flex">Register</a>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-full border border-gray-300 bg-white text-gray-700 transition-all duration-200 hover:border-gray-400 hover:bg-gray-50 md:hidden"
                        x-bind:class="isCompact ? 'h-10 w-10' : 'h-12 w-12'"
                        x-on:click="mobileMenuOpen = ! mobileMenuOpen"
                        x-bind:aria-expanded="mobileMenuOpen.toString()"
                        aria-controls="mobile-navigation"
                        aria-label="Toggle navigation menu"
                    >
                        <svg x-show="! mobileMenuOpen" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                        </svg>
                        <svg x-show="mobileMenuOpen" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" />
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
                class="border-t border-gray-200 py-4 md:hidden"
            >
                <div class="space-y-4 rounded-[2rem] border border-gray-200 bg-gray-50 p-4 shadow-sm">
                    <div class="grid grid-cols-2 gap-3">
                        <a x-on:click="mobileMenuOpen = false" href="{{ url('/') }}" class="rounded-2xl border px-4 py-3 text-sm font-semibold transition-colors {{ $isHomePage ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300' }}">Home</a>
                        <a x-on:click="mobileMenuOpen = false" href="{{ url('/features') }}" class="rounded-2xl border px-4 py-3 text-sm font-semibold transition-colors {{ $isFeaturesPage ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300' }}">Features</a>
                        <a x-on:click="mobileMenuOpen = false" href="{{ url('/pricing') }}" class="rounded-2xl border px-4 py-3 text-sm font-semibold transition-colors {{ $isPricingPage ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300' }}">Pricing</a>
                        <a x-on:click="mobileMenuOpen = false" href="{{ url('/') }}#docs" class="rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-700 transition-colors hover:border-gray-300">Docs</a>
                        <a x-on:click="mobileMenuOpen = false" href="{{ url('/') }}#about" class="rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-700 transition-colors hover:border-gray-300">About</a>
                        <a x-on:click="mobileMenuOpen = false" href="{{ url('/') }}#contact" class="rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-700 transition-colors hover:border-gray-300">Contact</a>
                    </div>

                    <div class="rounded-3xl bg-white p-4 ring-1 ring-gray-200">
                        <p class="text-sm font-semibold text-gray-900">Get started with SkyBase Cloud</p>
                        <p class="mt-1 text-sm leading-6 text-gray-600">Launch your tenant, review pricing, or sign back in from one place.</p>
                        <div class="mt-4 flex flex-col gap-3">
                            <a x-on:click="mobileMenuOpen = false" href="{{ route('auth.register') }}" class="inline-flex items-center justify-center rounded-full bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-blue-700">Register</a>
                            <a x-on:click="mobileMenuOpen = false" href="{{ route('auth.login') }}" class="inline-flex items-center justify-center rounded-full border border-gray-300 bg-white px-5 py-3 text-sm font-semibold text-gray-700 transition-colors hover:border-gray-400 hover:bg-gray-50">Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    @yield('content')

    <footer class="bg-gray-900 py-16 text-gray-400">
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
                        <li><a href="{{ url('/') }}#contact" class="hover:text-white">Contact</a></li>
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

            <div class="border-t border-gray-800 pt-8">
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
