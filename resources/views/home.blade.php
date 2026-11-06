@extends('layouts.layout')

@section('title', 'ISP & WISP CRM Software for MikroTik Networks | SkyBase')
@section('meta_description', 'Manage subscribers, billing, MikroTik routers, RADIUS, and support with SkyBase ISP and WISP CRM software. Free for up to 40 subscribers.')
@section('og_title', 'ISP & WISP CRM Software for MikroTik Networks | SkyBase')
@section('og_description', 'Straightforward cloud operations for small and growing MikroTik ISPs, with a free plan and direct founder support.')
@section('og_url', url('/'))
@section('body_class', 'bg-[#f7f3ea] text-[#17211f]')

@php
    $capabilities = [
        [
            'title' => 'ISP CRM and subscriber management',
            'copy' => 'Open a customer record to review subscriptions, service status, invoices, payments, and support history. Give billing and support teams the context they need when a subscriber calls.',
            'route' => 'seo.isp-crm',
            'link' => 'Explore ISP CRM',
        ],
        [
            'title' => 'MikroTik management and RADIUS authentication',
            'copy' => 'Connect subscriber accounts to their routers and network access. Manage provisioning and inspect active sessions alongside the subscription behind each connection.',
            'route' => 'seo.mikrotik-isp-software',
            'link' => 'Explore MikroTik ISP software',
        ],
        [
            'title' => 'ISP billing and payment tracking',
            'copy' => 'Generate invoices, record payments, and follow unpaid balances from the customer account. Keep recurring billing and service information available to the same team.',
            'route' => 'seo.isp-billing-software',
            'link' => 'Explore ISP billing',
        ],
        [
            'title' => 'Network operations',
            'copy' => 'See router health, bandwidth, sites, alerts, and IP pool utilization without changing tools.',
        ],
        [
            'title' => 'Support & field work',
            'copy' => 'Coordinate wireless installations and follow-up visits with tickets, assignments, appointments, notes, and materials. Keep the work connected to the subscriber and service location.',
            'route' => 'seo.wisp-crm',
            'link' => 'Explore WISP CRM',
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
                    <h1 class="mt-5 text-4xl font-bold leading-tight tracking-[-0.035em] text-[#17211f] sm:text-5xl lg:text-6xl">
                        ISP and WISP CRM for MikroTik operators
                    </h1>
                    <p class="mt-7 max-w-xl text-lg leading-8 text-[#52605d]">
                        Manage subscribers, subscriptions, invoices, MikroTik routers, RADIUS authentication, and support in one cloud workspace. SkyBase helps small and growing internet service providers keep customer records connected to the services they deliver.
                    </p>

                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('auth.register') }}" class="inline-flex items-center justify-center rounded-xl bg-[#f5c542] px-6 py-3.5 font-semibold text-[#17211f] ring-1 ring-[#17211f]/10 transition hover:bg-[#ffd75c] focus:outline-none focus:ring-2 focus:ring-[#145a5a] focus:ring-offset-2 motion-reduce:transition-none">
                            Try SkyBase
                            <span class="ml-2" aria-hidden="true">→</span>
                        </a>
                        <a href="#guided-setup" class="inline-flex items-center justify-center rounded-xl border border-[#0d2f35]/20 bg-white px-6 py-3.5 font-semibold text-[#0d2f35] transition hover:border-[#0d2f35]/40 focus:outline-none focus:ring-2 focus:ring-[#145a5a] focus:ring-offset-2 motion-reduce:transition-none">
                            Book a guided setup
                        </a>
                    </div>

                    <p class="mt-4 text-sm font-medium text-[#52605d]">14-day trial. No credit card required. Free plan available for up to 40 subscribers.</p>
                    <p class="mt-2 text-sm text-[#52605d]">Run your ISP. Not your software. Explore on your own, or let us help you get started.</p>
                </div>

                <figure class="min-w-0">
                    <a href="{{ asset('images/product/customer.webp') }}" class="block rounded-2xl focus:outline-none focus:ring-2 focus:ring-[#145a5a] focus:ring-offset-4" aria-label="Open full-size SkyBase customer workspace screenshot">
                        <img src="{{ asset('images/product/customer.webp') }}" alt="SkyBase customer record showing service status, subscription access, and tabs for invoices, payments, and tickets. Private details are redacted." width="905" height="605" fetchpriority="high" class="h-auto w-full rounded-2xl border border-[#17211f]/15">
                    </a>
                    <figcaption class="mt-4 text-sm leading-6 text-[#52605d]">The SkyBase customer workspace, captured from a working instance. Private details have been redacted. Select the image to see it full size.</figcaption>
                </figure>
            </div>
        </section>

        <section class="border-b border-[#17211f]/10">
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8 lg:py-24">
                <div class="max-w-2xl">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#145a5a]">The work in one place</p>
                    <h2 class="mt-4 text-4xl font-bold tracking-[-0.035em] sm:text-5xl">Customer management, billing, and network operations for ISPs</h2>
                    <p class="mt-5 text-lg leading-8 text-[#52605d]">SkyBase is built around the daily operating work of a MikroTik ISP—not a checklist made for every kind of business.</p>
                </div>

                <div class="mt-12 divide-y divide-[#17211f]/10 border-y border-[#17211f]/10">
                    @foreach($capabilities as $capability)
                        <article class="grid gap-3 py-7 md:grid-cols-[0.7fr_1.3fr]">
                            <h3 class="text-2xl font-semibold">{{ $capability['title'] }}</h3>
                            <div>
                                <p class="leading-7 text-[#52605d]">{{ $capability['copy'] }}</p>
                                @isset($capability['route'])
                                    <a href="{{ route($capability['route']) }}" class="mt-3 inline-flex font-semibold text-[#145a5a] underline underline-offset-4">{{ $capability['link'] }} →</a>
                                @endisset
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <x-product-screenshot image="billing" heading="See invoices and payment status in your ISP billing workspace" caption="Filter invoices by status and date, review amounts, and open the customer account behind a balance." />

        <section class="border-b border-[#17211f]/10">
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">From a new subscriber to ongoing service</h2>
                <ol class="mt-8 grid gap-8 md:grid-cols-3">
                    <li><h3 class="text-xl font-semibold">1. Create the customer</h3><p class="mt-3 leading-7 text-[#52605d]">Record contact details and the service location, then add a subscription with the agreed plan and billing settings.</p></li>
                    <li><h3 class="text-xl font-semibold">2. Connect the service</h3><p class="mt-3 leading-7 text-[#52605d]">Associate the subscription with its router and network settings. Check authentication and service access before handing over to the customer.</p></li>
                    <li><h3 class="text-xl font-semibold">3. Manage billing and support</h3><p class="mt-3 leading-7 text-[#52605d]">Follow invoices, payments, and tickets from the customer record so the next person can pick up the work.</p></li>
                </ol>
                <p class="mt-8"><a href="{{ route('guide') }}" class="font-semibold text-[#145a5a] underline underline-offset-4">Follow the first subscription guide →</a></p>
                <p class="mt-4"><a href="{{ route('guides.mikrotik-billing') }}" class="font-semibold text-[#145a5a] underline underline-offset-4">Read the MikroTik billing checklist →</a></p>
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
                        <h2 class="mt-4 max-w-3xl text-4xl font-bold tracking-[-0.035em] sm:text-5xl">ISP CRM pricing — free for up to 40 subscribers</h2>
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

        <section class="border-b border-[#17211f]/10 bg-[#fffdf8]">
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">Common questions about SkyBase ISP CRM</h2>
                <div class="mt-8 divide-y divide-[#17211f]/10">
                    <article class="py-6"><h3 class="text-xl font-semibold">What is an ISP CRM?</h3><p class="mt-3 leading-7 text-[#52605d]">An ISP CRM connects customer records to internet subscriptions, billing, network access, and support. SkyBase gives your team a shared account history when a subscriber asks about an invoice or connection.</p></article>
                    <article class="py-6"><h3 class="text-xl font-semibold">Is SkyBase suitable for a wireless ISP?</h3><p class="mt-3 leading-7 text-[#52605d]">SkyBase is built for small and growing MikroTik operators, including wireless internet service providers (WISPs). Keep subscriber locations, installations, subscriptions, and support work connected. <a href="{{ route('seo.wisp-management-software') }}" class="font-semibold text-[#145a5a] underline underline-offset-4">See WISP management workflows.</a></p></article>
                    <article class="py-6"><h3 class="text-xl font-semibold">How does SkyBase work with MikroTik and RADIUS?</h3><p class="mt-3 leading-7 text-[#52605d]">Customer subscriptions connect to network settings and service access. RADIUS handles authentication, authorization, and accounting; billing records handle invoices and payments. Review your access method and router requirements during guided setup.</p></article>
                    <article class="py-6"><h3 class="text-xl font-semibold">How do the 14-day trial and Free plan differ?</h3><p class="mt-3 leading-7 text-[#52605d]">The trial gives you 14 days to evaluate SkyBase without a credit card. The Free plan has no monthly charge and supports up to 40 subscribers. Choose the Free plan if its limits fit your operation, or contact us about a paid plan before your trial ends. <a href="{{ route('pricing') }}" class="font-semibold text-[#145a5a] underline underline-offset-4">Compare plan features and limits.</a></p></article>
                    <article class="py-6"><h3 class="text-xl font-semibold">Can I move existing subscribers to SkyBase?</h3><p class="mt-3 leading-7 text-[#52605d]">Start with your subscriber count, export format, billing balances, and network dependencies. We will review the fit during guided setup; transfer support depends on the records and integrations involved. <a href="{{ route('guides.isp-software-migration') }}" class="font-semibold text-[#145a5a] underline underline-offset-4">Use the ISP migration checklist.</a></p></article>
                    <article class="py-6"><h3 class="text-xl font-semibold">Will my payment provider and currency work?</h3><p class="mt-3 leading-7 text-[#52605d]">Bring your payment provider, settlement currency, and invoicing requirements to guided setup so we can confirm compatibility. Invoice and payment tracking alone does not mean a particular online payment gateway is supported.</p></article>
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
                        <input type="hidden" name="form_token" value="{{ Illuminate\Support\Facades\Crypt::encryptString(hash('sha256', session()->getId()).'|'.now()->timestamp) }}">
                        <div class="absolute -left-[10000px]" aria-hidden="true"><x-ui.input.text name="website" label="" tabindex="-1" autocomplete="off" /></div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-ui.input.text name="business_name" label="Business name" :value="old('business_name')" error="{{ $errors->demoRequest->first('business_name') }}" placeholder="Your business" required class="h-12 rounded-xl border-[#17211f]/20 px-4 text-base shadow-none focus:border-[#145a5a] focus:ring-[#145a5a]" />
                            <x-ui.input.text name="contact_name" label="Your name" :value="old('contact_name')" error="{{ $errors->demoRequest->first('contact_name') }}" placeholder="Your name" required class="h-12 rounded-xl border-[#17211f]/20 px-4 text-base shadow-none focus:border-[#145a5a] focus:ring-[#145a5a]" />
                            <x-ui.input.text name="email" type="email" label="Work email" :value="old('email')" error="{{ $errors->demoRequest->first('email') }}" placeholder="you@business.com" required class="h-12 rounded-xl border-[#17211f]/20 px-4 text-base shadow-none focus:border-[#145a5a] focus:ring-[#145a5a]" />
                            <x-ui.input.text name="phone" type="tel" label="Phone number" :value="old('phone')" error="{{ $errors->demoRequest->first('phone') }}" placeholder="+33 7 58 35 14 73" required class="h-12 rounded-xl border-[#17211f]/20 px-4 text-base shadow-none focus:border-[#145a5a] focus:ring-[#145a5a]" />
                            <x-ui.input.text name="country" label="Country" :value="old('country')" error="{{ $errors->demoRequest->first('country') }}" placeholder="Where you operate" required class="h-12 rounded-xl border-[#17211f]/20 px-4 text-base shadow-none focus:border-[#145a5a] focus:ring-[#145a5a]" />
                            <div class="sm:col-span-2">
                                <x-ui.input.text name="customer_count" type="number" label="Approximate subscribers" :value="old('customer_count')" error="{{ $errors->demoRequest->first('customer_count') }}" placeholder="For example, 120" min="1" required class="h-12 rounded-xl border-[#17211f]/20 px-4 text-base shadow-none focus:border-[#145a5a] focus:ring-[#145a5a]" />
                            </div>
                            <div class="sm:col-span-2">
                                <x-ui.input.textarea name="message" label="What would you like to make easier? (optional)" :value="old('message')" error="{{ $errors->demoRequest->first('message') }}" placeholder="A sentence or two is plenty." rows="3" class="rounded-xl border-[#17211f]/20 px-4 py-3 text-base shadow-none focus:border-[#145a5a] focus:ring-[#145a5a]" />
                            </div>
                        </div>

                <x-turnstile error-bag="demoRequest" action="demo_request" />

                        <div class="mt-6 flex flex-col gap-4 border-t border-[#17211f]/10 pt-6 sm:flex-row sm:items-center sm:justify-between">
                            <p class="max-w-xs text-sm leading-6 text-[#52605d]">We’ll use your contact details only to arrange the setup.</p>
                            <button type="submit" class="rounded-xl bg-[#f5c542] px-6 py-3.5 font-semibold text-[#17211f] ring-1 ring-[#17211f]/10 transition hover:bg-[#ffd75c] focus:outline-none focus:ring-2 focus:ring-[#145a5a] focus:ring-offset-2 motion-reduce:transition-none">Book my guided setup</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </main>
@endsection
