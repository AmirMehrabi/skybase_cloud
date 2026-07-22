@extends('layouts.layout')

@section('title', 'SkyBase Cloud - Simple ISP Management Software for MikroTik ISP Owners')
@section('meta_description', 'Affordable ISP management software for MikroTik ISP owners. Manage customers, services, connections, billing, and support with direct founder support.')
@section('meta_keywords', 'ISP management software, MikroTik management, Radius server, WISP software, ISP billing, PPPoE management')
@section('og_title', 'SkyBase Cloud - Simple ISP Management Software for MikroTik ISP Owners')
@section('og_description', 'Run your MikroTik ISP with less daily work, clearer customer management, and direct founder support.')
@section('og_url', url('/'))
@section('body_class', 'bg-[#f6f1e8] text-slate-950')

@php
    $whatsappUrl = 'https://wa.me/33758351473?text='.rawurlencode('Hi SkyBase, I would like help with my ISP setup.');

    $outcomes = [
        [
            'eyebrow' => 'Customers',
            'title' => 'See every customer and service in one clear place.',
            'copy' => 'Keep customer details, plans, service status, and follow-up work together so your team always knows what happens next.',
            'items' => ['Customer records', 'Service status', 'Plan management', 'Invoice follow-up'],
        ],
        [
            'eyebrow' => 'Connections',
            'title' => 'Know what is happening before customers call.',
            'copy' => 'See connection activity and network status from one dashboard, with practical information your team can act on quickly.',
            'items' => ['Active connections', 'Connection status', 'Service settings', 'Usage visibility'],
        ],
        [
            'eyebrow' => 'Daily work',
            'title' => 'Keep billing, support, and follow-up moving.',
            'copy' => 'Replace scattered tools and manual reminders with a simpler daily workflow built around the way your business actually operates.',
            'items' => ['Payment follow-up', 'Support requests', 'Team tasks', 'Useful alerts'],
        ],
    ];

    $pricingPlans = [
        ['name' => 'Free', 'price' => '$0', 'limit' => 'Up to 40 subscribers', 'note' => 'Free forever'],
        ['name' => 'Starter', 'price' => '$69', 'limit' => 'Up to 150 subscribers', 'note' => 'For growing ISPs'],
        ['name' => 'Growth', 'price' => '$129', 'limit' => 'Up to 300 subscribers', 'note' => 'For scaling ISPs'],
    ];

    $testimonials = [
        [
            'quote' => "Since adoptins Skybase, we've had complete peace of mind. Their system is reliable and their support is always available, responsive and personalised to our needs. Truly exceptional Service!",
            'name' => 'Makhado T',
            'role' => 'Operations Director, Ultech Solutions',
            'is_placeholder' => false,
        ]
    ];

    $testimonial = $testimonials[array_rand($testimonials)];

    $faqs = [
        ['question' => 'Can a small ISP use SkyBase?', 'answer' => 'Yes. SkyBase is designed for small and growing internet providers that want less manual work and a clearer way to manage customers and services.'],
        ['question' => 'What happens during a guided setup?', 'answer' => 'The founder reviews your current setup with you, answers your questions, shows the parts that matter to your business, and recommends a practical first step.'],
        ['question' => 'Is the Free plan really free forever?', 'answer' => 'Yes. The Free plan is $0 per month for up to 40 subscribers. You can move to a paid plan when your business grows.'],
        ['question' => 'Can I speak directly with the founder?', 'answer' => 'Yes. Every customer has a direct path to the founder for setup questions, product feedback, and help choosing the right next step.'],
    ];
@endphp

