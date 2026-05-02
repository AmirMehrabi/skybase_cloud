@extends('layouts.layout')

@section('title', 'SkyBase Cloud - Simple ISP Management Software for MikroTik Operators')
@section('meta_description', 'Affordable ISP management software for MikroTik operators. Manage customers, PPPoE/Hotspot Radius, routers, billing, and alerts with 24/7 WhatsApp support.')
@section('meta_keywords', 'ISP management software, MikroTik management, Radius server, WISP software, ISP billing, PPPoE management, WhatsApp support')
@section('og_title', 'SkyBase Cloud - Simple ISP Management Software for MikroTik Operators')
@section('og_description', 'Run your MikroTik ISP from one simple cloud dashboard with affordable pricing and 24/7 WhatsApp support.')
@section('og_url', url('/'))
@section('body_class', 'bg-[#f6f1e8] text-slate-950')

@php
    $whatsappUrl = 'https://wa.me/33758351473?text='.rawurlencode('Hi SkyBase, I would like help with my ISP setup.');

    $proofPoints = [
        'Free plan up to 40 subscribers',
        'Built for MikroTik',
        'WhatsApp: +33 7 58 35 14 73',
    ];

    $operations = [
        ['label' => 'Create customer', 'value' => '42 sec', 'note' => 'Plan, PPPoE user, and status in one flow'],
        ['label' => 'Radius sessions', 'value' => '1,248', 'note' => 'PPPoE and Hotspot accounting at a glance'],
        ['label' => 'Routers online', 'value' => '18 / 19', 'note' => 'Instant visibility before customers call'],
        ['label' => 'Support replies', 'value' => '24/7', 'note' => 'WhatsApp help for setup and urgent questions'],
    ];

    $painPoints = [
        'Self-hosted Radius boxes to patch, monitor, and rescue',
        'Manual MikroTik changes that break at the worst time',
        'Customer activation and suspension spread across tools',
        'Support tickets piling up because alerts arrive too late',
    ];

    $outcomes = [
        'Cloud Radius for PPPoE and Hotspot authentication',
        'Customer, subscription, invoice, and router workflows together',
        'Alerts and operational snapshots your team can understand fast',
        'A real support path on WhatsApp when you need help now',
    ];

    $features = [
        [
            'eyebrow' => 'Customers',
            'title' => 'Activate, suspend, and update subscribers without router hopping.',
            'items' => ['Customer records', 'Plans and profiles', 'Subscription status', 'Invoice follow-up'],
        ],
        [
            'eyebrow' => 'MikroTik + Radius',
            'title' => 'Provision PPPoE and Hotspot access from a clean cloud workflow.',
            'items' => ['Radius users', 'Speed profiles', 'Session tracking', 'Accounting data'],
        ],
        [
            'eyebrow' => 'Operations',
            'title' => 'See the network, payments, and support queue before small issues grow.',
            'items' => ['Router health', 'Offline alerts', 'Payment due list', 'Open tickets'],
        ],
    ];

    $pricingPlans = [
        ['name' => 'Free', 'price' => '$0', 'limit' => 'Up to 40 subscribers', 'note' => 'Perfect for small ISPs getting started'],
        ['name' => 'Starter', 'price' => '$69', 'limit' => 'Up to 150 subscribers', 'note' => 'For growing ISPs'],
        ['name' => 'Growth', 'price' => '$129', 'limit' => 'Up to 300 subscribers', 'note' => 'Best value for scaling ISPs'],
    ];

    $pricingHighlights = [
        'No contracts',
        'Cancel anytime',
        'No setup fees',
        'Cloud hosting included',
    ];

    $faqs = [
        ['question' => 'Is this only for MikroTik networks?', 'answer' => 'SkyBase is designed around MikroTik ISP workflows, including PPPoE, Hotspot, Radius users, router visibility, and customer provisioning.'],
        ['question' => 'Can a small ISP use it?', 'answer' => 'Yes. The landing flow, pricing, and onboarding are meant for small and growing operators that want less infrastructure and fewer manual steps.'],
        ['question' => 'What happens during the demo?', 'answer' => 'We review your current setup, customer count, routers, authentication flow, pricing fit, and the simplest path to your first working tenant.'],
        ['question' => 'Can I contact you on WhatsApp?', 'answer' => 'Yes. Message us on +33 7 58 35 14 73 for demo scheduling, onboarding questions, and product support.'],
    ];
