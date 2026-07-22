@extends('layouts.layout')

@section('title', 'SkyBase Pricing | Cloud & On-Premise ISP Management Platform')
@section('meta_description', 'Explore SkyBase pricing for ISP management software. Start free with our cloud platform or deploy on-premise for full infrastructure control.')
@section('meta_keywords', 'ISP pricing, MikroTik pricing, WISP pricing, cloud ISP management, on-premise ISP software')
@section('og_title', 'SkyBase Pricing | Cloud & On-Premise ISP Management Platform')
@section('og_description', 'Explore SkyBase pricing for ISP management software. Start free with our cloud platform or deploy on-premise for full infrastructure control.')
@section('og_url', url('/pricing'))
@section('body_class', 'bg-[#f6f1e8] text-slate-950')

@section('content')
<div x-data="demoRequestModal()">
<!-- Hero Section -->
    <section class="relative isolate overflow-hidden bg-[#0d2f35] py-16 text-white sm:py-20">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_18%_20%,rgba(34,197,94,0.26),transparent_28%),radial-gradient(circle_at_85%_10%,rgba(245,197,66,0.22),transparent_30%),linear-gradient(135deg,#09252b_0%,#0d2f35_52%,#123f3d_100%)]"></div>
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto text-center">
                <p class="mb-4 text-sm font-bold uppercase tracking-[0.24em] text-[#f5c542]">Simple cloud pricing</p>
                <h1 class="text-4xl sm:text-5xl font-bold text-white leading-tight mb-6">
                    SkyBase Pricing
                </h1>
                <p class="text-xl text-teal-50/85 mb-4 leading-relaxed">
                    Flexible pricing for ISPs of every size. Start free and scale as your subscriber base grows.
                </p>
                <p class="text-lg text-teal-50/70">
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
                    <h2 class="text-3xl font-bold text-slate-950 mb-4">How many subscribers do you manage?</h2>
                    <p class="text-lg text-slate-600">Adjust the slider to see your recommended plan</p>
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
                            class="w-full h-3 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-emerald-600"
                        >
                    </div>
                    <div class="text-center mt-4">
                        <span class="text-3xl font-bold text-slate-950" x-text="subscribers"></span>
                        <span class="text-xl text-slate-600 ml-2">Subscribers</span>
                    </div>
                </div>

                <!-- Dynamic Pricing Display -->
                <div class="bg-gradient-to-br from-[#fbf7ed] to-white border border-emerald-200 rounded-2xl p-8 text-center">
                    <div class="mb-4">
                        <span class="inline-block px-4 py-1 bg-[#0d2f35] text-white rounded-full text-sm font-semibold">Recommended Plan</span>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-950 mb-2" x-text="selectedPlan.name"></h3>
                    <div class="text-5xl font-bold text-teal-700 mb-2">
                        $<span x-text="selectedPlan.price"></span>
                        <template x-if="selectedPlan.price > 0">
                            <span class="text-2xl text-slate-600"> / month</span>
                        </template>
                    </div>
                    <p class="text-lg text-slate-600 mb-4">
                        For up to <span x-text="selectedPlan.limit"></span> subscribers
                    </p>
                    <template x-if="selectedPlan.price > 0">
                        <p class="text-lg text-slate-700 font-medium">
                            ≈ $<span x-text="perUserCost"></span> per subscriber
                        </p>
                    </template>
                    <template x-if="selectedPlan.price === 0">
                        <p class="text-lg text-slate-700 font-medium">
                            Free forever
                        </p>
                    </template>

                    <!-- CTA Button -->
                    <div class="mt-8">
                        <a href="{{ route('auth.register') }}" class="inline-flex items-center justify-center px-10 py-4 text-lg font-semibold text-white bg-[#0d2f35] rounded-2xl hover:bg-[#123f3d] transition-colors">
                            Start Free
                        </a>
                        <p class="text-sm text-slate-500 mt-3">No contracts • Cancel anytime • No setup fees</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Cloud Pricing Cards -->
    <section class="py-16 bg-[#f6f1e8]">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-slate-950 mb-4">Cloud Pricing</h2>
                <p class="text-lg text-slate-600">All plans include cloud hosting, automatic updates, and core features</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-7xl mx-auto">
                <!-- Free Plan -->
                <div class="bg-white border border-slate-950/10 rounded-2xl p-8 shadow-sm hover:shadow-lg transition">
                    <h3 class="text-2xl font-bold text-slate-950 mb-2">Free</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-slate-950">$0</span>
                        <span class="text-slate-600"> / month</span>
                    </div>
                    <p class="text-slate-600 mb-6">Perfect for small ISPs getting started</p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Up to 40 subscribers</span>
                        </li>
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Cloud hosting</span>
                        </li>
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Automatic updates</span>
                        </li>
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Basic support</span>
                        </li>
                    </ul>
                    <a href="{{ route('auth.register') }}" class="block w-full text-center px-6 py-3 text-lg font-semibold text-white bg-slate-950 rounded-2xl hover:bg-slate-800 transition-colors">
                        Start Free
                    </a>
                </div>

                <!-- Starter Plan -->
                <div class="bg-white border border-slate-950/10 rounded-2xl p-8 shadow-sm hover:shadow-lg transition">
                    <h3 class="text-2xl font-bold text-slate-950 mb-2">Starter</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-slate-950">$69</span>
                        <span class="text-slate-600"> / month</span>
                    </div>
                    <p class="text-slate-600 mb-6">For growing ISPs</p>
                    <p class="text-sm text-slate-500 mb-4">≈ $0.46 per subscriber</p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Up to 150 subscribers</span>
                        </li>
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Everything in Free</span>
                        </li>
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Priority support</span>
                        </li>
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Advanced monitoring</span>
                        </li>
                    </ul>
                    <a href="{{ route('auth.register') }}" class="block w-full text-center px-6 py-3 text-lg font-semibold text-white bg-[#0d2f35] rounded-2xl hover:bg-[#123f3d] transition-colors">
                        Start Free
                    </a>
                </div>

                <!-- Growth Plan (Most Popular) -->
                <div class="bg-white border-2 border-[#f5c542] rounded-2xl p-8 shadow-lg hover:shadow-xl transition scale-105 relative">
                    <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                        <span class="inline-block px-4 py-1 bg-[#0d2f35] text-white rounded-full text-sm font-semibold">Most Popular</span>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-950 mb-2 mt-2">Growth</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-slate-950">$129</span>
                        <span class="text-slate-600"> / month</span>
                    </div>
                    <p class="text-slate-600 mb-6">Best value for scaling ISPs</p>
                    <p class="text-sm text-slate-500 mb-4">≈ $0.43 per subscriber</p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Up to 300 subscribers</span>
                        </li>
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Everything in Starter</span>
                        </li>
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>API access</span>
                        </li>
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Custom integrations</span>
                        </li>
                    </ul>
                    <a href="{{ route('auth.register') }}" class="block w-full text-center px-6 py-3 text-lg font-semibold text-white bg-[#0d2f35] rounded-2xl hover:bg-[#123f3d] transition-colors">
                        Start Free
                    </a>
                </div>

                <!-- Scale Plan -->
                <div class="bg-white border border-slate-950/10 rounded-2xl p-8 shadow-sm hover:shadow-lg transition">
                    <h3 class="text-2xl font-bold text-slate-950 mb-2">Scale</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-slate-950">$239</span>
                        <span class="text-slate-600"> / month</span>
                    </div>
                    <p class="text-slate-600 mb-6">For established ISPs</p>
                    <p class="text-sm text-slate-500 mb-4">≈ $0.40 per subscriber</p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Up to 600 subscribers</span>
                        </li>
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Everything in Growth</span>
                        </li>
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Dedicated support</span>
                        </li>
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Advanced reporting</span>
                        </li>
                    </ul>
                    <a href="{{ route('auth.register') }}" class="block w-full text-center px-6 py-3 text-lg font-semibold text-white bg-[#0d2f35] rounded-2xl hover:bg-[#123f3d] transition-colors">
                        Start Free
                    </a>
                </div>

                <!-- Business Plan -->
                <div class="bg-white border border-slate-950/10 rounded-2xl p-8 shadow-sm hover:shadow-lg transition">
                    <h3 class="text-2xl font-bold text-slate-950 mb-2">Business</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-slate-950">$399</span>
                        <span class="text-slate-600"> / month</span>
                    </div>
                    <p class="text-slate-600 mb-6">For large operations</p>
                    <p class="text-sm text-slate-500 mb-4">≈ $0.33 per subscriber</p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Up to 1,200 subscribers</span>
                        </li>
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Everything in Scale</span>
                        </li>
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Account manager</span>
                        </li>
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Custom training</span>
                        </li>
                    </ul>
                    <a href="{{ route('auth.register') }}" class="block w-full text-center px-6 py-3 text-lg font-semibold text-white bg-[#0d2f35] rounded-2xl hover:bg-[#123f3d] transition-colors">
                        Start Free
                    </a>
                </div>

                <!-- Carrier Plan -->
                <div class="bg-white border border-slate-950/10 rounded-2xl p-8 shadow-sm hover:shadow-lg transition">
                    <h3 class="text-2xl font-bold text-slate-950 mb-2">Carrier</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-slate-950">$749</span>
                        <span class="text-slate-600"> / month</span>
                    </div>
                    <p class="text-slate-600 mb-6">For carrier-grade networks</p>
                    <p class="text-sm text-slate-500 mb-4">≈ $0.31 per subscriber</p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Up to 2,400 subscribers</span>
                        </li>
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Everything in Business</span>
                        </li>
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Direct founder support</span>
                        </li>
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>SLA guarantee</span>
                        </li>
                    </ul>
                    <a href="{{ route('auth.register') }}" class="block w-full text-center px-6 py-3 text-lg font-semibold text-white bg-[#0d2f35] rounded-2xl hover:bg-[#123f3d] transition-colors">
                        Start Free
                    </a>
                </div>
            </div>

            <!-- Enterprise CTA -->
            <div class="mt-12 text-center">
                <p class="text-lg text-slate-600 mb-4">Need more than 2,400 subscribers?</p>
                <a href="mailto:sales@skybase.app" class="inline-flex items-center justify-center px-8 py-3 text-lg font-semibold text-teal-700 bg-[#fbf7ed] rounded-2xl hover:bg-emerald-50 transition-colors">
                    Contact Sales for Enterprise Pricing
                </a>
            </div>
        </div>
    </section>

    <!-- Cloud vs On-Premise Section -->
    <section class="py-16 bg-white">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-slate-950 mb-4">Choose Your Deployment</h2>
                <p class="text-lg text-slate-600">Select the option that best fits your infrastructure needs</p>
            </div>

            <div class="grid md:grid-cols-2 gap-8 max-w-6xl mx-auto">
                <!-- Cloud -->
                <div class="bg-gradient-to-br from-[#fbf7ed] to-white border border-emerald-200 rounded-2xl p-8">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-16 h-16 bg-[#0d2f35] rounded-2xl flex items-center justify-center">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-slate-950">SkyBase Cloud</h3>
                    </div>
                    <p class="text-slate-600 mb-6 text-lg">
                        SkyBase Cloud is fully managed by our team. No infrastructure setup is required and updates are applied automatically.
                    </p>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3 text-slate-700">
                            <svg class="w-6 h-6 text-teal-700 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>No server management</span>
                        </li>
                        <li class="flex items-start gap-3 text-slate-700">
                            <svg class="w-6 h-6 text-teal-700 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Automatic updates</span>
                        </li>
                        <li class="flex items-start gap-3 text-slate-700">
                            <svg class="w-6 h-6 text-teal-700 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Secure hosting</span>
                        </li>
                        <li class="flex items-start gap-3 text-slate-700">
                            <svg class="w-6 h-6 text-teal-700 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Fast deployment</span>
                        </li>
                        <li class="flex items-start gap-3 text-slate-700">
                            <svg class="w-6 h-6 text-teal-700 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Best for growing ISPs</span>
                        </li>
                    </ul>
                </div>

                <!-- On-Premise -->
                <div class="bg-gradient-to-br from-[#fbf7ed] to-white border border-slate-950/10 rounded-2xl p-8">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-16 h-16 bg-[#172a2c] rounded-2xl flex items-center justify-center">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-slate-950">SkyBase On-Premise</h3>
                    </div>
                    <p class="text-slate-600 mb-6 text-lg">
                        Run SkyBase on your own infrastructure for full control and customization.
                    </p>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3 text-slate-700">
                            <svg class="w-6 h-6 text-slate-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Self-hosted deployment</span>
                        </li>
                        <li class="flex items-start gap-3 text-slate-700">
                            <svg class="w-6 h-6 text-slate-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Full infrastructure control</span>
                        </li>
                        <li class="flex items-start gap-3 text-slate-700">
                            <svg class="w-6 h-6 text-slate-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Custom integrations</span>
                        </li>
                        <li class="flex items-start gap-3 text-slate-700">
                            <svg class="w-6 h-6 text-slate-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Internal data hosting</span>
                        </li>
                        <li class="flex items-start gap-3 text-slate-700">
                            <svg class="w-6 h-6 text-slate-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
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
    <section class="py-16 bg-[#f6f1e8]">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-slate-950 mb-4">On-Premise Pricing</h2>
                <p class="text-lg text-slate-600">Deploy SkyBase on your own infrastructure</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-7xl mx-auto">
                <!-- Basic Plan -->
                <div class="bg-white border border-slate-950/10 rounded-2xl p-8 shadow-sm hover:shadow-lg transition">
                    <h3 class="text-2xl font-bold text-slate-950 mb-2">Basic</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-slate-950">$105</span>
                        <span class="text-slate-600"> / month</span>
                    </div>
                    <p class="text-slate-600 mb-6">For small deployments</p>
                    <p class="text-sm text-slate-500 mb-4">≈ $0.70 per subscriber</p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Up to 150 users</span>
                        </li>
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>No setup fees</span>
                        </li>
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Self-hosted deployment</span>
                        </li>
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Full platform features</span>
                        </li>
                    </ul>
                    <button type="button" @click="open('Basic')" class="block w-full text-center px-6 py-3 text-lg font-semibold text-white bg-[#172a2c] rounded-2xl hover:bg-slate-800 transition-colors">
                        Demo Request
                    </button>
                </div>

                <!-- Standard Plan (Most Popular) -->
                <div class="bg-white border-2 border-[#f5c542] rounded-2xl p-8 shadow-lg hover:shadow-xl transition scale-105 relative">
                    <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                        <span class="inline-block px-4 py-1 bg-[#0d2f35] text-white rounded-full text-sm font-semibold">Most Popular</span>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-950 mb-2 mt-2">Standard</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-slate-950">$195</span>
                        <span class="text-slate-600"> / month</span>
                    </div>
                    <p class="text-slate-600 mb-6">Best value for teams</p>
                    <p class="text-sm text-slate-500 mb-4">≈ $0.65 per subscriber</p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Up to 300 users</span>
                        </li>
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Everything in Basic</span>
                        </li>
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Direct support</span>
                        </li>
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Deployment assistance</span>
                        </li>
                    </ul>
                    <button type="button" @click="open('Standard')" class="block w-full text-center px-6 py-3 text-lg font-semibold text-white bg-[#0d2f35] rounded-2xl hover:bg-[#123f3d] transition-colors">
                        Demo Request
                    </button>
                </div>

                <!-- Advanced Plan -->
                <div class="bg-white border border-slate-950/10 rounded-2xl p-8 shadow-sm hover:shadow-lg transition">
                    <h3 class="text-2xl font-bold text-slate-950 mb-2">Advanced</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-slate-950">$360</span>
                        <span class="text-slate-600"> / month</span>
                    </div>
                    <p class="text-slate-600 mb-6">For scaling operations</p>
                    <p class="text-sm text-slate-500 mb-4">≈ $0.60 per subscriber</p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Up to 600 users</span>
                        </li>
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Everything in Standard</span>
                        </li>
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Priority support</span>
                        </li>
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Custom configuration</span>
                        </li>
                    </ul>
                    <button type="button" @click="open('Advanced')" class="block w-full text-center px-6 py-3 text-lg font-semibold text-white bg-[#172a2c] rounded-2xl hover:bg-slate-800 transition-colors">
                        Demo Request
                    </button>
                </div>

                <!-- Professional Plan -->
                <div class="bg-white border border-slate-950/10 rounded-2xl p-8 shadow-sm hover:shadow-lg transition">
                    <h3 class="text-2xl font-bold text-slate-950 mb-2">Professional</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-slate-950">$600</span>
                        <span class="text-slate-600"> / month</span>
                    </div>
                    <p class="text-slate-600 mb-6">For large teams</p>
                    <p class="text-sm text-slate-500 mb-4">≈ $0.50 per subscriber</p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Up to 1,200 users</span>
                        </li>
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Everything in Advanced</span>
                        </li>
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Dedicated support</span>
                        </li>
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>On-site training</span>
                        </li>
                    </ul>
                    <button type="button" @click="open('Professional')" class="block w-full text-center px-6 py-3 text-lg font-semibold text-white bg-[#172a2c] rounded-2xl hover:bg-slate-800 transition-colors">
                        Demo Request
                    </button>
                </div>

                <!-- Premium Plan -->
                <div class="bg-white border border-slate-950/10 rounded-2xl p-8 shadow-sm hover:shadow-lg transition">
                    <h3 class="text-2xl font-bold text-slate-950 mb-2">Premium</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-slate-950">$1,080</span>
                        <span class="text-slate-600"> / month</span>
                    </div>
                    <p class="text-slate-600 mb-6">For enterprise needs</p>
                    <p class="text-sm text-slate-500 mb-4">≈ $0.45 per subscriber</p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Up to 2,400 users</span>
                        </li>
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Everything in Professional</span>
                        </li>
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Direct founder support</span>
                        </li>
                        <li class="flex items-start gap-2 text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>SLA guarantee</span>
                        </li>
                    </ul>
                    <button type="button" @click="open('Premium')" class="block w-full text-center px-6 py-3 text-lg font-semibold text-white bg-[#172a2c] rounded-2xl hover:bg-slate-800 transition-colors">
                        Demo Request
                    </button>
                </div>

                <!-- Enterprise Plan -->
                <div class="bg-gradient-to-br from-[#172a2c] to-[#0d2f35] border border-white/10 rounded-2xl p-8 shadow-sm hover:shadow-lg transition">
                    <h3 class="text-2xl font-bold text-white mb-2">Enterprise</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-white">Custom</span>
                    </div>
                    <p class="text-teal-50/80 mb-6">For carrier-grade networks</p>
                    <p class="text-sm text-slate-400 mb-4">Unlimited scalability</p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start gap-2 text-teal-50/80">
                            <svg class="w-5 h-5 text-[#f5c542] mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Unlimited users</span>
                        </li>
                        <li class="flex items-start gap-2 text-teal-50/80">
                            <svg class="w-5 h-5 text-[#f5c542] mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Everything in Premium</span>
                        </li>
                        <li class="flex items-start gap-2 text-teal-50/80">
                            <svg class="w-5 h-5 text-[#f5c542] mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Custom development</span>
                        </li>
                        <li class="flex items-start gap-2 text-teal-50/80">
                            <svg class="w-5 h-5 text-[#f5c542] mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Dedicated infrastructure</span>
                        </li>
                    </ul>
                    <button type="button" @click="open('Enterprise')" class="block w-full text-center px-6 py-3 text-lg font-semibold text-white bg-[#0d2f35] rounded-2xl hover:bg-[#123f3d] transition-colors">
                        Demo Request
                    </button>
                </div>
            </div>
        </div>
    </section>

    @if (session('demo_request_success'))
        <section class="bg-emerald-50">
            <div class="mx-auto max-w-7xl px-4 py-4 text-sm font-medium text-emerald-800 sm:px-6 lg:px-8">
                {{ session('demo_request_success') }}
            </div>
        </section>
    @endif

    <!-- Feature Comparison Table -->
    <section class="py-16 bg-white">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-slate-950 mb-4">Cloud vs On-Premise</h2>
                <p class="text-lg text-slate-600">Compare deployment options</p>
            </div>

            <div class="max-w-4xl mx-auto">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b-2 border-slate-950/10">
                                <th class="text-left py-4 px-6 text-slate-950 font-semibold">Feature</th>
                                <th class="text-center py-4 px-6 text-teal-700 font-semibold">Cloud</th>
                                <th class="text-center py-4 px-6 text-slate-700 font-semibold">On-Premise</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b border-slate-950/10">
                                <td class="py-4 px-6 text-slate-950 font-medium">Hosting</td>
                                <td class="text-center py-4 px-6 text-slate-600">SkyBase</td>
                                <td class="text-center py-4 px-6 text-slate-600">Your Infrastructure</td>
                            </tr>
                            <tr class="border-b border-slate-950/10 bg-[#f6f1e8]">
                                <td class="py-4 px-6 text-slate-950 font-medium">Updates</td>
                                <td class="text-center py-4 px-6 text-slate-600">Automatic</td>
                                <td class="text-center py-4 px-6 text-slate-600">Manual</td>
                            </tr>
                            <tr class="border-b border-slate-950/10">
                                <td class="py-4 px-6 text-slate-950 font-medium">Server Maintenance</td>
                                <td class="text-center py-4 px-6 text-slate-600">SkyBase</td>
                                <td class="text-center py-4 px-6 text-slate-600">Customer</td>
                            </tr>
                            <tr class="border-b border-slate-950/10 bg-[#f6f1e8]">
                                <td class="py-4 px-6 text-slate-950 font-medium">Setup Time</td>
                                <td class="text-center py-4 px-6 text-slate-600">Minutes</td>
                                <td class="text-center py-4 px-6 text-slate-600">Depends on server</td>
                            </tr>
                            <tr class="border-b border-slate-950/10">
                                <td class="py-4 px-6 text-slate-950 font-medium">Customization</td>
                                <td class="text-center py-4 px-6 text-slate-600">Limited</td>
                                <td class="text-center py-4 px-6 text-slate-600">Full control</td>
                            </tr>
                            <tr class="bg-[#f6f1e8]">
                                <td class="py-4 px-6 text-slate-950 font-medium">Best For</td>
                                <td class="text-center py-4 px-6 text-slate-600">Fast deployment</td>
                                <td class="text-center py-4 px-6 text-slate-600">Infrastructure control</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-16 bg-[#f6f1e8]" x-data="{ openFaq: null }">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-slate-950 mb-4">Frequently Asked Questions</h2>
                <p class="text-lg text-slate-600">Find answers to common questions about SkyBase pricing</p>
            </div>

            <div class="max-w-3xl mx-auto space-y-4">
                <!-- FAQ 1 -->
                <div class="bg-white border border-slate-950/10 rounded-2xl overflow-hidden">
                    <button
                        @click="openFaq = openFaq === 1 ? null : 1"
                        class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-[#f6f1e8] transition-colors"
                    >
                        <span class="font-semibold text-slate-950">Can I upgrade my plan later?</span>
                        <svg class="w-5 h-5 text-slate-500 transition-transform" :class="{ 'rotate-180': openFaq === 1 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="openFaq === 1" x-transition class="px-6 pb-4 text-slate-600">
                        Yes, you can upgrade at any time. When you upgrade, you'll get immediate access to all features in your new plan, and we'll prorate your billing accordingly.
                    </div>
                </div>

                <!-- FAQ 2 -->
                <div class="bg-white border border-slate-950/10 rounded-2xl overflow-hidden">
                    <button
                        @click="openFaq = openFaq === 2 ? null : 2"
                        class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-[#f6f1e8] transition-colors"
                    >
                        <span class="font-semibold text-slate-950">Is there a setup fee?</span>
                        <svg class="w-5 h-5 text-slate-500 transition-transform" :class="{ 'rotate-180': openFaq === 2 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="openFaq === 2" x-transition class="px-6 pb-4 text-slate-600">
                        No. SkyBase does not charge setup fees. You can start the free plan immediately without any upfront costs.
                    </div>
                </div>

                <!-- FAQ 3 -->
                <div class="bg-white border border-slate-950/10 rounded-2xl overflow-hidden">
                    <button
                        @click="openFaq = openFaq === 3 ? null : 3"
                        class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-[#f6f1e8] transition-colors"
                    >
                        <span class="font-semibold text-slate-950">Do you support MikroTik?</span>
                        <svg class="w-5 h-5 text-slate-500 transition-transform" :class="{ 'rotate-180': openFaq === 3 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="openFaq === 3" x-transition class="px-6 pb-4 text-slate-600">
                        Yes. SkyBase integrates seamlessly with MikroTik for PPPoE, Hotspot, and RADIUS authentication. We're built specifically for MikroTik-based networks.
                    </div>
                </div>

                <!-- FAQ 4 -->
                <div class="bg-white border border-slate-950/10 rounded-2xl overflow-hidden">
                    <button
                        @click="openFaq = openFaq === 4 ? null : 4"
                        class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-[#f6f1e8] transition-colors"
                    >
                        <span class="font-semibold text-slate-950">Can I migrate from On-Premise to Cloud?</span>
                        <svg class="w-5 h-5 text-slate-500 transition-transform" :class="{ 'rotate-180': openFaq === 4 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="openFaq === 4" x-transition class="px-6 pb-4 text-slate-600">
                        Yes. Migration assistance is available if you want to move from On-Premise to Cloud. Our team can help you seamlessly transition your data and configuration.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Final CTA Section -->
    <section class="py-20 bg-[#0d2f35]">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl mx-auto text-center">
                <h2 class="text-3xl font-bold text-white mb-4">Start Managing Your ISP with SkyBase</h2>
                <p class="text-xl text-teal-50/85 mb-8">
                    Deploy in minutes with our Cloud platform or host SkyBase on your own infrastructure.
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="{{ route('auth.register') }}" class="inline-flex items-center justify-center px-10 py-4 text-base font-semibold text-slate-950 bg-white rounded-2xl hover:bg-[#f6f1e8] transition-colors">
                        Start Free
                    </a>
                    <a href="mailto:sales@skybase.app" class="inline-flex items-center justify-center px-10 py-4 text-base font-semibold text-white border-2 border-white rounded-2xl hover:bg-white hover:text-teal-700 transition-colors">
                        Contact Sales
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div
        x-cloak
        x-show="show"
        x-on:keydown.escape.window="close()"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display: none;"
    >
        <div x-show="show" x-transition.opacity class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" @click="close()"></div>
        <div x-show="show" x-transition class="relative z-10 w-full max-w-3xl overflow-hidden rounded-[2rem] bg-white shadow-2xl">
            <div class="flex items-start justify-between border-b border-slate-950/10 px-6 py-5 sm:px-8">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-700">On-Premise Demo</p>
                    <h3 class="mt-2 text-2xl font-bold text-slate-950">Tell us about your business</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">We use this to tailor the demo around your current operations and growth plans.</p>
                </div>
                <button type="button" @click="close()" class="rounded-full p-2 text-slate-400 transition hover:bg-[#f6f1e8] hover:text-slate-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </div>

            <form action="{{ route('demo-requests.store') }}" method="POST" class="px-6 py-6 sm:px-8">
                @csrf

                <div class="grid gap-5 md:grid-cols-2">
                    <x-ui.input.text
                        name="requested_plan"
                        label="Requested plan"
                        :value="old('requested_plan')"
                        error="{{ $errors->demoRequest->first('requested_plan') }}"
                        x-model="plan"
                        readonly
                        required
                    />
                    <x-ui.input.text
                        name="business_name"
                        label="Business name"
                        :value="old('business_name')"
                        error="{{ $errors->demoRequest->first('business_name') }}"
                        placeholder="SkyNet Fiber"
                        required
                    />
                    <x-ui.input.text
                        name="contact_name"
                        label="Contact name"
                        :value="old('contact_name')"
                        error="{{ $errors->demoRequest->first('contact_name') }}"
                        placeholder="Jane Doe"
                        required
                    />
                    <x-ui.input.text
                        name="email"
                        type="email"
                        label="Work email"
                        :value="old('email')"
                        error="{{ $errors->demoRequest->first('email') }}"
                        placeholder="jane@company.com"
                        required
                    />
                    <x-ui.input.text
                        name="phone"
                        type="tel"
                        label="Phone number"
                        :value="old('phone')"
                        error="{{ $errors->demoRequest->first('phone') }}"
                        placeholder="+1 555 123 4567"
                    />
                    <x-ui.input.text
                        name="country"
                        label="Country"
                        :value="old('country')"
                        error="{{ $errors->demoRequest->first('country') }}"
                        placeholder="France"
                        required
                    />
                    <x-ui.input.text
                        name="company_website"
                        type="url"
                        label="Company website"
                        :value="old('company_website')"
                        error="{{ $errors->demoRequest->first('company_website') }}"
                        placeholder="https://example.com"
                    />
                    <x-ui.input.text
                        name="customer_count"
                        type="number"
                        label="How many customers?"
                        :value="old('customer_count')"
                        error="{{ $errors->demoRequest->first('customer_count') }}"
                        placeholder="350"
                        min="1"
                        required
                    />
                    <x-ui.input.text
                        name="current_system"
                        label="Current setup"
                        :value="old('current_system')"
                        error="{{ $errors->demoRequest->first('current_system') }}"
                        placeholder="Spreadsheets, custom ERP, billing suite..."
                    />
                    <x-ui.input.select
                        name="deployment_timeline"
                        label="Deployment timeline"
                        :value="old('deployment_timeline')"
                        :options="[
                            'Immediately' => 'Immediately',
                            'This month' => 'This month',
                            'This quarter' => 'This quarter',
                            'Just exploring' => 'Just exploring',
                        ]"
                        error="{{ $errors->demoRequest->first('deployment_timeline') }}"
                        placeholder="Choose a timeline"
                    />
                </div>

                <div class="mt-5">
                    <x-ui.input.textarea
                        name="message"
                        label="Anything else we should know?"
                        :value="old('message')"
                        error="{{ $errors->demoRequest->first('message') }}"
                        placeholder="Share deployment goals, integrations, or any priorities for the walkthrough."
                        rows="4"
                    />
                </div>

                <div class="mt-6 flex flex-col gap-3 border-t border-slate-950/10 pt-5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm leading-6 text-slate-500">Your submission is saved in our database and sent to our Telegram sales channel.</p>
                    <div class="flex gap-3">
                        <button type="button" @click="close()" class="inline-flex items-center justify-center rounded-full border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-[#f6f1e8]">Cancel</button>
                        <button type="submit" class="inline-flex items-center justify-center rounded-full bg-[#0d2f35] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#123f3d]">Request Demo</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
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

        function demoRequestModal() {
            return {
                show: @json($errors->demoRequest->any()),
                plan: @js(old('requested_plan', 'Standard')),
                open(selectedPlan) {
                    this.plan = selectedPlan;
                    this.show = true;
                },
                close() {
                    this.show = false;
                }
            }
        }
    </script>
@endpush
