@extends('layouts.layout')

@section('title', 'Contact SkyBase | Talk to Our Team')
@section('meta_description', 'Contact SkyBase Cloud for product questions, demos, migration guidance, and sales support.')
@section('meta_keywords', 'SkyBase contact, ISP software contact, MikroTik sales, demo request')
@section('og_title', 'Contact SkyBase | Talk to Our Team')
@section('og_description', 'Reach the SkyBase team for product questions, demos, and sales guidance.')
@section('og_url', url('/contact'))

@section('content')
    <section class="border-b border-gray-200 bg-gray-50 py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold uppercase tracking-[0.28em] text-blue-600">Contact</p>
                <h1 class="mt-4 text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl">Let’s talk about your ISP operations</h1>
                <p class="mt-5 max-w-2xl text-lg leading-8 text-gray-600">Ask about pricing, migrations, onboarding, or deployment options. We keep the process simple and reply with practical next steps.</p>
            </div>
        </div>
    </section>

    <section class="bg-white py-16 sm:py-20">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-[1fr_1.15fr] lg:px-8">
            <div class="space-y-6">
                <div class="rounded-[2rem] border border-gray-200 bg-white p-8 shadow-sm">
                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-gray-500">Call or message</p>
                    <h2 class="mt-3 text-2xl font-bold text-gray-900">We’re easy to reach</h2>
                    <div class="mt-6 space-y-5">
                        <div class="rounded-2xl bg-gray-50 p-5">
                            <p class="text-sm font-medium text-gray-500">Phone</p>
                            <a href="tel:+33758351473" class="mt-2 inline-flex text-lg font-semibold text-gray-900 hover:text-blue-600">+33 7 58 35 14 73</a>
                        </div>
                        <div class="rounded-2xl bg-gray-50 p-5">
                            <p class="text-sm font-medium text-gray-500">Email</p>
                            <a href="mailto:sales@skybase.app" class="mt-2 inline-flex text-lg font-semibold text-gray-900 hover:text-blue-600">sales@skybase.app</a>
                        </div>
                        <div class="rounded-2xl bg-blue-50 p-5">
                            <p class="text-sm font-medium text-blue-700">Best for</p>
                            <ul class="mt-3 space-y-2 text-sm leading-6 text-blue-900">
                                <li>Product and pricing questions</li>
                                <li>Migration planning and onboarding</li>
                                <li>On-premise and cloud deployment advice</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-[2rem] border border-gray-200 bg-white p-8 shadow-sm sm:p-10">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-blue-600">Send a message</p>
                        <h2 class="mt-3 text-2xl font-bold text-gray-900">Contact form</h2>
                        <p class="mt-2 text-sm leading-6 text-gray-600">Clear fields, no clutter, and your message is saved to the database and pushed to Telegram for quick follow-up.</p>
                    </div>
                </div>

                @if (session('contact_success'))
                    <div class="mt-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
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
                            placeholder="I’d like to discuss pricing"
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

                    <div class="mt-6 flex flex-col gap-3 border-t border-gray-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm leading-6 text-gray-500">We usually respond with the right next step instead of a generic message.</p>
                        <button type="submit" class="inline-flex items-center justify-center rounded-full bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
                            Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
