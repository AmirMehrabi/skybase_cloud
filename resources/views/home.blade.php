@extends('layouts.layout')

@section('title', 'SkyBase Cloud | WISP & ISP Management for MikroTik Operators')
@section('meta_description', 'Straightforward WISP and ISP management software for customers, subscriptions, MikroTik routers, RADIUS, billing, and support. Free for up to 40 subscribers.')
@section('meta_keywords', 'ISP management software, MikroTik management, RADIUS server, WISP software, ISP billing, PPPoE management')
@section('og_title', 'SkyBase Cloud - Run Your ISP, Not Your Software')
@section('og_description', 'Straightforward cloud operations for small and growing MikroTik ISPs, with a free plan and direct founder support.')
@section('og_url', url('/'))
@section('body_class', 'bg-[#f7f3ea] text-[#17211f]')

@php
    $capabilities = [
        [
            'title' => 'Customers & subscriptions',
            'copy' => 'Keep account details, plans, service state, usage, and next actions together.',
        ],
        [
            'title' => 'MikroTik & RADIUS',
            'copy' => 'Connect routers, authenticate subscribers, provision services, and inspect active sessions.',
        ],
        [
            'title' => 'Billing & payments',
            'copy' => 'Generate invoices, follow payment status, and keep recurring billing work moving.',
        ],
        [
            'title' => 'Network operations',
            'copy' => 'See router health, bandwidth, sites, alerts, and IP pool utilization without changing tools.',
        ],
        [
            'title' => 'Support & field work',
            'copy' => 'Track tickets, assignments, appointments, notes, materials, and provisioning work.',
        ],
        [
            'title' => 'Customer portal',
            'copy' => 'Give customers a clear place to see service, invoices, notifications, and support requests.',
        ],
    ];

    $pricingPlans = [
        ['name' => 'Free', 'price' => '$0', 'limit' => 'Up to 40 subscribers', 'note' => 'Free forever'],
        ['name' => 'Starter', 'price' => '$69', 'limit' => 'Up to 150 subscribers', 'note' => 'For growing ISPs'],
        ['name' => 'Growth', 'price' => '$129', 'limit' => 'Up to 300 subscribers', 'note' => 'For scaling ISPs'],
    ];
@endphp

