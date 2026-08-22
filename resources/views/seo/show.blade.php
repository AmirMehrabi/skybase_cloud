@extends('layouts.layout')

@section('title', $page['title'])
@section('meta_description', $page['meta_description'])
@section('og_title', $page['title'])
@section('og_description', $page['meta_description'])
@section('og_url', route($page['route']))
@section('canonical_url', route($page['route']))
@section('body_class', 'bg-[#f7f3ea] text-[#17211f]')

@section('content')
    <main>
        <section class="border-b border-[#17211f]/10 bg-[#fffdf8]">
            <div class="mx-auto grid max-w-6xl gap-12 px-4 py-16 sm:px-6 sm:py-20 lg:grid-cols-[0.9fr_1.1fr] lg:items-center lg:px-8 lg:py-24">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#145a5a]">{{ $page['eyebrow'] }}</p>
                    <h1 class="mt-5 text-5xl font-bold leading-[1.02] tracking-[-0.05em] sm:text-6xl">
                        {{ $page['headline'] }}
                    </h1>
                    <p class="mt-7 max-w-2xl text-lg leading-8 text-[#52605d]">{{ $page['intro'] }}</p>

                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('home') }}#guided-setup" class="inline-flex items-center justify-center rounded-xl bg-[#f5c542] px-6 py-3.5 font-semibold text-[#17211f] ring-1 ring-[#17211f]/10 transition hover:bg-[#ffd75c] focus:outline-none focus:ring-2 focus:ring-[#145a5a] focus:ring-offset-2 motion-reduce:transition-none">
                            Book a guided setup
                            <span class="ml-2" aria-hidden="true">→</span>
                        </a>
                        <a href="{{ route('pricing') }}" class="inline-flex items-center justify-center rounded-xl border border-[#0d2f35]/20 bg-white px-6 py-3.5 font-semibold text-[#0d2f35] transition hover:border-[#0d2f35]/40 focus:outline-none focus:ring-2 focus:ring-[#145a5a] focus:ring-offset-2 motion-reduce:transition-none">
                            See pricing
                        </a>
                    </div>
                    <p class="mt-4 text-sm font-medium text-[#52605d]">Free for up to 40 subscribers.</p>
                </div>

                <div class="overflow-hidden rounded-2xl border border-[#17211f]/15 bg-white shadow-[0_24px_70px_rgba(23,33,31,0.12)]">
                    <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">One connected workspace</p>
                        <p class="mt-1 text-xl font-semibold text-slate-900">Customer and network operations</p>
                    </div>
                    <div class="grid gap-3 bg-slate-100 p-4 sm:grid-cols-2 sm:p-5">
                        @foreach($page['highlights'] as $highlight)
                            <article class="rounded-xl border border-slate-200 bg-white p-4">
                                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#145a5a]/10 font-semibold text-[#145a5a]" aria-hidden="true">✓</span>
                                <h2 class="mt-4 text-lg font-semibold text-slate-900">{{ $highlight['title'] }}</h2>
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $highlight['copy'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section class="border-b border-[#17211f]/10">
            <div class="mx-auto grid max-w-6xl gap-10 px-4 py-16 sm:px-6 sm:py-20 lg:grid-cols-[0.75fr_1.25fr] lg:px-8 lg:py-24">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#145a5a]">Why it is different</p>
                    <h2 class="mt-4 text-4xl font-bold tracking-[-0.035em] sm:text-5xl">{{ $page['summary_title'] }}</h2>
                </div>
                <p class="text-lg leading-8 text-[#52605d] lg:pt-9">{{ $page['summary'] }}</p>
            </div>
        </section>

        <section class="border-b border-[#17211f]/10 bg-[#0d2f35] text-white">
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8 lg:py-24">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#f5c542]">A connected workflow</p>
                <h2 class="mt-4 max-w-3xl text-4xl font-bold tracking-[-0.035em] sm:text-5xl">{{ $page['workflow_title'] }}</h2>

                <div class="mt-12 grid gap-px overflow-hidden rounded-2xl border border-white/15 bg-white/15 lg:grid-cols-3">
                    @foreach($page['workflow'] as $workflowStep)
                        <article class="bg-[#0d2f35] p-6 sm:p-8">
                            <p class="text-sm font-semibold text-[#f5c542]">{{ $workflowStep['number'] }}</p>
                            <h3 class="mt-5 text-2xl font-semibold">{{ $workflowStep['title'] }}</h3>
                            <p class="mt-4 leading-7 text-teal-50/70">{{ $workflowStep['copy'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="border-b border-[#17211f]/10 bg-[#fffdf8]">
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8 lg:py-24">
                <div class="max-w-3xl">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#145a5a]">Common questions</p>
                    <h2 class="mt-4 text-4xl font-bold tracking-[-0.035em] sm:text-5xl">A clearer way to evaluate SkyBase.</h2>
                </div>

                <div class="mt-12 divide-y divide-[#17211f]/10 border-y border-[#17211f]/10">
                    @foreach($page['faqs'] as $faq)
                        <article class="grid gap-3 py-7 md:grid-cols-[0.8fr_1.2fr]">
                            <h3 class="text-xl font-semibold">{{ $faq['question'] }}</h3>
                            <p class="leading-7 text-[#52605d]">{{ $faq['answer'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section>
            <div class="mx-auto grid max-w-6xl gap-10 px-4 py-16 sm:px-6 sm:py-20 lg:grid-cols-[0.85fr_1.15fr] lg:px-8 lg:py-24">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#145a5a]">Explore SkyBase</p>
                    <h2 class="mt-4 text-4xl font-bold tracking-[-0.035em]">Related ISP workflows</h2>
                    <p class="mt-5 leading-7 text-[#52605d]">See how the customer, business, and network parts of the platform work together.</p>
                </div>

                <div class="divide-y divide-[#17211f]/10 border-y border-[#17211f]/10">
                    @foreach($relatedPages as $relatedPage)
                        <a href="{{ route($relatedPage['route']) }}" class="group block py-6 focus:outline-none focus:ring-2 focus:ring-[#145a5a] focus:ring-offset-4">
                            <p class="text-sm font-semibold uppercase tracking-[0.16em] text-[#145a5a]">{{ $relatedPage['eyebrow'] }}</p>
                            <p class="mt-2 flex items-center justify-between gap-5 text-xl font-semibold">
                                <span>{{ $relatedPage['headline'] }}</span>
                                <span class="transition group-hover:translate-x-1 motion-reduce:transition-none" aria-hidden="true">→</span>
                            </p>
                        </a>
                    @endforeach
                    <a href="{{ route('features') }}" class="group block py-6 focus:outline-none focus:ring-2 focus:ring-[#145a5a] focus:ring-offset-4">
                        <p class="text-sm font-semibold uppercase tracking-[0.16em] text-[#145a5a]">All features</p>
                        <p class="mt-2 flex items-center justify-between gap-5 text-xl font-semibold">
                            <span>Explore the complete SkyBase feature set.</span>
                            <span class="transition group-hover:translate-x-1 motion-reduce:transition-none" aria-hidden="true">→</span>
                        </p>
                    </a>
                </div>
            </div>
        </section>
    </main>
@endsection
