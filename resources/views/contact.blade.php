@extends('layouts.layout')

@section('title', 'Contact SkyBase | Talk to Our Team')
@section('meta_description', 'Contact SkyBase Cloud for product questions, demos, migration guidance, and sales support.')
@section('meta_keywords', 'SkyBase contact, ISP software contact, MikroTik sales, demo request')
@section('og_title', 'Contact SkyBase | Talk to Our Team')
@section('og_description', 'Reach the SkyBase team for product questions, demos, and sales guidance.')
@section('og_url', url('/contact'))
@section('body_class', 'bg-[#f6f1e8] text-slate-950')

@section('content')
    <section class="relative isolate overflow-hidden bg-[#0d2f35] py-16 text-white sm:py-20">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_18%_20%,rgba(34,197,94,0.26),transparent_28%),radial-gradient(circle_at_85%_10%,rgba(245,197,66,0.22),transparent_30%),linear-gradient(135deg,#09252b_0%,#0d2f35_52%,#123f3d_100%)]"></div>
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="text-sm font-bold uppercase tracking-[0.24em] text-[#f5c542]">Contact</p>
                <h1 class="mt-4 text-4xl font-bold tracking-tight text-white sm:text-5xl">Talk to SkyBase about your ISP operations.</h1>
                <p class="mt-5 max-w-2xl text-lg leading-8 text-teal-50/85">Ask about pricing, onboarding, migrations, or deployment options. We reply with practical next steps for your network and team.</p>
            </div>
        </div>
    </section>

    <section class="bg-[#f6f1e8] py-16 sm:py-20">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[0.9fr_1.1fr] lg:px-8">
            <div class="space-y-5">
                <div class="rounded-[2rem] bg-[#172a2c] p-8 text-white shadow-xl">
                    <p class="text-sm font-bold uppercase tracking-[0.24em] text-[#f5c542]">Call or message</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight">We are easy to reach.</h2>
                    <div class="mt-6 space-y-4">
                        <div class="rounded-2xl border border-white/10 bg-white/[0.06] p-5">
                            <p class="text-sm font-semibold text-teal-50/70">WhatsApp / Phone</p>
                            <a href="tel:+33758351473" class="mt-2 inline-flex text-lg font-bold text-white hover:text-[#f5c542]">+33 7 58 35 14 73</a>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/[0.06] p-5">
                            <p class="text-sm font-semibold text-teal-50/70">Email</p>
                            <a href="mailto:sales@skybase.app" class="mt-2 inline-flex text-lg font-bold text-white hover:text-[#f5c542]">sales@skybase.app</a>
                        </div>
                    </div>
                </div>

                <div class="rounded-[2rem] border border-emerald-200 bg-white p-8 shadow-xl">
                    <p class="text-sm font-bold uppercase tracking-[0.24em] text-emerald-700">Best for</p>
                    <div class="mt-5 space-y-3">
                        @foreach(['Product and pricing questions', 'Migration planning and onboarding', 'On-premise and cloud deployment advice'] as $contactReason)
                            <div class="flex gap-3 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-slate-800 ring-1 ring-emerald-100">
                                <span class="mt-1.5 h-2 w-2 shrink-0 bg-emerald-500"></span>
                                <span>{{ $contactReason }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="rounded-[2rem] border border-slate-950/10 bg-[#fbf7ed] p-6 shadow-xl sm:p-8">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-[0.24em] text-teal-700">Send a message</p>
                        <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950">Contact form</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Your message is saved to the database and pushed to Telegram for quick follow-up.</p>
                    </div>
                </div>

                @if (session('contact_success'))
                    <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                        {{ session('contact_success') }}
                    </div>
                @endif

                <form action="{{ route('contact.store') }}" method="POST" class="mt-8">
                    @csrf

                    <div class="grid gap-5 md:grid-cols-2">
                        <x-ui.input.text
                            name="name"
                            label="Your name"
                            :value="old('name')"
                            error="{{ $errors->contactInquiry->first('name') }}"
                            placeholder="Jane Doe"
                            required
                        />
                        <x-ui.input.text
                            name="email"
                            type="email"
                            label="Email address"
                            :value="old('email')"
                            error="{{ $errors->contactInquiry->first('email') }}"
                            placeholder="jane@example.com"
                            required
                        />
                        <x-ui.input.text
                            name="phone"
                            type="tel"
                            label="Phone number"
                            :value="old('phone')"
                            error="{{ $errors->contactInquiry->first('phone') }}"
                            placeholder="+33 7 58 35 14 73"
                        />
                        <x-ui.input.text
                            name="company_name"
                            label="Company name"
                            :value="old('company_name')"
                            error="{{ $errors->contactInquiry->first('company_name') }}"
                            placeholder="SkyNet Fiber"
                        />
                    </div>

                    <div class="mt-1">
                        <x-ui.input.text
                            name="subject"
                            label="Subject"
                            :value="old('subject')"
                            error="{{ $errors->contactInquiry->first('subject') }}"
                            placeholder="I would like to discuss pricing"
                            required
                        />
                    </div>

                    <div class="mt-1">
                        <x-ui.input.textarea
                            name="message"
                            label="How can we help?"
                            :value="old('message')"
                            error="{{ $errors->contactInquiry->first('message') }}"
                            placeholder="Tell us a bit about your ISP, what you need, and any timeline you have in mind."
                            rows="6"
                            required
                        />
                    </div>

                    <div class="mt-6 flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm leading-6 text-slate-600">We usually respond with the right next step instead of a generic message.</p>
                        <button type="submit" class="inline-flex items-center justify-center bg-emerald-600 px-6 py-3 text-sm font-bold text-white shadow-[0_16px_35px_rgba(5,150,105,0.25)] transition hover:-translate-y-0.5 hover:bg-emerald-700">
                            Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