@section('content')
    <main>
        <section class="border-b border-[#17211f]/10 bg-[#fffdf8]">
            <div class="mx-auto grid max-w-6xl gap-14 px-4 py-16 sm:px-6 sm:py-20 lg:grid-cols-[0.82fr_1.18fr] lg:items-center lg:px-8 lg:py-28">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#145a5a]">For small and growing MikroTik ISPs</p>
                    <h1 class="mt-5 text-5xl font-bold leading-[0.98] tracking-[-0.055em] text-[#17211f] sm:text-6xl lg:text-7xl">
                        Run your ISP.<br>Not your software.
                    </h1>
                    <p class="mt-7 max-w-xl text-lg leading-8 text-[#52605d]">
                        SkyBase keeps customers, subscriptions, routers, billing, and support in one straightforward workspace—so your team can spend less time maintaining systems and more time serving customers.
                    </p>

                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        <a href="#guided-setup" class="inline-flex items-center justify-center rounded-xl bg-[#f5c542] px-6 py-3.5 font-semibold text-[#17211f] ring-1 ring-[#17211f]/10 transition hover:bg-[#ffd75c] focus:outline-none focus:ring-2 focus:ring-[#145a5a] focus:ring-offset-2 motion-reduce:transition-none">
                            Book a guided setup
                            <span class="ml-2" aria-hidden="true">→</span>
                        </a>
                        <a href="#pricing" class="inline-flex items-center justify-center rounded-xl border border-[#0d2f35]/20 bg-white px-6 py-3.5 font-semibold text-[#0d2f35] transition hover:border-[#0d2f35]/40 focus:outline-none focus:ring-2 focus:ring-[#145a5a] focus:ring-offset-2 motion-reduce:transition-none">
                            See pricing
                        </a>
                    </div>

                    <p class="mt-4 text-sm font-medium text-[#52605d]">Free for up to 40 subscribers.</p>
                </div>

                <div class="overflow-hidden rounded-2xl border border-[#17211f]/15 bg-white shadow-[0_24px_70px_rgba(23,33,31,0.12)]">
                    <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-4 py-3 sm:px-5">
                        <div class="flex gap-1.5" aria-hidden="true">
                            <span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span>
                        </div>
                        <p class="text-xs font-semibold text-slate-500">Representative SkyBase dashboard</p>
                    </div>

                    <div class="grid bg-slate-100 sm:grid-cols-[126px_1fr]">
                        <aside class="hidden bg-[#0d2f35] p-4 text-white sm:block">
                            <p class="text-base font-semibold">SkyBase</p>
                            <div class="mt-8 space-y-2 text-[10px] font-semibold text-teal-50/70">
                                <p class="rounded-lg bg-white/10 px-3 py-2.5 text-white">Dashboard</p>
                                <p class="px-3 py-2">Customers</p>
                                <p class="px-3 py-2">Subscriptions</p>
                                <p class="px-3 py-2">Routers</p>
                                <p class="px-3 py-2">Billing</p>
                                <p class="px-3 py-2">Support</p>
                            </div>
                        </aside>

                        <div class="p-3 sm:p-5">
                            <div class="rounded-xl bg-[#132e37] p-4 text-white">
                                <p class="text-[10px] uppercase tracking-[0.18em] text-sky-100/70">Tenant dashboard</p>
                                <div class="mt-2 flex items-end justify-between gap-4">
                                    <p class="text-xl font-semibold">Today at your ISP</p>
                                    <span class="rounded-full bg-emerald-400/15 px-2 py-1 text-[9px] font-semibold text-emerald-100">Active</span>
                                </div>
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-3 xl:grid-cols-4">
                                @foreach([
                                    ['label' => 'Customers', 'value' => 'In one place'],
                                    ['label' => 'Subscriptions', 'value' => 'Clear status'],
                                    ['label' => 'Routers', 'value' => 'Health visible'],
                                    ['label' => 'Billing', 'value' => 'Follow-up ready'],
                                ] as $dashboardItem)
                                    <div class="rounded-xl border border-slate-200 bg-white p-3">
                                        <p class="text-[9px] font-semibold uppercase tracking-wider text-slate-400">{{ $dashboardItem['label'] }}</p>
                                        <p class="mt-2 text-base font-semibold text-slate-900 sm:text-lg">{{ $dashboardItem['value'] }}</p>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-3 grid gap-3 xl:grid-cols-[1.35fr_0.65fr]">
                                <div class="rounded-xl border border-slate-200 bg-white p-4">
                                    <p class="text-xs font-semibold text-slate-800">Recent activity</p>
                                    <div class="mt-4 space-y-3" aria-hidden="true">
                                        <div class="h-2 rounded-full bg-slate-100"><div class="h-2 w-4/5 rounded-full bg-sky-500"></div></div>
                                        <div class="h-2 rounded-full bg-slate-100"><div class="h-2 w-3/5 rounded-full bg-emerald-500"></div></div>
                                        <div class="h-2 rounded-full bg-slate-100"><div class="h-2 w-2/3 rounded-full bg-amber-400"></div></div>
                                    </div>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-white p-4">
                                    <p class="text-xs font-semibold text-slate-800">Router health</p>
                                    <div class="mt-4 space-y-2 text-[10px]">
                                        <p class="flex justify-between"><span>Core router</span><span class="text-emerald-600">Online</span></p>
                                        <p class="flex justify-between"><span>Edge router</span><span class="text-emerald-600">Online</span></p>
                                        <p class="flex justify-between"><span>IP pools</span><span class="text-slate-500">Visible</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="border-b border-[#17211f]/10">
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8 lg:py-24">
                <div class="max-w-2xl">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#145a5a]">The work in one place</p>
                    <h2 class="mt-4 text-4xl font-bold tracking-[-0.035em] sm:text-5xl">Capable where it matters. Quiet everywhere else.</h2>
                    <p class="mt-5 text-lg leading-8 text-[#52605d]">SkyBase is built around the daily operating work of a MikroTik ISP—not a checklist made for every kind of business.</p>
                </div>

                <div class="mt-12 divide-y divide-[#17211f]/10 border-y border-[#17211f]/10">
                    @foreach($capabilities as $capability)
                        <article class="grid gap-3 py-7 md:grid-cols-[0.7fr_1.3fr]">
                            <h3 class="text-2xl font-semibold">{{ $capability['title'] }}</h3>
                            <p class="leading-7 text-[#52605d]">{{ $capability['copy'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="about" class="bg-[#0d2f35] text-white">
            <div class="mx-auto grid max-w-6xl gap-12 px-4 py-16 sm:px-6 sm:py-20 lg:grid-cols-2 lg:px-8 lg:py-24">
                <article>
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#f5c542]">A note from the founder</p>
                    <div class="mt-6 space-y-5 text-lg leading-8 text-teal-50/80">
                        <p>Dear ISP owner,</p>
                        <p>You should not have to become a full-time systems administrator just to run a reliable internet business. SkyBase exists to make the daily work clearer—and to give you a direct path to the person building it.</p>
                        <p>Bring your current workflow and the parts slowing you down. We will look at them together and find a practical first step.</p>
                    </div>
                    <p class="mt-8 text-xl font-semibold">Abbie Barlowe</p>
                    <p class="mt-1 text-sm text-teal-50/60">Founder, SkyBase Cloud</p>
                </article>

                <blockquote class="border-l border-white/20 pl-6 sm:pl-8">
                    <p class="text-3xl font-semibold leading-tight tracking-[-0.03em]">“Since adopting SkyBase, we've had complete peace of mind. Their system is reliable and their support is always available, responsive and personalised to our needs. Truly exceptional service!”</p>
                    <footer class="mt-7 text-sm text-teal-50/65">Makhado T <span class="mx-2">·</span> Operations Director, Ultech Solutions</footer>
                </blockquote>
            </div>
        </section>

        <section id="pricing" class="border-b border-[#17211f]/10 bg-[#fffdf8]">
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8 lg:py-24">
                <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#145a5a]">Simple cloud pricing</p>
                        <h2 class="mt-4 max-w-3xl text-4xl font-bold tracking-[-0.035em] sm:text-5xl">Start small. Pay more when you grow.</h2>
                    </div>
                    <a href="{{ route('pricing') }}" class="font-semibold text-[#145a5a] underline decoration-[#145a5a]/30 underline-offset-4 transition hover:decoration-[#145a5a] focus:outline-none focus:ring-2 focus:ring-[#145a5a] focus:ring-offset-4 motion-reduce:transition-none">See every plan →</a>
                </div>

                <div class="mt-12 grid border-y border-[#17211f]/10 md:grid-cols-3 md:divide-x md:divide-[#17211f]/10">
                    @foreach($pricingPlans as $pricingPlan)
                        <article class="border-t border-[#17211f]/10 py-8 first:border-t-0 md:border-t-0 md:px-8 md:first:pl-0 md:last:pr-0">
                            <p class="text-xl font-semibold">{{ $pricingPlan['name'] }}</p>
                            <p class="mt-5 text-5xl font-bold">{{ $pricingPlan['price'] }}<span class="text-sm font-medium text-[#52605d]">/month</span></p>
                            <p class="mt-4 text-[#52605d]">{{ $pricingPlan['limit'] }}. {{ $pricingPlan['note'] }}.</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="guided-setup">
            <div class="mx-auto grid max-w-6xl gap-12 px-4 py-16 sm:px-6 sm:py-20 lg:grid-cols-[0.72fr_1.28fr] lg:px-8 lg:py-24">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#145a5a]">Book a guided setup</p>
                    <h2 class="mt-4 text-4xl font-bold tracking-[-0.035em] sm:text-5xl">Let’s look at how your ISP works today.</h2>
                    <p class="mt-5 text-lg leading-8 text-[#52605d]">A focused conversation with the founder about your current tools, what takes too long, and the simplest useful first step.</p>
                    <div class="mt-8 space-y-3 text-sm font-semibold">
                        <p><span class="mr-2 text-[#15803d]" aria-hidden="true">✓</span>A walkthrough shaped around your business</p>
                        <p><span class="mr-2 text-[#15803d]" aria-hidden="true">✓</span>Honest answers about product fit</p>
                        <p><span class="mr-2 text-[#15803d]" aria-hidden="true">✓</span>No sales handoff</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-[#17211f]/15 bg-[#fffdf8] p-5 shadow-[0_18px_50px_rgba(23,33,31,0.08)] sm:p-8">
                    @if (session('demo_request_success'))
                        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800" role="status">
                            {{ session('demo_request_success') }}
                        </div>
                    @endif

                    <form action="{{ route('demo-requests.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="requested_plan" value="Guided setup from homepage">
                        <input type="hidden" name="source_page" value="home">

                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-ui.input.text name="business_name" label="Business name" :value="old('business_name')" error="{{ $errors->demoRequest->first('business_name') }}" placeholder="Your business" required class="h-12 rounded-xl border-[#17211f]/20 px-4 text-base shadow-none focus:border-[#145a5a] focus:ring-[#145a5a]" />
                            <x-ui.input.text name="contact_name" label="Your name" :value="old('contact_name')" error="{{ $errors->demoRequest->first('contact_name') }}" placeholder="Your name" required class="h-12 rounded-xl border-[#17211f]/20 px-4 text-base shadow-none focus:border-[#145a5a] focus:ring-[#145a5a]" />
                            <x-ui.input.text name="email" type="email" label="Work email" :value="old('email')" error="{{ $errors->demoRequest->first('email') }}" placeholder="you@business.com" required class="h-12 rounded-xl border-[#17211f]/20 px-4 text-base shadow-none focus:border-[#145a5a] focus:ring-[#145a5a]" />
                            <x-ui.input.text name="country" label="Country" :value="old('country')" error="{{ $errors->demoRequest->first('country') }}" placeholder="Where you operate" required class="h-12 rounded-xl border-[#17211f]/20 px-4 text-base shadow-none focus:border-[#145a5a] focus:ring-[#145a5a]" />
                            <div class="sm:col-span-2">
                                <x-ui.input.text name="customer_count" type="number" label="Approximate subscribers" :value="old('customer_count')" error="{{ $errors->demoRequest->first('customer_count') }}" placeholder="For example, 120" min="1" required class="h-12 rounded-xl border-[#17211f]/20 px-4 text-base shadow-none focus:border-[#145a5a] focus:ring-[#145a5a]" />
                            </div>
                            <div class="sm:col-span-2">
                                <x-ui.input.textarea name="message" label="What would you like to make easier? (optional)" :value="old('message')" error="{{ $errors->demoRequest->first('message') }}" placeholder="A sentence or two is plenty." rows="3" class="rounded-xl border-[#17211f]/20 px-4 py-3 text-base shadow-none focus:border-[#145a5a] focus:ring-[#145a5a]" />
                            </div>
                        </div>

                        <div class="mt-6 flex flex-col gap-4 border-t border-[#17211f]/10 pt-6 sm:flex-row sm:items-center sm:justify-between">
                            <p class="max-w-xs text-sm leading-6 text-[#52605d]">We’ll use your email only to arrange the setup.</p>
                            <button type="submit" class="rounded-xl bg-[#f5c542] px-6 py-3.5 font-semibold text-[#17211f] ring-1 ring-[#17211f]/10 transition hover:bg-[#ffd75c] focus:outline-none focus:ring-2 focus:ring-[#145a5a] focus:ring-offset-2 motion-reduce:transition-none">Book my guided setup</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </main>
@endsection