@endphp

@section('content')
    <section class="relative isolate overflow-hidden bg-[#0d2f35] text-white">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_15%_20%,rgba(34,197,94,0.28),transparent_28%),radial-gradient(circle_at_85%_15%,rgba(245,158,11,0.22),transparent_30%),linear-gradient(135deg,#09252b_0%,#0d2f35_46%,#123f3d_100%)]"></div>
        <div class="absolute left-1/2 top-0 -z-10 h-[36rem] w-[36rem] -translate-x-1/2 rounded-full border border-white/10 bg-white/[0.03] blur-3xl"></div>

        <div class="mx-auto grid max-w-7xl gap-12 px-4 py-16 sm:px-6 sm:py-20 lg:grid-cols-[0.92fr_1.08fr] lg:px-8 lg:py-24">
            <div class="flex flex-col justify-center">
                <div class="inline-flex w-fit items-center gap-2 rounded-full border border-emerald-300/30 bg-emerald-300/10 px-4 py-2 text-sm font-semibold text-emerald-100 shadow-[0_12px_40px_rgba(16,185,129,0.18)]">
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-300 shadow-[0_0_18px_rgba(110,231,183,0.9)]"></span>
                    Affordable cloud ISP management with real WhatsApp help
                </div>

                <h1 class="mt-7 max-w-4xl text-5xl font-bold tracking-[-0.06em] text-white sm:text-6xl lg:text-7xl">
                    Run your MikroTik ISP without servers, scripts, or stress.
                </h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-teal-50/85 sm:text-xl">
                    SkyBase Cloud brings customers, PPPoE/Hotspot Radius, router monitoring, billing follow-up, and support workflows into one simple dashboard built for small and growing ISPs.
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('auth.register') }}" class="group inline-flex items-center justify-center rounded-full bg-[#f5c542] px-7 py-4 text-base font-bold text-slate-950 shadow-[0_20px_50px_rgba(245,197,66,0.28)] transition hover:-translate-y-0.5 hover:bg-[#ffd95d]">
                        Start Trial
                        <span class="ml-2 transition group-hover:translate-x-1">-&gt;</span>
                    </a>
                    <a href="{{ route('pricing') }}" class="inline-flex items-center justify-center rounded-full border border-white/20 bg-white/10 px-7 py-4 text-base font-bold text-white backdrop-blur transition hover:bg-white hover:text-slate-950">
                        View Pricing
                    </a>
                </div>

                <p class="mt-4 text-sm font-medium text-teal-50/75">Start a free trial, compare pricing, or message us on WhatsApp for help choosing the right plan.</p>

                <div class="mt-8 flex flex-wrap gap-3">
                    @foreach($proofPoints as $proofPoint)
                        <span class="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-semibold text-teal-50 backdrop-blur">{{ $proofPoint }}</span>
                    @endforeach
                </div>
            </div>

            <div class="relative">
                <div class="absolute -left-8 top-14 hidden w-44 rotate-[-8deg] rounded-[2rem] border border-white/15 bg-white/10 p-4 shadow-2xl backdrop-blur lg:block">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-teal-100">Alert</p>
                    <p class="mt-3 text-2xl font-bold">4 min</p>
                    <p class="mt-1 text-sm leading-5 text-teal-50/75">Router warning before customer calls.</p>
                </div>

                <div class="rounded-[2.25rem] border border-white/15 bg-white/10 p-3 shadow-[0_35px_90px_rgba(0,0,0,0.35)] backdrop-blur-xl">
                    <div class="overflow-hidden rounded-[1.75rem] bg-[#eef3e7] text-slate-950">
                        <div class="flex items-center justify-between border-b border-slate-950/10 bg-[#fbf7ed] px-5 py-4">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.24em] text-teal-800">Today at North Ridge Wireless</p>
                                <h2 class="mt-1 text-xl font-bold tracking-tight">ISP operations board</h2>
                            </div>
                            <div class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800 ring-1 ring-emerald-200">Live</div>
                        </div>

                        <div class="grid gap-3 p-4 sm:grid-cols-2">
                            @foreach($operations as $operation)
                                <div class="rounded-[1.5rem] border border-slate-950/10 bg-white p-4 shadow-sm">
                                    <p class="text-sm font-semibold text-slate-500">{{ $operation['label'] }}</p>
                                    <p class="mt-3 text-3xl font-bold tracking-tight text-slate-950">{{ $operation['value'] }}</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $operation['note'] }}</p>
                                </div>
                            @endforeach
                        </div>

                        <div class="grid gap-4 border-t border-slate-950/10 p-4 lg:grid-cols-[1.15fr_0.85fr]">
                            <div class="rounded-[1.5rem] bg-[#0d2f35] p-5 text-white">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-[0.24em] text-emerald-200">Provisioning</p>
                                        <h3 class="mt-2 text-2xl font-bold">New customer ready</h3>
                                    </div>
                                    <span class="rounded-full bg-[#f5c542] px-3 py-1 text-xs font-bold text-slate-950">Done</span>
                                </div>
                                <div class="mt-5 space-y-3">
                                    <div class="flex items-center justify-between rounded-2xl bg-white/10 px-4 py-3 text-sm"><span>Create customer</span><span>Complete</span></div>
                                    <div class="flex items-center justify-between rounded-2xl bg-white/10 px-4 py-3 text-sm"><span>Assign Fiber 200 plan</span><span>Complete</span></div>
                                    <div class="flex items-center justify-between rounded-2xl bg-white/10 px-4 py-3 text-sm"><span>Push Radius profile</span><span>Complete</span></div>
                                </div>
                            </div>
                            <div class="rounded-[1.5rem] border border-emerald-200 bg-emerald-50 p-5">
                                <p class="text-xs font-bold uppercase tracking-[0.24em] text-emerald-700">WhatsApp support</p>
                                <p class="mt-3 text-lg font-bold text-slate-950">Need help connecting router #1?</p>
                                <p class="mt-2 text-sm leading-6 text-slate-700">Message +33 7 58 35 14 73 for setup help, onboarding questions, or demo scheduling.</p>
                                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="mt-5 inline-flex rounded-full bg-emerald-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-emerald-700">Message WhatsApp</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-[#f6f1e8] py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-3 rounded-[2rem] border border-slate-950/10 bg-white/70 p-3 shadow-sm backdrop-blur md:grid-cols-4">
                @foreach(['Wireless ISPs', 'Fiber operators', 'MikroTik Hotspots', 'Growing local ISPs'] as $audience)
                    <div class="rounded-[1.4rem] bg-white px-5 py-4 text-center text-sm font-bold text-slate-800 ring-1 ring-slate-950/5">{{ $audience }}</div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-[#f6f1e8] py-16 sm:py-20">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[0.95fr_1.05fr] lg:px-8">
            <div class="rounded-[2rem] bg-[#172a2c] p-8 text-white shadow-xl sm:p-10">
                <p class="text-sm font-bold uppercase tracking-[0.24em] text-red-200">Before SkyBase</p>
                <h2 class="mt-4 text-3xl font-bold tracking-tight sm:text-4xl">Too many fragile pieces for a business that needs to stay online.</h2>
                <div class="mt-8 space-y-3">
                    @foreach($painPoints as $painPoint)
                        <div class="rounded-2xl border border-white/10 bg-white/[0.06] px-4 py-3 text-sm leading-6 text-slate-100">{{ $painPoint }}</div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-[2rem] border border-emerald-200 bg-white p-8 shadow-xl sm:p-10">
                <p class="text-sm font-bold uppercase tracking-[0.24em] text-emerald-700">After SkyBase</p>
                <h2 class="mt-4 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">A simpler operating system for customers, routers, and support.</h2>
                <div class="mt-8 space-y-3">
                    @foreach($outcomes as $outcome)
                        <div class="flex gap-3 rounded-2xl bg-emerald-50 px-4 py-3 text-sm leading-6 text-slate-800 ring-1 ring-emerald-100">
                            <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-emerald-500"></span>
                            <span>{{ $outcome }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="bg-white py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
                <div class="max-w-2xl">
                    <p class="text-sm font-bold uppercase tracking-[0.24em] text-teal-700">Product walkthrough</p>
                    <h2 class="mt-4 text-4xl font-bold tracking-tight text-slate-950 sm:text-5xl">Keep the daily ISP work obvious.</h2>
                </div>
                <p class="max-w-md text-lg leading-8 text-slate-600">Manage the core ISP workflows in one place: customers, Radius authentication, router monitoring, billing follow-up, and support tasks.</p>
            </div>

            <div class="mt-10 grid gap-5 lg:grid-cols-3">
                @foreach($features as $feature)
                    <article class="group overflow-hidden rounded-[2rem] border border-slate-200 bg-[#fbf7ed] p-6 transition hover:-translate-y-1 hover:shadow-2xl">
                        <div class="flex h-full flex-col">
                            <p class="text-sm font-bold uppercase tracking-[0.24em] text-amber-700">{{ $feature['eyebrow'] }}</p>
                            <h3 class="mt-4 text-2xl font-bold tracking-tight text-slate-950">{{ $feature['title'] }}</h3>
                            <div class="mt-8 grid grid-cols-2 gap-3">
                                @foreach($feature['items'] as $item)
                                    <span class="rounded-2xl bg-white px-4 py-3 text-sm font-semibold text-slate-700 ring-1 ring-slate-950/5">{{ $item }}</span>
                                @endforeach
                            </div>
                            <div class="mt-8 h-2 rounded-full bg-slate-200">
                                <div class="h-2 rounded-full bg-gradient-to-r from-teal-700 via-emerald-500 to-[#f5c542] transition-all group-hover:w-full" style="width: {{ $loop->first ? '75%' : ($loop->iteration === 2 ? '66%' : '84%') }}"></div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="pricing" class="bg-[#102f34] py-16 text-white sm:py-20">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[1fr_0.8fr] lg:px-8 lg:items-center">
            <div>
                <p class="text-sm font-bold uppercase tracking-[0.24em] text-[#f5c542]">Simple cloud pricing</p>
                <h2 class="mt-4 text-4xl font-bold tracking-tight sm:text-5xl">Start free. Upgrade only when your subscriber base grows.</h2>
                <p class="mt-5 max-w-2xl text-lg leading-8 text-teal-50/80">Cloud plans start at $0/month for up to 40 subscribers. Paid plans begin at $69/month and include cloud hosting, automatic updates, and core ISP management features.</p>

                <div class="mt-6 grid gap-3 sm:grid-cols-3">
                    @foreach($pricingPlans as $pricingPlan)
                        <div class="rounded-2xl border border-white/10 bg-white/10 p-4">
                            <p class="text-sm font-bold text-white">{{ $pricingPlan['name'] }}</p>
                            <p class="mt-2 text-2xl font-bold text-[#f5c542]">{{ $pricingPlan['price'] }}<span class="text-sm font-semibold text-teal-50/70"> / month</span></p>
                            <p class="mt-2 text-xs font-semibold text-teal-50">{{ $pricingPlan['limit'] }}</p>
                            <p class="mt-1 text-xs leading-5 text-teal-50/70">{{ $pricingPlan['note'] }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8 grid gap-3 sm:grid-cols-2">
                    @foreach($pricingHighlights as $pricingHighlight)
                        <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 text-sm font-semibold text-teal-50">{{ $pricingHighlight }}</div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-[2rem] border border-white/10 bg-white p-7 text-slate-950 shadow-2xl">
                <p class="text-sm font-bold uppercase tracking-[0.24em] text-teal-700">Popular cloud plans</p>
                <h3 class="mt-4 text-3xl font-bold tracking-tight">Clear monthly pricing for growing MikroTik ISPs.</h3>
                <p class="mt-4 text-base leading-7 text-slate-600">Choose a plan by subscriber count, start without setup fees, and move to the next tier when your network grows.</p>
                <div class="mt-6 rounded-2xl bg-[#f6f1e8] p-5">
                    <p class="text-sm font-semibold text-slate-500">Starts from</p>
                    <p class="mt-2 text-2xl font-bold">$0/month for up to 40 subscribers</p>
                </div>
                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('pricing') }}" class="inline-flex flex-1 items-center justify-center rounded-full bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-slate-800">View Pricing</a>
                    <a href="{{ route('auth.register') }}" class="inline-flex flex-1 items-center justify-center rounded-full bg-[#f5c542] px-5 py-3 text-sm font-bold text-slate-950 transition hover:bg-[#ffd95d]">Start Trial</a>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-[#f6f1e8] py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-[2.5rem] border border-slate-950/10 bg-white p-6 shadow-xl sm:p-10">
                <div class="grid gap-8 lg:grid-cols-[0.78fr_1.22fr] lg:items-center">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-[0.24em] text-emerald-700">24/7 WhatsApp support</p>
                        <h2 class="mt-4 text-4xl font-bold tracking-tight text-slate-950">Support should feel as simple as sending a message.</h2>
                        <p class="mt-5 text-lg leading-8 text-slate-600">Talk to SkyBase directly on WhatsApp at +33 7 58 35 14 73 when you want a walkthrough, setup guidance, or quick answers before starting.</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="rounded-[1.75rem] bg-emerald-50 p-6 ring-1 ring-emerald-100">
                            <p class="text-3xl font-bold text-emerald-700">1</p>
                            <p class="mt-3 font-bold text-slate-950">Share your setup</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Routers, customers, Radius, and billing pain.</p>
                        </div>
                        <div class="rounded-[1.75rem] bg-amber-50 p-6 ring-1 ring-amber-100">
                            <p class="text-3xl font-bold text-amber-700">2</p>
                            <p class="mt-3 font-bold text-slate-950">Get a guided demo</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Focused on your ISP, not generic slides.</p>
                        </div>
                        <div class="rounded-[1.75rem] bg-teal-50 p-6 ring-1 ring-teal-100">
                            <p class="text-3xl font-bold text-teal-700">3</p>
                            <p class="mt-3 font-bold text-slate-950">Connect the first router</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Get practical onboarding help.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="demo" class="bg-white py-16 sm:py-20">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-[0.85fr_1.15fr] lg:px-8">
            <div class="flex flex-col justify-between rounded-[2rem] bg-slate-950 p-8 text-white sm:p-10">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.24em] text-[#f5c542]">Book your demo</p>
                    <h2 class="mt-4 text-4xl font-bold tracking-tight sm:text-5xl">Book a product demo around your real ISP setup.</h2>
                    <p class="mt-5 text-lg leading-8 text-slate-300">Share a few details and we will tailor the walkthrough around your customer count, current system, pricing questions, and preferred contact method.</p>
                </div>
                <div class="mt-10 space-y-3">
                    @foreach(['Free product demo', 'Start trial separately', 'Pricing questions welcome', 'WhatsApp follow-up available'] as $demoBenefit)
                        <div class="rounded-2xl border border-white/10 bg-white/[0.06] px-4 py-3 text-sm font-semibold text-slate-100">{{ $demoBenefit }}</div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-[2rem] border border-slate-200 bg-[#fbf7ed] p-6 shadow-xl sm:p-8">
                @if (session('demo_request_success'))
                    <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                        {{ session('demo_request_success') }}
                    </div>
                @endif

                <form action="{{ route('demo-requests.store') }}" method="POST" class="space-y-5">
                    @csrf
                    <input type="hidden" name="requested_plan" value="WhatsApp demo from homepage">
                    <input type="hidden" name="source_page" value="home">

                    <div class="grid gap-5 md:grid-cols-2">
                        <x-ui.input.text
                            name="business_name"
                            label="ISP name"
                            :value="old('business_name')"
                            error="{{ $errors->demoRequest->first('business_name') }}"
                            placeholder="North Ridge Wireless"
                            required
                        />
                        <x-ui.input.text
                            name="contact_name"
                            label="Your name"
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
                            placeholder="jane@isp.com"
                            required
                        />
                        <x-ui.input.text
                            name="phone"
                            type="tel"
                            label="WhatsApp number"
                            :value="old('phone')"
                            error="{{ $errors->demoRequest->first('phone') }}"
                            placeholder="+33 7 58 35 14 73"
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
                            name="customer_count"
                            type="number"
                            label="Customer count"
                            :value="old('customer_count')"
                            error="{{ $errors->demoRequest->first('customer_count') }}"
                            placeholder="350"
                            min="1"
                            required
                        />
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <x-ui.input.text
                            name="current_system"
                            label="Current system"
                            :value="old('current_system')"
                            error="{{ $errors->demoRequest->first('current_system') }}"
                            placeholder="MikroTik + spreadsheets"
                        />
                        <x-ui.input.select
                            name="deployment_timeline"
                            label="Timeline"
                            :value="old('deployment_timeline')"
                            :options="[
                                'Immediately' => 'Immediately',
                                'This month' => 'This month',
                                'This quarter' => 'This quarter',
                                'Just exploring' => 'Just exploring',
                            ]"
                            error="{{ $errors->demoRequest->first('deployment_timeline') }}"
                            placeholder="Choose one"
                        />
                    </div>

                    <x-ui.input.textarea
                        name="message"
                        label="What do you want to simplify first?"
                        :value="old('message')"
                        error="{{ $errors->demoRequest->first('message') }}"
                        placeholder="Tell us about Radius, billing, customer activation, router monitoring, support, or pricing questions."
                        rows="4"
                    />

                    <div class="flex flex-col gap-4 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm leading-6 text-slate-600">We will use your WhatsApp number or email to arrange the demo. You can also message +33 7 58 35 14 73 directly.</p>
                        <button type="submit" class="inline-flex items-center justify-center rounded-full bg-emerald-600 px-7 py-3 text-sm font-bold text-white shadow-[0_16px_35px_rgba(5,150,105,0.25)] transition hover:-translate-y-0.5 hover:bg-emerald-700">
                            Request Demo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <section class="bg-[#f6f1e8] py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-5 lg:grid-cols-4">
                @foreach($faqs as $faq)
                    <div class="rounded-[1.75rem] border border-slate-950/10 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-slate-950">{{ $faq['question'] }}</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600">{{ $faq['answer'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-[#0d2f35] py-16 text-white sm:py-20">
        <div class="mx-auto max-w-5xl px-4 text-center sm:px-6 lg:px-8">
            <p class="text-sm font-bold uppercase tracking-[0.24em] text-[#f5c542]">Ready when you are</p>
            <h2 class="mt-4 text-4xl font-bold tracking-tight sm:text-5xl">Start your trial or book a demo to see if SkyBase fits your ISP.</h2>
            <p class="mx-auto mt-5 max-w-2xl text-lg leading-8 text-teal-50/80">Create your trial account when you are ready, or request a walkthrough if you want to review your setup with us first.</p>
            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ route('auth.register') }}" class="inline-flex items-center justify-center rounded-full bg-[#f5c542] px-8 py-4 text-base font-bold text-slate-950 transition hover:bg-[#ffd95d]">Start Trial</a>
                <a href="#demo" class="inline-flex items-center justify-center rounded-full border border-white/20 bg-white/10 px-8 py-4 text-base font-bold text-white transition hover:bg-white hover:text-slate-950">Book a Demo</a>
            </div>
        </div>
    </section>
@endsection
