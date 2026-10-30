@php
    $isHomePage = request()->is('/');
    $canonicalUrl = rtrim(config('app.url'), '/').($isHomePage ? '' : '/'.request()->path());
    $isFeaturesPage = request()->is('features');
    $isPricingPage = request()->is('pricing');
    $isContactPage = request()->is('contact');
    $isChangelogPage = request()->is('changelog');
    $guidedSetupUrl = $isHomePage ? '#guided-setup' : url('/#guided-setup');

    $navigationLinks = [
        ['label' => 'Features', 'url' => url('/features'), 'active' => $isFeaturesPage],
        ['label' => 'Pricing', 'url' => url('/pricing'), 'active' => $isPricingPage],
        ['label' => 'Changelog', 'url' => route('changelog'), 'active' => $isChangelogPage],
        ['label' => 'Contact', 'url' => route('contact.show'), 'active' => $isContactPage],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('meta_description', 'SkyBase Cloud - Cloud operations for MikroTik ISPs.')">
    <meta name="keywords" content="@yield('meta_keywords', 'ISP management software, MikroTik management, RADIUS server')">
    <meta name="author" content="SkyBase Cloud">

    <meta property="og:title" content="@yield('og_title', 'SkyBase Cloud')">
    <meta property="og:description" content="@yield('og_description', 'SkyBase Cloud - Cloud operations for MikroTik ISPs.')">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="@yield('og_image', asset('images/product/dashboard.webp'))">
    <meta property="og:site_name" content="SkyBase Cloud">

    <link rel="canonical" href="{{ $canonicalUrl }}">

    <title>@yield('title', 'SkyBase Cloud')</title>

    <link rel="preload" href="{{ Vite::asset('resources/css/fonts/space-grotesk-latin.woff2') }}" as="font" type="font/woff2" crossorigin>

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    @vite(['resources/css/marketing.css', 'resources/js/marketing.js'])
    @stack('styles')
</head>
<body data-marketing-page="{{ request()->route()?->getName() }}" data-lead-submitted="{{ session('demo_request_success') ? 'guided_setup' : (session('contact_success') ? 'contact' : '') }}" class="@yield('body_class', 'bg-[#fffdf8] text-[#17211f]')">
    <nav
        x-data="{ mobileMenuOpen: false }"
        x-on:keydown.escape.window="mobileMenuOpen = false"
        class="sticky top-0 z-50 border-b border-[#17211f]/10 bg-[#fffdf8]/95 backdrop-blur"
        aria-label="Primary navigation"
    >
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="flex min-h-20 items-center justify-between gap-5">
                <a href="{{ route('home') }}" class="flex items-center focus:outline-none focus:ring-2 focus:ring-[#145a5a] focus:ring-offset-4" aria-label="SkyBase Cloud home">
                    <img src="{{ asset('assets/images/logo/logo-black.png') }}" width="300" height="75" class="h-9 w-auto" alt="SkyBase Cloud">
                </a>

                <div class="hidden items-center gap-7 md:flex">
                    @foreach($navigationLinks as $navigationLink)
                        <a
                            href="{{ $navigationLink['url'] }}"
                            @class([
                                'border-b-2 py-2 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-[#145a5a] focus:ring-offset-4 motion-reduce:transition-none',
                                'border-[#145a5a] text-[#0d2f35]' => $navigationLink['active'],
                                'border-transparent text-[#52605d] hover:text-[#0d2f35]' => ! $navigationLink['active'],
                            ])
                        >
                            {{ $navigationLink['label'] }}
                        </a>
                    @endforeach
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('auth.login') }}" class="hidden px-3 py-2.5 text-sm font-semibold text-[#52605d] transition hover:text-[#0d2f35] focus:outline-none focus:ring-2 focus:ring-[#145a5a] focus:ring-offset-2 lg:inline-flex motion-reduce:transition-none">Log in</a>
                    <a href="{{ $guidedSetupUrl }}" class="hidden rounded-xl bg-[#f5c542] px-5 py-3 text-sm font-semibold text-[#17211f] ring-1 ring-[#17211f]/10 transition hover:bg-[#ffd75c] focus:outline-none focus:ring-2 focus:ring-[#145a5a] focus:ring-offset-2 sm:inline-flex motion-reduce:transition-none">Book a guided setup</a>
                    <button
                        type="button"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-[#17211f]/15 bg-white text-[#17211f] transition hover:border-[#17211f]/30 focus:outline-none focus:ring-2 focus:ring-[#145a5a] focus:ring-offset-2 md:hidden motion-reduce:transition-none"
                        x-on:click="mobileMenuOpen = ! mobileMenuOpen"
                        x-bind:aria-expanded="mobileMenuOpen.toString()"
                        aria-controls="mobile-navigation"
                        aria-label="Toggle navigation menu"
                    >
                        <svg x-show="! mobileMenuOpen" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                        </svg>
                        <svg x-show="mobileMenuOpen" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
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
                <div class="rounded-xl border border-[#17211f]/15 bg-white p-3 shadow-lg">
                    <div class="grid gap-1">
                        @foreach($navigationLinks as $navigationLink)
                            <a
                                x-on:click="mobileMenuOpen = false"
                                href="{{ $navigationLink['url'] }}"
                                @class([
                                    'rounded-lg px-4 py-3 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-[#145a5a] motion-reduce:transition-none',
                                    'bg-[#0d2f35] text-white' => $navigationLink['active'],
                                    'text-[#52605d] hover:bg-[#f7f3ea] hover:text-[#0d2f35]' => ! $navigationLink['active'],
                                ])
                            >
                                {{ $navigationLink['label'] }}
                            </a>
                        @endforeach
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-3 border-t border-[#17211f]/10 pt-3">
                        <a x-on:click="mobileMenuOpen = false" href="{{ route('auth.login') }}" class="inline-flex items-center justify-center rounded-xl border border-[#17211f]/15 px-4 py-3 text-sm font-semibold text-[#17211f]">Log in</a>
                        <a x-on:click="mobileMenuOpen = false" href="{{ $guidedSetupUrl }}" class="inline-flex items-center justify-center rounded-xl bg-[#f5c542] px-4 py-3 text-center text-sm font-semibold text-[#17211f]">Guided setup</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    @yield('content')

    <x-marketing-footer />

    @stack('scripts')
</body>
</html>
