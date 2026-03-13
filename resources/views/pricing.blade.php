<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Explore SkyBase pricing for ISP management software. Start free with our cloud platform or deploy on-premise for full infrastructure control.">
    <meta name="keywords" content="ISP pricing, MikroTik pricing, WISP pricing, cloud ISP management, on-premise ISP software">
    <meta name="author" content="SkyBase Cloud">

    <!-- Open Graph -->
    <meta property="og:title" content="SkyBase Pricing | Cloud & On-Premise ISP Management Platform">
    <meta property="og:description" content="Explore SkyBase pricing for ISP management software. Start free with our cloud platform or deploy on-premise for full infrastructure control.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/pricing') }}">
    <meta property="og:image" content="{{ asset('images/og-image.png') }}">

    <title>SkyBase Pricing | Cloud & On-Premise ISP Management Platform</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Manrope', sans-serif;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Space Grotesk', sans-serif;
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white">
    <!-- Navbar -->
    <nav class="border-b border-gray-200 bg-white sticky top-0 z-50">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="{{ url('/') }}" class="text-3xl font-bold text-gray-900">SkyBase Cloud</a>
                </div>

                <!-- Center Links (Desktop) -->
                <div class="hidden md:flex items-center gap-10">
                    <a href="{{ url('/') }}#features" class="text-gray-600 hover:text-gray-900 text-lg font-medium">Features</a>
                    <a href="{{ url('/pricing') }}" class="text-blue-600 text-lg font-medium">Pricing</a>
                    <a href="{{ url('/') }}#docs" class="text-gray-600 hover:text-gray-900 text-lg font-medium">Docs</a>
                    <a href="{{ url('/') }}#about" class="text-gray-600 hover:text-gray-900 text-lg font-medium">About</a>
                </div>

                <!-- Right Buttons -->
                <div class="flex items-center gap-4">
                    <a href="{{ route('auth.login') }}" class="hidden sm:inline-flex items-center justify-center px-6 py-3 text-lg font-medium text-gray-700 bg-white border border-gray-300 rounded-2xl hover:bg-gray-50 transition-colors">Login</a>
                    <a href="{{ route('auth.register') }}" class="inline-flex items-center justify-center px-6 py-3 text-lg font-medium text-white bg-blue-600 rounded-2xl hover:bg-blue-700 transition-colors">Register</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="bg-gray-50 border-b border-gray-200 py-16 sm:py-20">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto text-center">
                <h1 class="text-4xl sm:text-5xl font-bold text-gray-900 leading-tight mb-6">
                    SkyBase Pricing
                </h1>
                <p class="text-xl text-gray-600 mb-4 leading-relaxed">
                    Flexible pricing for ISPs of every size. Start free and scale as your subscriber base grows.
                </p>
                <p class="text-lg text-gray-500">
                    Choose between our fully managed Cloud platform or self-hosted On-Premise deployment.
                </p>
            </div>
        </div>
    </section>

    <!-- Interactive Subscriber Slider Section -->
    <section class="py-16 bg-white" x-data="pricingCalculator()">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">How many subscribers do you manage?</h2>
                    <p class="text-lg text-gray-600">Adjust the slider to see your recommended plan</p>
                </div>

                <!-- Slider -->
                <div class="mb-8">
                    <div class="relative">
                        <input
                            type="range"
                            x-model="subscribers"
                            min="10"
                            max="5000"
                            step="10"
                            class="w-full h-3 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600"
                        >
                    </div>
                    <div class="text-center mt-4">
                        <span class="text-3xl font-bold text-gray-900" x-text="subscribers"></span>
                        <span class="text-xl text-gray-600 ml-2">Subscribers</span>
                    </div>
                </div>

                <!-- Dynamic Pricing Display -->
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-2xl p-8 text-center">
                    <div class="mb-4">
                        <span class="inline-block px-4 py-1 bg-blue-600 text-white rounded-full text-sm font-semibold">Recommended Plan</span>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2" x-text="selectedPlan.name"></h3>
                    <div class="text-5xl font-bold text-blue-600 mb-2">
                        $<span x-text="selectedPlan.price"></span>
                        <template x-if="selectedPlan.price > 0">
                            <span class="text-2xl text-gray-600"> / month</span>
                        </template>
                    </div>
                    <p class="text-lg text-gray-600 mb-4">
                        For up to <span x-text="selectedPlan.limit"></span> subscribers
                    </p>
                    <template x-if="selectedPlan.price > 0">
                        <p class="text-lg text-gray-700 font-medium">
                            ≈ $<span x-text="perUserCost"></span> per subscriber
                        </p>
                    </template>
                    <template x-if="selectedPlan.price === 0">
                        <p class="text-lg text-gray-700 font-medium">
                            Free forever
                        </p>
                    </template>

                    <!-- CTA Button -->
                    <div class="mt-8">
                        <a href="{{ route('auth.register') }}" class="inline-flex items-center justify-center px-10 py-4 text-lg font-semibold text-white bg-blue-600 rounded-2xl hover:bg-blue-700 transition-colors">
                            Start Free Trial
                        </a>
                        <p class="text-sm text-gray-500 mt-3">No contracts • Cancel anytime • No setup fees</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Cloud Pricing Cards -->
    <section class="py-16 bg-gray-50">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Cloud Pricing</h2>
                <p class="text-lg text-gray-600">All plans include cloud hosting, automatic updates, and core features</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-7xl mx-auto">
                <!-- Free Plan -->
                <div class="bg-white border border-gray-200 rounded-xl p-8 shadow-sm hover:shadow-lg transition">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Free</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-gray-900">$0</span>
                        <span class="text-gray-600"> / month</span>
                    </div>
                    <p class="text-gray-600 mb-6">Perfect for small ISPs getting started</p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Up to 40 subscribers</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Cloud hosting</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Automatic updates</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Basic support</span>
                        </li>
                    </ul>
                    <a href="{{ route('auth.register') }}" class="block w-full text-center px-6 py-3 text-lg font-semibold text-white bg-gray-900 rounded-xl hover:bg-gray-800 transition-colors">
                        Start Free
                    </a>
                </div>

                <!-- Starter Plan -->
                <div class="bg-white border border-gray-200 rounded-xl p-8 shadow-sm hover:shadow-lg transition">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Starter</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-gray-900">$69</span>
                        <span class="text-gray-600"> / month</span>
                    </div>
                    <p class="text-gray-600 mb-6">For growing ISPs</p>
                    <p class="text-sm text-gray-500 mb-4">≈ $0.46 per subscriber</p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Up to 150 subscribers</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Everything in Free</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Priority support</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Advanced monitoring</span>
                        </li>
                    </ul>
                    <a href="{{ route('auth.register') }}" class="block w-full text-center px-6 py-3 text-lg font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors">
                        Start Free Trial
                    </a>
                </div>

                <!-- Growth Plan (Most Popular) -->
                <div class="bg-white border-2 border-indigo-500 rounded-xl p-8 shadow-lg hover:shadow-xl transition scale-105 relative">
                    <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                        <span class="inline-block px-4 py-1 bg-indigo-600 text-white rounded-full text-sm font-semibold">Most Popular</span>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2 mt-2">Growth</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-gray-900">$129</span>
                        <span class="text-gray-600"> / month</span>
                    </div>
                    <p class="text-gray-600 mb-6">Best value for scaling ISPs</p>
                    <p class="text-sm text-gray-500 mb-4">≈ $0.43 per subscriber</p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Up to 300 subscribers</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Everything in Starter</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>API access</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Custom integrations</span>
                        </li>
                    </ul>
                    <a href="{{ route('auth.register') }}" class="block w-full text-center px-6 py-3 text-lg font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition-colors">
                        Start Free Trial
                    </a>
                </div>

                <!-- Scale Plan -->
                <div class="bg-white border border-gray-200 rounded-xl p-8 shadow-sm hover:shadow-lg transition">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Scale</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-gray-900">$239</span>
                        <span class="text-gray-600"> / month</span>
                    </div>
                    <p class="text-gray-600 mb-6">For established ISPs</p>
                    <p class="text-sm text-gray-500 mb-4">≈ $0.40 per subscriber</p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Up to 600 subscribers</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Everything in Growth</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Dedicated support</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Advanced reporting</span>
                        </li>
                    </ul>
                    <a href="{{ route('auth.register') }}" class="block w-full text-center px-6 py-3 text-lg font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors">
                        Start Free Trial
                    </a>
                </div>

                <!-- Business Plan -->
                <div class="bg-white border border-gray-200 rounded-xl p-8 shadow-sm hover:shadow-lg transition">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Business</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-gray-900">$399</span>
                        <span class="text-gray-600"> / month</span>
                    </div>
                    <p class="text-gray-600 mb-6">For large operations</p>
                    <p class="text-sm text-gray-500 mb-4">≈ $0.33 per subscriber</p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Up to 1,200 subscribers</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Everything in Scale</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Account manager</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Custom training</span>
                        </li>
                    </ul>
                    <a href="{{ route('auth.register') }}" class="block w-full text-center px-6 py-3 text-lg font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors">
                        Start Free Trial
                    </a>
                </div>

                <!-- Carrier Plan -->
                <div class="bg-white border border-gray-200 rounded-xl p-8 shadow-sm hover:shadow-lg transition">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Carrier</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-gray-900">$749</span>
                        <span class="text-gray-600"> / month</span>
                    </div>
                    <p class="text-gray-600 mb-6">For carrier-grade networks</p>
                    <p class="text-sm text-gray-500 mb-4">≈ $0.31 per subscriber</p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Up to 2,400 subscribers</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Everything in Business</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>24/7 priority support</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>SLA guarantee</span>
                        </li>
                    </ul>
                    <a href="{{ route('auth.register') }}" class="block w-full text-center px-6 py-3 text-lg font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors">
                        Start Free Trial
                    </a>
                </div>
            </div>

            <!-- Enterprise CTA -->
            <div class="mt-12 text-center">
                <p class="text-lg text-gray-600 mb-4">Need more than 2,400 subscribers?</p>
                <a href="mailto:sales@skybase.cloud" class="inline-flex items-center justify-center px-8 py-3 text-lg font-semibold text-blue-600 bg-blue-50 rounded-xl hover:bg-blue-100 transition-colors">
                    Contact Sales for Enterprise Pricing
                </a>
            </div>
        </div>
    </section>

    <!-- Cloud vs On-Premise Section -->
    <section class="py-16 bg-white">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Choose Your Deployment</h2>
                <p class="text-lg text-gray-600">Select the option that best fits your infrastructure needs</p>
            </div>

            <div class="grid md:grid-cols-2 gap-8 max-w-6xl mx-auto">
                <!-- Cloud -->
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-2xl p-8">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900">SkyBase Cloud</h3>
                    </div>
                    <p class="text-gray-600 mb-6 text-lg">
                        SkyBase Cloud is fully managed by our team. No infrastructure setup is required and updates are applied automatically.
                    </p>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3 text-gray-700">
                            <svg class="w-6 h-6 text-blue-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>No server management</span>
                        </li>
                        <li class="flex items-start gap-3 text-gray-700">
                            <svg class="w-6 h-6 text-blue-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Automatic updates</span>
                        </li>
                        <li class="flex items-start gap-3 text-gray-700">
                            <svg class="w-6 h-6 text-blue-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Secure hosting</span>
                        </li>
                        <li class="flex items-start gap-3 text-gray-700">
                            <svg class="w-6 h-6 text-blue-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Fast deployment</span>
                        </li>
                        <li class="flex items-start gap-3 text-gray-700">
                            <svg class="w-6 h-6 text-blue-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Best for growing ISPs</span>
                        </li>
                    </ul>
                </div>

                <!-- On-Premise -->
                <div class="bg-gradient-to-br from-gray-50 to-slate-50 border border-gray-200 rounded-2xl p-8">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-16 h-16 bg-gray-700 rounded-2xl flex items-center justify-center">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900">SkyBase On-Premise</h3>
                    </div>
                    <p class="text-gray-600 mb-6 text-lg">
                        Run SkyBase on your own infrastructure for full control and customization.
                    </p>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3 text-gray-700">
                            <svg class="w-6 h-6 text-gray-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Self-hosted deployment</span>
                        </li>
                        <li class="flex items-start gap-3 text-gray-700">
                            <svg class="w-6 h-6 text-gray-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Full infrastructure control</span>
                        </li>
                        <li class="flex items-start gap-3 text-gray-700">
                            <svg class="w-6 h-6 text-gray-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Custom integrations</span>
                        </li>
                        <li class="flex items-start gap-3 text-gray-700">
                            <svg class="w-6 h-6 text-gray-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Internal data hosting</span>
                        </li>
                        <li class="flex items-start gap-3 text-gray-700">
                            <svg class="w-6 h-6 text-gray-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Best for enterprise ISPs</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- On-Premise Pricing Cards -->
    <section class="py-16 bg-gray-50">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">On-Premise Pricing</h2>
                <p class="text-lg text-gray-600">Deploy SkyBase on your own infrastructure</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-7xl mx-auto">
                <!-- Basic Plan -->
                <div class="bg-white border border-gray-200 rounded-xl p-8 shadow-sm hover:shadow-lg transition">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Basic</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-gray-900">$105</span>
                        <span class="text-gray-600"> / month</span>
                    </div>
                    <p class="text-gray-600 mb-6">For small deployments</p>
                    <p class="text-sm text-gray-500 mb-4">≈ $0.70 per subscriber</p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Up to 150 users</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>No setup fees</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Self-hosted deployment</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Full platform features</span>
                        </li>
                    </ul>
                    <a href="mailto:sales@skybase.cloud" class="block w-full text-center px-6 py-3 text-lg font-semibold text-white bg-gray-700 rounded-xl hover:bg-gray-800 transition-colors">
                        Contact Sales
                    </a>
                </div>

                <!-- Standard Plan (Most Popular) -->
                <div class="bg-white border-2 border-indigo-500 rounded-xl p-8 shadow-lg hover:shadow-xl transition scale-105 relative">
                    <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                        <span class="inline-block px-4 py-1 bg-indigo-600 text-white rounded-full text-sm font-semibold">Most Popular</span>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2 mt-2">Standard</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-gray-900">$195</span>
                        <span class="text-gray-600"> / month</span>
                    </div>
                    <p class="text-gray-600 mb-6">Best value for teams</p>
                    <p class="text-sm text-gray-500 mb-4">≈ $0.65 per subscriber</p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Up to 300 users</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Everything in Basic</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Direct support</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Deployment assistance</span>
                        </li>
                    </ul>
                    <a href="mailto:sales@skybase.cloud" class="block w-full text-center px-6 py-3 text-lg font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition-colors">
                        Contact Sales
                    </a>
                </div>

                <!-- Advanced Plan -->
                <div class="bg-white border border-gray-200 rounded-xl p-8 shadow-sm hover:shadow-lg transition">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Advanced</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-gray-900">$360</span>
                        <span class="text-gray-600"> / month</span>
                    </div>
                    <p class="text-gray-600 mb-6">For scaling operations</p>
                    <p class="text-sm text-gray-500 mb-4">≈ $0.60 per subscriber</p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Up to 600 users</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Everything in Standard</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Priority support</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Custom configuration</span>
                        </li>
                    </ul>
                    <a href="mailto:sales@skybase.cloud" class="block w-full text-center px-6 py-3 text-lg font-semibold text-white bg-gray-700 rounded-xl hover:bg-gray-800 transition-colors">
                        Contact Sales
                    </a>
                </div>

                <!-- Professional Plan -->
                <div class="bg-white border border-gray-200 rounded-xl p-8 shadow-sm hover:shadow-lg transition">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Professional</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-gray-900">$600</span>
                        <span class="text-gray-600"> / month</span>
                    </div>
                    <p class="text-gray-600 mb-6">For large teams</p>
                    <p class="text-sm text-gray-500 mb-4">≈ $0.50 per subscriber</p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Up to 1,200 users</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Everything in Advanced</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Dedicated support</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>On-site training</span>
                        </li>
                    </ul>
                    <a href="mailto:sales@skybase.cloud" class="block w-full text-center px-6 py-3 text-lg font-semibold text-white bg-gray-700 rounded-xl hover:bg-gray-800 transition-colors">
                        Contact Sales
                    </a>
                </div>

                <!-- Premium Plan -->
                <div class="bg-white border border-gray-200 rounded-xl p-8 shadow-sm hover:shadow-lg transition">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Premium</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-gray-900">$1,080</span>
                        <span class="text-gray-600"> / month</span>
                    </div>
                    <p class="text-gray-600 mb-6">For enterprise needs</p>
                    <p class="text-sm text-gray-500 mb-4">≈ $0.45 per subscriber</p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Up to 2,400 users</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Everything in Professional</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>24/7 support</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>SLA guarantee</span>
                        </li>
                    </ul>
                    <a href="mailto:sales@skybase.cloud" class="block w-full text-center px-6 py-3 text-lg font-semibold text-white bg-gray-700 rounded-xl hover:bg-gray-800 transition-colors">
                        Contact Sales
                    </a>
                </div>

                <!-- Enterprise Plan -->
                <div class="bg-gradient-to-br from-gray-800 to-gray-900 border border-gray-700 rounded-xl p-8 shadow-sm hover:shadow-lg transition">
                    <h3 class="text-2xl font-bold text-white mb-2">Enterprise</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-white">Custom</span>
                    </div>
                    <p class="text-gray-300 mb-6">For carrier-grade networks</p>
                    <p class="text-sm text-gray-400 mb-4">Unlimited scalability</p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start gap-2 text-gray-300">
                            <svg class="w-5 h-5 text-blue-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Unlimited users</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-300">
                            <svg class="w-5 h-5 text-blue-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Everything in Premium</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-300">
                            <svg class="w-5 h-5 text-blue-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Custom development</span>
                        </li>
                        <li class="flex items-start gap-2 text-gray-300">
                            <svg class="w-5 h-5 text-blue-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Dedicated infrastructure</span>
                        </li>
                    </ul>
                    <a href="mailto:sales@skybase.cloud" class="block w-full text-center px-6 py-3 text-lg font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors">
                        Contact Sales
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Feature Comparison Table -->
    <section class="py-16 bg-white">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Cloud vs On-Premise</h2>
                <p class="text-lg text-gray-600">Compare deployment options</p>
            </div>

            <div class="max-w-4xl mx-auto">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b-2 border-gray-200">
                                <th class="text-left py-4 px-6 text-gray-900 font-semibold">Feature</th>
                                <th class="text-center py-4 px-6 text-blue-600 font-semibold">Cloud</th>
                                <th class="text-center py-4 px-6 text-gray-700 font-semibold">On-Premise</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b border-gray-100">
                                <td class="py-4 px-6 text-gray-900 font-medium">Hosting</td>
                                <td class="text-center py-4 px-6 text-gray-600">SkyBase</td>
                                <td class="text-center py-4 px-6 text-gray-600">Your Infrastructure</td>
                            </tr>
                            <tr class="border-b border-gray-100 bg-gray-50">
                                <td class="py-4 px-6 text-gray-900 font-medium">Updates</td>
                                <td class="text-center py-4 px-6 text-gray-600">Automatic</td>
                                <td class="text-center py-4 px-6 text-gray-600">Manual</td>
                            </tr>
                            <tr class="border-b border-gray-100">
                                <td class="py-4 px-6 text-gray-900 font-medium">Server Maintenance</td>
                                <td class="text-center py-4 px-6 text-gray-600">SkyBase</td>
                                <td class="text-center py-4 px-6 text-gray-600">Customer</td>
                            </tr>
                            <tr class="border-b border-gray-100 bg-gray-50">
                                <td class="py-4 px-6 text-gray-900 font-medium">Setup Time</td>
                                <td class="text-center py-4 px-6 text-gray-600">Minutes</td>
                                <td class="text-center py-4 px-6 text-gray-600">Depends on server</td>
                            </tr>
                            <tr class="border-b border-gray-100">
                                <td class="py-4 px-6 text-gray-900 font-medium">Customization</td>
                                <td class="text-center py-4 px-6 text-gray-600">Limited</td>
                                <td class="text-center py-4 px-6 text-gray-600">Full control</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="py-4 px-6 text-gray-900 font-medium">Best For</td>
                                <td class="text-center py-4 px-6 text-gray-600">Fast deployment</td>
                                <td class="text-center py-4 px-6 text-gray-600">Infrastructure control</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-16 bg-gray-50" x-data="{ openFaq: null }">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Frequently Asked Questions</h2>
                <p class="text-lg text-gray-600">Find answers to common questions about SkyBase pricing</p>
            </div>

            <div class="max-w-3xl mx-auto space-y-4">
                <!-- FAQ 1 -->
                <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                    <button
                        @click="openFaq = openFaq === 1 ? null : 1"
                        class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors"
                    >
                        <span class="font-semibold text-gray-900">Can I upgrade my plan later?</span>
                        <svg class="w-5 h-5 text-gray-500 transition-transform" :class="{ 'rotate-180': openFaq === 1 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="openFaq === 1" x-transition class="px-6 pb-4 text-gray-600">
                        Yes, you can upgrade at any time. When you upgrade, you'll get immediate access to all features in your new plan, and we'll prorate your billing accordingly.
                    </div>
                </div>

                <!-- FAQ 2 -->
                <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                    <button
                        @click="openFaq = openFaq === 2 ? null : 2"
                        class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors"
                    >
                        <span class="font-semibold text-gray-900">Is there a setup fee?</span>
                        <svg class="w-5 h-5 text-gray-500 transition-transform" :class="{ 'rotate-180': openFaq === 2 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="openFaq === 2" x-transition class="px-6 pb-4 text-gray-600">
                        No. SkyBase does not charge setup fees. You can start your free trial immediately without any upfront costs.
                    </div>
                </div>

                <!-- FAQ 3 -->
                <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                    <button
                        @click="openFaq = openFaq === 3 ? null : 3"
                        class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors"
                    >
                        <span class="font-semibold text-gray-900">Do you support MikroTik?</span>
                        <svg class="w-5 h-5 text-gray-500 transition-transform" :class="{ 'rotate-180': openFaq === 3 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="openFaq === 3" x-transition class="px-6 pb-4 text-gray-600">
                        Yes. SkyBase integrates seamlessly with MikroTik for PPPoE, Hotspot, and RADIUS authentication. We're built specifically for MikroTik-based networks.
                    </div>
                </div>

                <!-- FAQ 4 -->
                <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                    <button
                        @click="openFaq = openFaq === 4 ? null : 4"
                        class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors"
                    >
                        <span class="font-semibold text-gray-900">Can I migrate from On-Premise to Cloud?</span>
                        <svg class="w-5 h-5 text-gray-500 transition-transform" :class="{ 'rotate-180': openFaq === 4 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="openFaq === 4" x-transition class="px-6 pb-4 text-gray-600">
                        Yes. Migration assistance is available if you want to move from On-Premise to Cloud. Our team can help you seamlessly transition your data and configuration.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Final CTA Section -->
    <section class="py-20 bg-blue-600">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl mx-auto text-center">
                <h2 class="text-3xl font-bold text-white mb-4">Start Managing Your ISP with SkyBase</h2>
                <p class="text-xl text-blue-100 mb-8">
                    Deploy in minutes with our Cloud platform or host SkyBase on your own infrastructure.
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="{{ route('auth.register') }}" class="inline-flex items-center justify-center px-10 py-4 text-base font-semibold text-white bg-white rounded-2xl hover:bg-gray-50 transition-colors">
                        Start Free Trial
                    </a>
                    <a href="mailto:sales@skybase.cloud" class="inline-flex items-center justify-center px-10 py-4 text-base font-semibold text-white border-2 border-white rounded-2xl hover:bg-white hover:text-blue-600 transition-colors">
                        Contact Sales
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400 py-12">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-8 mb-8">
                <!-- Brand -->
                <div class="col-span-2">
                    <h3 class="text-white font-bold text-lg mb-4">SkyBase Cloud</h3>
                    <p class="text-lg">Cloud-based ISP management platform for MikroTik networks.</p>
                </div>

                <!-- Product -->
                <div>
                    <h4 class="text-white font-semibold mb-3">Product</h4>
                    <ul class="space-y-2 text-lg">
                        <li><a href="{{ url('/') }}#features" class="hover:text-white">Features</a></li>
                        <li><a href="{{ url('/pricing') }}" class="hover:text-white">Pricing</a></li>
                        <li><a href="{{ url('/') }}#docs" class="hover:text-white">Documentation</a></li>
                        <li><a href="#" class="hover:text-white">Changelog</a></li>
                    </ul>
                </div>

                <!-- Company -->
                <div>
                    <h4 class="text-white font-semibold mb-3">Company</h4>
                    <ul class="space-y-2 text-lg">
                        <li><a href="{{ url('/') }}#about" class="hover:text-white">About</a></li>
                        <li><a href="#" class="hover:text-white">Contact</a></li>
                        <li><a href="#" class="hover:text-white">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-white">Terms of Service</a></li>
                    </ul>
                </div>

                <!-- Resources -->
                <div>
                    <h4 class="text-white font-semibold mb-3">Resources</h4>
                    <ul class="space-y-2 text-lg">
                        <li><a href="#" class="hover:text-white">ISP Management Guide</a></li>
                        <li><a href="#" class="hover:text-white">MikroTik Integration</a></li>
                        <li><a href="#" class="hover:text-white">Radius Authentication</a></li>
                    </ul>
                </div>
            </div>

            <!-- Contact & Bottom -->
            <div class="pt-8 border-t border-gray-800">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="text-lg">
                        <a href="mailto:support@skybase.cloud" class="hover:text-white">support@skybase.cloud</a>
                        <span class="mx-2">·</span>
                        <a href="https://skybase.cloud" class="hover:text-white">skybase.cloud</a>
                    </div>
                    <p class="text-lg">© 2026 SkyBase Cloud. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Alpine.js Pricing Calculator -->
    <script>
        function pricingCalculator() {
            return {
                subscribers: 150,
                plans: [
                    { name: 'Free', limit: 40, price: 0 },
                    { name: 'Starter', limit: 150, price: 69 },
                    { name: 'Growth', limit: 300, price: 129 },
                    { name: 'Scale', limit: 600, price: 239 },
                    { name: 'Business', limit: 1200, price: 399 },
                    { name: 'Carrier', limit: 2400, price: 749 }
                ],
                get selectedPlan() {
                    // Find the first plan where subscribers <= limit
                    const plan = this.plans.find(p => this.subscribers <= p.limit);
                    return plan || { name: 'Enterprise', limit: 5000, price: 1300 };
                },
                get perUserCost() {
                    if (this.selectedPlan.price === 0) return '0.00';
                    return (this.selectedPlan.price / this.selectedPlan.limit).toFixed(2);
                }
            }
        }
    </script>
</body>
</html>
