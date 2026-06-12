@extends('layouts.layout')

@section('title', $page['title'])
@section('meta_description', $page['meta_description'])
@section('meta_keywords', $page['meta_keywords'])
@section('og_title', $page['title'])
@section('og_description', $page['meta_description'])
@section('og_url', route($page['route']))
@section('body_class', 'bg-[#f6f1e8] text-slate-950')

@php
    $whatsappUrl = 'https://wa.me/33758351473?text='.rawurlencode('Hi SkyBase, I am comparing SkyBase with '.$page['competitor'].'.');
@endphp

@section('content')
    <section class="relative isolate overflow-hidden bg-[#0d2f35] text-white">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_15%_20%,rgba(34,197,94,0.26),transparent_28%),radial-gradient(circle_at_85%_12%,rgba(245,197,66,0.22),transparent_30%),linear-gradient(135deg,#09252b_0%,#0d2f35_50%,#123f3d_100%)]"></div>

        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:px-6 sm:py-20 lg:grid-cols-[0.96fr_1.04fr] lg:px-8 lg:py-24">
            <div class="flex flex-col justify-center">
                <p class="text-sm font-bold uppercase tracking-[0.24em] text-[#f5c542]">{{ $page['eyebrow'] }}</p>
                <h1 class="mt-5 max-w-4xl text-5xl font-bold tracking-[-0.05em] text-white sm:text-6xl">
                    {{ $page['headline'] }}
                </h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-teal-50/85">
                    {{ $page['intro'] }}
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('auth.register') }}" class="inline-flex items-center justify-center rounded-full bg-[#f5c542] px-7 py-4 text-base font-bold text-slate-950 shadow-[0_20px_50px_rgba(245,197,66,0.28)] transition hover:-translate-y-0.5 hover:bg-[#ffd95d]">
                        Start Trial
                    </a>
                    <a href="{{ route('pricing') }}" class="inline-flex items-center justify-center rounded-full border border-white/20 bg-white/10 px-7 py-4 text-base font-bold text-white backdrop-blur transition hover:bg-white hover:text-slate-950">
                        View Pricing
                    </a>
                </div>
            </div>

            <div class="rounded-[2.25rem] border border-white/15 bg-white/10 p-3 shadow-[0_35px_90px_rgba(0,0,0,0.35)] backdrop-blur-xl">
                <div class="overflow-hidden rounded-[1.75rem] bg-[#fbf7ed] text-slate-950">
                    <div class="border-b border-slate-950/10 bg-white px-5 py-4">
                        <p class="text-xs font-bold uppercase tracking-[0.24em] text-teal-800">Comparison snapshot</p>
                        <h2 class="mt-1 text-xl font-bold tracking-tight">SkyBase and {{ $page['competitor'] }}</h2>
                    </div>
                    <div class="grid gap-3 p-4 sm:grid-cols-3">
                        @foreach($page['cards'] as $card)
                            <div class="rounded-[1.5rem] border border-slate-950/10 bg-white p-4 shadow-sm">
                                <p class="text-sm font-semibold text-slate-500">{{ $card['label'] }}</p>
                                <p class="mt-3 text-3xl font-bold tracking-tight text-slate-950">{{ $card['value'] }}</p>
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $card['note'] }}</p>
                            </div>
                        @endforeach
                    </div>
                    <div class="border-t border-slate-950/10 p-4">
                        <div class="rounded-[1.5rem] bg-[#0d2f35] p-5 text-white">
                            <p class="text-xs font-bold uppercase tracking-[0.24em] text-emerald-200">Best fit</p>
                            <p class="mt-3 text-lg font-bold">SkyBase is strongest when your ISP runs on MikroTik and needs a simple cloud operating system.</p>
                            <p class="mt-3 text-sm leading-6 text-teal-50/75">Pricing references for {{ $page['competitor'] }} are based on public information checked on June 12, 2026. Confirm current pricing before making a buying decision.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-white py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-8 lg:grid-cols-[0.78fr_1.22fr] lg:items-start">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.24em] text-teal-700">Decision guide</p>
                    <h2 class="mt-4 text-4xl font-bold tracking-tight text-slate-950">Choose by operating model, not only by feature count.</h2>
                    <p class="mt-5 text-lg leading-8 text-slate-600">Both products can support ISP operations. The practical question is whether your team needs a focused MikroTik cloud workflow or a broader platform with more implementation surface.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-[1.75rem] border border-emerald-200 bg-emerald-50 p-6">
                        <p class="text-sm font-bold uppercase tracking-[0.24em] text-emerald-700">SkyBase fits</p>
                        <p class="mt-4 text-xl font-bold text-slate-950">{{ $page['best_for_skybase'] }}</p>
                    </div>
                    <div class="rounded-[1.75rem] border border-slate-200 bg-[#f6f1e8] p-6">
                        <p class="text-sm font-bold uppercase tracking-[0.24em] text-slate-600">{{ $page['competitor'] }} fits</p>
                        <p class="mt-4 text-xl font-bold text-slate-950">{{ $page['best_for_competitor'] }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-[#f6f1e8] py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-10 flex flex-col justify-between gap-5 md:flex-row md:items-end">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.24em] text-emerald-700">Side-by-side comparison</p>
                    <h2 class="mt-4 text-4xl font-bold tracking-tight text-slate-950">SkyBase vs {{ $page['competitor'] }}</h2>
                </div>
                <p class="max-w-xl text-base leading-7 text-slate-600">{{ $page['pricing_note'] }}</p>
            </div>

            <div class="overflow-hidden rounded-[2rem] border border-slate-950/10 bg-white shadow-xl">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px]">
                        <thead>
                            <tr class="border-b border-slate-950/10 bg-[#0d2f35] text-white">
                                <th class="px-6 py-4 text-left text-sm font-bold">Area</th>
                                <th class="px-6 py-4 text-left text-sm font-bold">SkyBase</th>
                                <th class="px-6 py-4 text-left text-sm font-bold">{{ $page['competitor'] }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($page['rows'] as $row)
                                <tr class="border-b border-slate-950/10 {{ $loop->even ? 'bg-[#fbf7ed]' : 'bg-white' }}">
                                    <td class="px-6 py-5 text-sm font-bold text-slate-950">{{ $row['feature'] }}</td>
                                    <td class="px-6 py-5 text-sm leading-6 text-slate-700">{{ $row['skybase'] }}</td>
                                    <td class="px-6 py-5 text-sm leading-6 text-slate-700">{{ $row['competitor'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm leading-6 text-amber-900">
                {{ $page['competitor'] }} pricing and feature descriptions can change. The comparison above summarizes public information checked on June 12, 2026 and links to the source page:
                <a href="{{ $page['source_url'] }}" target="_blank" rel="noopener" class="font-bold underline decoration-amber-700/40 underline-offset-4">{{ $page['source_label'] }}</a>.
            </div>
        </div>
    </section>

    <section class="bg-white py-16 sm:py-20">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[0.95fr_1.05fr] lg:px-8 lg:items-center">
            <div class="rounded-[2rem] bg-[#102f34] p-8 text-white shadow-xl sm:p-10">
                <p class="text-sm font-bold uppercase tracking-[0.24em] text-[#f5c542]">Pricing fit</p>
                <h2 class="mt-4 text-4xl font-bold tracking-tight">A smaller ISP should not need a large starting commitment to modernize operations.</h2>
                <p class="mt-5 text-lg leading-8 text-teal-50/80">SkyBase starts with a free cloud plan for up to 40 subscribers. Paid plans begin at $69/month and include cloud hosting, automatic updates, and core ISP management workflows.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                @foreach(['No setup fees', 'Cancel anytime', 'Cloud hosting included', 'MikroTik-first workflows'] as $highlight)
                    <div class="rounded-[1.75rem] border border-slate-950/10 bg-[#fbf7ed] p-6">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-lg font-bold text-emerald-700">&check;</span>
                        <p class="mt-4 text-lg font-bold text-slate-950">{{ $highlight }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-[#f6f1e8] py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-10 max-w-3xl">
                <p class="text-sm font-bold uppercase tracking-[0.24em] text-teal-700">FAQ</p>
                <h2 class="mt-4 text-4xl font-bold tracking-tight text-slate-950">Questions about SkyBase as a {{ $page['competitor'] }} alternative</h2>
            </div>

            <div class="grid gap-5 lg:grid-cols-3">
                @foreach($page['faqs'] as $faq)
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
            <p class="text-sm font-bold uppercase tracking-[0.24em] text-[#f5c542]">Compare with your own setup</p>
            <h2 class="mt-4 text-4xl font-bold tracking-tight sm:text-5xl">See whether SkyBase is the right fit before you switch.</h2>
            <p class="mx-auto mt-5 max-w-2xl text-lg leading-8 text-teal-50/80">Share your customer count, routers, Radius setup, and current billing process. We will walk through pricing fit and the practical migration path.</p>
            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ route('auth.register') }}" class="inline-flex items-center justify-center rounded-full bg-[#f5c542] px-8 py-4 text-base font-bold text-slate-950 transition hover:bg-[#ffd95d]">Start Trial</a>
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center rounded-full border border-white/20 bg-white/10 px-8 py-4 text-base font-bold text-white transition hover:bg-white hover:text-slate-950">Message WhatsApp</a>
            </div>
        </div>
    </section>
@endsection