@section('content')
    {{-- 1. Promise and primary action --}}
    <section class="relative isolate overflow-hidden bg-[#0d2f35] text-white">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_15%_20%,rgba(34,197,94,0.28),transparent_28%),radial-gradient(circle_at_85%_15%,rgba(245,158,11,0.22),transparent_30%),linear-gradient(135deg,#09252b_0%,#0d2f35_46%,#123f3d_100%)]"></div>
        <div class="absolute left-1/2 top-0 -z-10 h-[36rem] w-[36rem] -translate-x-1/2 rounded-full border border-white/10 bg-white/[0.03] blur-3xl"></div>

        <div class="mx-auto grid max-w-7xl gap-12 px-4 py-16 sm:px-6 sm:py-20 lg:grid-cols-[0.9fr_1.1fr] lg:px-8 lg:py-24">
            <div class="flex flex-col justify-center">
                <div class="inline-flex w-fit items-center gap-2 rounded-full border border-emerald-300/30 bg-emerald-300/10 px-4 py-2 text-sm font-semibold text-emerald-100">
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-300 shadow-[0_0_18px_rgba(110,231,183,0.9)]"></span>
                    Simple ISP management for MikroTik businesses
                </div>

                <h1 class="mt-7 max-w-3xl text-5xl font-bold tracking-[-0.06em] text-white sm:text-6xl lg:text-7xl">
                    Run your ISP with less daily work.
                </h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-teal-50/85 sm:text-xl">
                    SkyBase brings customers, services, connections, billing, and support into one clear cloud workspace for small and growing internet providers.
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="#guided-setup" class="group inline-flex items-center justify-center rounded-full bg-[#f5c542] px-7 py-4 text-base font-bold text-slate-950 shadow-[0_20px_50px_rgba(245,197,66,0.28)] transition hover:-translate-y-0.5 hover:bg-[#ffd95d]">
                        Book a guided setup
                        <span class="ml-2 transition group-hover:translate-x-1">-&gt;</span>
                    </a>
                    <a href="#pricing" class="inline-flex items-center justify-center rounded-full border border-white/20 bg-white/10 px-7 py-4 text-base font-bold text-white backdrop-blur transition hover:bg-white hover:text-slate-950">
                        See pricing
                    </a>
                </div>

                <p class="mt-4 text-sm font-medium text-teal-50/75">Start with a conversation, or create your free account when you are ready.</p>
            </div>

            <div class="relative">
                <div class="absolute -left-8 top-14 hidden w-44 rotate-[-8deg] rounded-[2rem] border border-white/15 bg-white/10 p-4 shadow-2xl backdrop-blur lg:block">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-teal-100">Daily clarity</p>
                    <p class="mt-3 text-2xl font-bold">One place</p>
                    <p class="mt-1 text-sm leading-5 text-teal-50/75">For the work your team handles every day.</p>
                </div>

                <div class="rounded-[2.25rem] border border-white/15 bg-white/10 p-3 shadow-[0_35px_90px_rgba(0,0,0,0.35)] backdrop-blur-xl">
                    <div class="overflow-hidden rounded-[1.75rem] bg-[#eef3e7] text-slate-950">
                        <div class="flex items-center justify-between border-b border-slate-950/10 bg-[#fbf7ed] px-5 py-4">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.24em] text-teal-800">Illustrative workspace</p>
                                <h2 class="mt-1 text-xl font-bold tracking-tight">Your daily overview</h2>
                            </div>
                            <div class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800 ring-1 ring-emerald-200">Ready</div>
                        </div>

                        <div class="grid gap-3 p-4 sm:grid-cols-2">
                            @foreach([
                                ['label' => 'Customers to follow up', 'value' => '12', 'note' => 'Clear next actions'],
                                ['label' => 'Active connections', 'value' => '1,248', 'note' => 'Live service overview'],
                                ['label' => 'Connections needing attention', 'value' => '3', 'note' => 'See issues earlier'],
                                ['label' => 'Payments to review', 'value' => '18', 'note' => 'Keep billing moving'],
                            ] as $overview)
                                <div class="rounded-[1.5rem] border border-slate-950/10 bg-white p-4 shadow-sm">
                                    <p class="text-sm font-semibold text-slate-500">{{ $overview['label'] }}</p>
                                    <p class="mt-3 text-3xl font-bold tracking-tight text-slate-950">{{ $overview['value'] }}</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $overview['note'] }}</p>
                                </div>
                            @endforeach
                        </div>

                        <div class="border-t border-slate-950/10 p-4">
                            <div class="rounded-[1.5rem] bg-[#0d2f35] p-5 text-white">
                                <p class="text-xs font-bold uppercase tracking-[0.24em] text-emerald-200">Next step</p>
                                <div class="mt-3 flex items-center justify-between gap-4">
                                    <p class="text-xl font-bold">Review three connections</p>
                                    <span class="rounded-full bg-[#f5c542] px-3 py-1 text-xs font-bold text-slate-950">Open</span>
                                </div>
                                <p class="mt-3 text-sm leading-6 text-teal-50/75">A simple view of what needs your attention today.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 2. Founder letter --}}
    <section class="bg-[#fbf7ed] py-16 sm:py-20">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <article class="relative overflow-hidden rounded-[2rem] border border-slate-900/10 bg-white px-7 py-9 shadow-xl sm:px-12 sm:py-12">
                <div class="absolute right-0 top-0 h-32 w-32 rounded-full bg-amber-100/70 blur-3xl"></div>
                <div class="relative">
                    <p class="text-sm font-bold uppercase tracking-[0.24em] text-emerald-700">A letter from the founder</p>
                    <div class="mt-8 space-y-5 text-lg leading-8 text-slate-700">
                        <p>Dear ISP owner,</p>
                        <p>You should not have to become a full-time systems administrator just to run a reliable internet business. SkyBase exists to make the daily work clearer: your customers, your connections, your billing, and the next thing your team needs to do.</p>
                        <p>Every customer should have a direct path to me—not because they joined a special programme, but because being accountable to the people running real businesses is part of how SkyBase should work.</p>
                        <p>If you are considering SkyBase, book a guided setup. Bring your current workflow, your questions, and the parts that are slowing you down. We will look at them together.</p>
                    </div>
                    <div class="mt-10 border-t border-slate-200 pt-6">
                        <p class="font-[cursive] text-4xl italic tracking-tight text-slate-950">Abbie Barlowe</p>
                        <p class="mt-2 text-sm font-bold uppercase tracking-[0.2em] text-slate-500">Founder, SkyBase Cloud</p>
                    </div>
                </div>
            </article>
        </div>
    </section>

    {{-- 3. Customer outcomes --}}
    <section class="bg-white py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <p class="text-sm font-bold uppercase tracking-[0.24em] text-teal-700">What becomes easier</p>
                <h2 class="mt-4 text-4xl font-bold tracking-tight text-slate-950 sm:text-5xl">A clearer day for you and your team.</h2>
                <p class="mt-5 text-lg leading-8 text-slate-600">SkyBase focuses on the work that keeps your customers connected and your business moving.</p>
            </div>

            <div class="mt-10 grid gap-5 lg:grid-cols-3">
                @foreach($outcomes as $outcome)
                    <article class="rounded-[2rem] border border-slate-200 bg-[#fbf7ed] p-6 shadow-sm">
                        <p class="text-sm font-bold uppercase tracking-[0.24em] text-amber-700">{{ $outcome['eyebrow'] }}</p>
                        <h3 class="mt-4 text-2xl font-bold tracking-tight text-slate-950">{{ $outcome['title'] }}</h3>
                        <p class="mt-4 text-base leading-7 text-slate-600">{{ $outcome['copy'] }}</p>
                        <div class="mt-7 grid grid-cols-2 gap-3">
                            @foreach($outcome['items'] as $item)
                                <span class="rounded-2xl bg-white px-3 py-3 text-sm font-semibold text-slate-700 ring-1 ring-slate-950/5">{{ $item }}</span>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 4. Testimonials --}}
    <section class="bg-[#f6f1e8] py-16 sm:py-20">
        <div class="mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
            <p class="text-sm font-bold uppercase tracking-[0.24em] text-teal-700">What customers say</p>
            <blockquote class="mt-5 text-3xl font-bold leading-tight tracking-tight text-slate-950 sm:text-4xl">“{{ $testimonial['quote'] }}”</blockquote>
            <p class="mt-6 text-sm font-bold text-slate-600">{{ $testimonial['name'] }}<span class="mx-2 text-slate-300">·</span>{{ $testimonial['role'] }}</p>
        </div>
    </section>

    {{-- 5. Pricing --}}
    <section id="pricing" class="bg-[#102f34] py-16 text-white sm:py-20">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[1fr_0.85fr] lg:items-center lg:px-8">
            <div>
                <p class="text-sm font-bold uppercase tracking-[0.24em] text-[#f5c542]">Simple pricing</p>
                <h2 class="mt-4 text-4xl font-bold tracking-tight sm:text-5xl">Start free. Upgrade when your business grows.</h2>
                <p class="mt-5 max-w-2xl text-lg leading-8 text-teal-50/80">Choose a plan by the number of subscribers you serve. Every cloud plan includes hosting and automatic updates.</p>
                <a href="{{ route('pricing') }}" class="mt-7 inline-flex items-center justify-center rounded-full border border-white/20 bg-white/10 px-6 py-3 text-sm font-bold text-white transition hover:bg-white hover:text-slate-950">See all plans</a>
            </div>

            <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
                @foreach($pricingPlans as $pricingPlan)
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-5">
                        <div class="flex items-center justify-between gap-4">
                            <p class="font-bold text-white">{{ $pricingPlan['name'] }}</p>
                            <p class="text-2xl font-bold text-[#f5c542]">{{ $pricingPlan['price'] }}<span class="text-xs font-semibold text-teal-50/70">/month</span></p>
                        </div>
                        <p class="mt-2 text-sm font-semibold text-teal-50">{{ $pricingPlan['limit'] }}</p>
                        <p class="mt-1 text-sm leading-6 text-teal-50/70">{{ $pricingPlan['note'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 6. Guided setup --}}
    <section id="guided-setup" class="bg-white py-16 sm:py-20">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-[0.82fr_1.18fr] lg:px-8">
            <div class="flex flex-col justify-between rounded-[2rem] bg-slate-950 p-8 text-white sm:p-10">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.24em] text-[#f5c542]">Book a guided setup</p>
                    <h2 class="mt-4 text-4xl font-bold tracking-tight sm:text-5xl">See how SkyBase would work for your business.</h2>
                    <p class="mt-5 text-lg leading-8 text-slate-300">The founder will review your current setup, answer your questions, and show you the most useful first step.</p>
                </div>
                <div class="mt-10 space-y-3">
                    @foreach(['A conversation about your business', 'A focused walkthrough', 'A practical next step', 'Direct founder follow-up'] as $setupBenefit)
                        <div class="rounded-2xl border border-white/10 bg-white/[0.06] px-4 py-3 text-sm font-semibold text-slate-100">{{ $setupBenefit }}</div>
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
                    <input type="hidden" name="requested_plan" value="Guided setup from homepage">
                    <input type="hidden" name="source_page" value="home">

                    <div class="grid gap-5 md:grid-cols-2">
                        <x-ui.input.text name="business_name" label="Business name" :value="old('business_name')" error="{{ $errors->demoRequest->first('business_name') }}" placeholder="Your business name" required />
                        <x-ui.input.text name="contact_name" label="Your name" :value="old('contact_name')" error="{{ $errors->demoRequest->first('contact_name') }}" placeholder="Your name" required />
                        <x-ui.input.text name="email" type="email" label="Work email" :value="old('email')" error="{{ $errors->demoRequest->first('email') }}" placeholder="you@yourbusiness.com" required />
                        <x-ui.input.text name="phone" type="tel" label="WhatsApp number" :value="old('phone')" error="{{ $errors->demoRequest->first('phone') }}" placeholder="Your WhatsApp number" />
                        <x-ui.input.text name="country" label="Country" :value="old('country')" error="{{ $errors->demoRequest->first('country') }}" placeholder="Your country" required />
                        <x-ui.input.text name="customer_count" type="number" label="Subscribers" :value="old('customer_count')" error="{{ $errors->demoRequest->first('customer_count') }}" placeholder="Approximate number" min="1" required />
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <x-ui.input.text name="current_system" label="What do you use today?" :value="old('current_system')" error="{{ $errors->demoRequest->first('current_system') }}" placeholder="Your current tools" />
                        <x-ui.input.select name="deployment_timeline" label="When are you looking to start?" :value="old('deployment_timeline')" :options="['Immediately' => 'Immediately', 'This month' => 'This month', 'This quarter' => 'This quarter', 'Just exploring' => 'Just exploring']" error="{{ $errors->demoRequest->first('deployment_timeline') }}" placeholder="Choose one" />
                    </div>

                    <x-ui.input.textarea name="message" label="What would you like to make easier?" :value="old('message')" error="{{ $errors->demoRequest->first('message') }}" placeholder="Tell us what takes too much time today." rows="4" />

                    <div class="flex flex-col gap-4 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm leading-6 text-slate-600">We will use your email or WhatsApp number to arrange your guided setup.</p>
                        <button type="submit" class="inline-flex items-center justify-center rounded-full bg-emerald-600 px-7 py-3 text-sm font-bold text-white shadow-[0_16px_35px_rgba(5,150,105,0.25)] transition hover:-translate-y-0.5 hover:bg-emerald-700">Book Guided Setup</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    {{-- 7. Questions and close --}}
    <section class="bg-[#f6f1e8] py-16 sm:py-20">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-10 lg:grid-cols-[0.8fr_1.2fr] lg:items-start">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.24em] text-teal-700">Questions</p>
                    <h2 class="mt-4 text-4xl font-bold tracking-tight text-slate-950 sm:text-5xl">A simple next step.</h2>
                    <p class="mt-5 text-lg leading-8 text-slate-600">If you are not ready to talk yet, find a quick answer below. When you are ready, we are here to help.</p>
                    <a href="#guided-setup" class="mt-7 inline-flex items-center justify-center rounded-full bg-slate-950 px-6 py-3 text-sm font-bold text-white transition hover:bg-slate-800">Book a guided setup</a>
                </div>

                <div class="space-y-3">
                    @foreach($faqs as $faq)
                        <details class="group rounded-2xl border border-slate-950/10 bg-white p-5 shadow-sm">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-bold text-slate-950">
                                {{ $faq['question'] }}
                                <span class="text-xl text-teal-700 transition group-open:rotate-45">+</span>
                            </summary>
                            <p class="mt-4 max-w-2xl text-sm leading-6 text-slate-600">{{ $faq['answer'] }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endsection
