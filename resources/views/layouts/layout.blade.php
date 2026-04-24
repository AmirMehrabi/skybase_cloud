@php
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
    <nav class="sticky top-0 z-50 border-b border-gray-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-20 items-center justify-between">
                <div class="flex items-center">
                    <a href="{{ url('/') }}" class="text-3xl font-bold text-gray-900">
                        <img src="{{ asset('assets/images/logo/logo-black.png') }}" class="max-w-36" alt="SkyBase Cloud logo">
                    </a>
                </div>

                <div class="hidden items-center gap-10 md:flex">
                    <a href="{{ url('/features') }}" class="text-lg font-medium {{ $isFeaturesPage ? 'text-blue-600' : 'text-gray-600 hover:text-gray-900' }}">Features</a>
                    <a href="{{ url('/pricing') }}" class="text-lg font-medium {{ $isPricingPage ? 'text-blue-600' : 'text-gray-600 hover:text-gray-900' }}">Pricing</a>
                    <a href="{{ url('/') }}#docs" class="text-lg font-medium text-gray-600 hover:text-gray-900">Docs</a>
                    <a href="{{ url('/') }}#about" class="text-lg font-medium text-gray-600 hover:text-gray-900">About</a>
                </div>

                <div class="flex items-center gap-4">
                    <a href="{{ route('auth.login') }}" class="hidden items-center justify-center rounded-2xl border border-gray-300 bg-white px-6 py-3 text-lg font-medium text-gray-700 transition-colors hover:bg-gray-50 sm:inline-flex">Login</a>
                    <a href="{{ route('auth.register') }}" class="inline-flex items-center justify-center rounded-2xl bg-blue-600 px-6 py-3 text-lg font-medium text-white transition-colors hover:bg-blue-700">Register</a>
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
