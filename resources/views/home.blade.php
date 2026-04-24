@extends('layouts.layout')

@section('title', 'SkyBase Cloud - ISP Management Software for MikroTik Networks')
@section('meta_description', 'SkyBase Cloud - Cloud Control Plane for MikroTik ISPs. Manage authentication, monitor routers, and provision customers from one secure platform. The best ISP management software for WISPs.')
@section('meta_keywords', 'ISP management software, MikroTik management, Radius server, WISP software, ISP billing and monitoring, PPPoE management, MikroTik provisioning')
@section('og_title', 'SkyBase Cloud - ISP Management Software for MikroTik Networks')
@section('og_description', 'Cloud-based control plane for WISPs. Manage MikroTik routers, Radius authentication, and customer provisioning from one platform.')
@section('og_url', url('/'))

@section('content')
<!-- Hero Section -->
    <section class=" bg-gradient-to-b from-blue-900 via-blue-700 to-blue-600 py-20 lg:py-16">
        <div class="mx-auto px-4 sm:px-6 lg:px-8  max-w-7/8 grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="max-w-4xl mx-auto text-left flex flex-col items-start justify-center">
                <h1 class="text-4xl sm:text-5xl font-bold text-white leading-tight mb-6">
                    Cloud Control Plane for MikroTik ISPs
                </h1>
                <p class="text-xl text-white mb-8 leading-relaxed">
                    SkyBase Cloud helps WISPs and ISPs manage authentication, monitor routers, and provision customers from one secure, scalable platform. Replace manual MikroTik configuration and self-hosted Radius servers with a reliable cloud solution.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-start gap-4 mb-8">
                    <a href="{{ route('auth.register') }}" class="inline-flex items-center justify-center px-6 py-3 text-lg font-medium text-white bg-blue-600 rounded-2xl hover:bg-blue-700 transition-colors w-full sm:w-auto">Start Free Trial</a>
                    <a href="#demo" class="inline-flex items-center justify-center px-6 py-3 text-lg font-medium text-gray-700 bg-white border border-gray-300 rounded-2xl hover:bg-gray-50 transition-colors w-full sm:w-auto">Book a Demo</a>
                </div>
                <p class="text-lg text-gray-50">
                    Built for real ISPs. Designed for MikroTik networks. Trusted globally.
                </p>
            </div>

            <!-- Dashboard Preview -->
            <div class="mt-16 mx-auto w-full max-w-full">
                <div class="rounded-[2.25rem] border border-slate-700/60 bg-slate-900 p-3 shadow-[0_30px_80px_rgba(15,23,42,0.45)] ring-1 ring-white/10">
                    <div class="overflow-hidden rounded-[1.7rem] border border-slate-800 bg-slate-950">
                        <div class="flex items-center justify-between border-b border-slate-800 bg-slate-900/95 px-4 py-2.5">
                            <div class="flex items-center gap-2">
                                <span class="h-2.5 w-2.5 rounded-full bg-rose-400"></span>
                                <span class="h-2.5 w-2.5 rounded-full bg-amber-300"></span>
                                <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                            </div>
                            <div class="rounded-full border border-slate-700 bg-slate-950 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.25em] text-slate-400">
                                SkyBase dashboard
                            </div>
                        </div>

                        <div class="aspect-[16/10] overflow-hidden bg-slate-100">
                            <div class="scale-[0.58] origin-top-left p-4 sm:scale-[0.62] lg:scale-[0.64]">
                                <div class="w-[155%] space-y-4 rounded-[2rem] bg-slate-100 p-4">
                                    <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-sky-900 text-white shadow-sm">
                                        <div class="grid gap-6 px-5 py-5 lg:grid-cols-[minmax(0,1.7fr)_minmax(240px,1fr)] lg:items-end">
                                            <div class="space-y-4">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.24em] text-sky-100 ring-1 ring-inset ring-white/15">
                                                        Tenant dashboard
                                                    </span>
                                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-[10px] font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                                        Active
                                                    </span>
                                                    <span class="inline-flex items-center rounded-full bg-amber-400/15 px-3 py-1 text-[10px] font-medium text-amber-100 ring-1 ring-inset ring-amber-300/30">
                                                        Trial ends in 9 days
                                                    </span>
                                                </div>

                                                <div class="space-y-2">
                                                    <p class="text-xs font-medium text-sky-100/80">Welcome back</p>
                                                    <h3 class="text-2xl font-semibold tracking-tight sm:text-3xl">North Ridge Wireless</h3>
                                                    <p class="max-w-2xl text-xs leading-5 text-slate-200 sm:text-sm">
                                                        A live operations view of subscribers, service health, routers, and IP capacity scoped to a single tenant.
                                                    </p>
                                                </div>

                                                <div class="flex flex-wrap gap-2">
                                                    <span class="inline-flex items-center rounded-xl bg-white px-3 py-2 text-xs font-semibold text-slate-900">Add customer</span>
                                                    <span class="inline-flex items-center rounded-xl bg-sky-400/15 px-3 py-2 text-xs font-semibold text-white ring-1 ring-inset ring-sky-200/20">Create subscription</span>
                                                    <span class="inline-flex items-center rounded-xl bg-white/10 px-3 py-2 text-xs font-semibold text-white ring-1 ring-inset ring-white/15">Add router</span>
                                                </div>
                                            </div>

                                            <div class="grid gap-3 rounded-[24px] border border-white/10 bg-white/5 p-4 backdrop-blur-sm sm:grid-cols-2">
                                                <div class="rounded-2xl border border-white/10 bg-black/10 p-4">
                                                    <p class="text-[10px] font-medium uppercase tracking-[0.22em] text-slate-300">Active subscribers</p>
                                                    <p class="mt-2 text-2xl font-semibold tracking-tight text-white">1,248</p>
                                                    <p class="mt-2 text-xs leading-5 text-slate-300">+32 this month</p>
                                                </div>
                                                <div class="rounded-2xl border border-white/10 bg-black/10 p-4">
                                                    <p class="text-[10px] font-medium uppercase tracking-[0.22em] text-slate-300">Routers online</p>
                                                    <p class="mt-2 text-2xl font-semibold tracking-tight text-white">18 / 19</p>
                                                    <p class="mt-2 text-xs leading-5 text-slate-300">One site needs attention</p>
                                                </div>
                                                <div class="rounded-2xl border border-white/10 bg-black/10 p-4">
                                                    <p class="text-[10px] font-medium uppercase tracking-[0.22em] text-slate-300">MRR</p>
                                                    <p class="mt-2 text-2xl font-semibold tracking-tight text-white">$48.2k</p>
                                                    <p class="mt-2 text-xs leading-5 text-slate-300">Recurring revenue snapshot</p>
                                                </div>
                                                <div class="rounded-2xl border border-white/10 bg-black/10 p-4">
                                                    <p class="text-[10px] font-medium uppercase tracking-[0.22em] text-slate-300">IP capacity</p>
                                                    <p class="mt-2 text-2xl font-semibold tracking-tight text-white">74%</p>
                                                    <p class="mt-2 text-xs leading-5 text-slate-300">Pool usage across sites</p>
                                                </div>
                                            </div>
                                        </div>
                                    </section>

                                    <section class="grid grid-cols-2 gap-3 xl:grid-cols-5">
                                        <div class="rounded-[22px] border border-slate-200 bg-white p-4 shadow-sm">
                                            <div class="flex items-start justify-between gap-3">
                                                <div>
                                                    <p class="text-xs font-medium text-slate-500">Payments due</p>
                                                    <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">87</p>
                                                </div>
                                                <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">Focus</span>
                                            </div>
                                            <p class="mt-2 text-xs leading-5 text-slate-600">Accounts pending billing follow-up.</p>
                                        </div>
                                        <div class="rounded-[22px] border border-slate-200 bg-white p-4 shadow-sm">
                                            <div class="flex items-start justify-between gap-3">
                                                <div>
                                                    <p class="text-xs font-medium text-slate-500">Offline towers</p>
                                                    <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">1</p>
                                                </div>
                                                <span class="inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-[10px] font-semibold text-rose-700 ring-1 ring-inset ring-rose-200">Alert</span>
                                            </div>
                                            <p class="mt-2 text-xs leading-5 text-slate-600">One backhaul node lost telemetry.</p>
                                        </div>
                                        <div class="rounded-[22px] border border-slate-200 bg-white p-4 shadow-sm">
                                            <div class="flex items-start justify-between gap-3">
                                                <div>
                                                    <p class="text-xs font-medium text-slate-500">New installs</p>
                                                    <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">14</p>
                                                </div>
                                                <span class="inline-flex rounded-full bg-sky-50 px-2.5 py-1 text-[10px] font-semibold text-sky-700 ring-1 ring-inset ring-sky-200">Focus</span>
                                            </div>
                                            <p class="mt-2 text-xs leading-5 text-slate-600">Scheduled for this service week.</p>
                                        </div>
                                        <div class="rounded-[22px] border border-slate-200 bg-white p-4 shadow-sm">
                                            <div class="flex items-start justify-between gap-3">
                                                <div>
                                                    <p class="text-xs font-medium text-slate-500">Suspended</p>
                                                    <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">23</p>
                                                </div>
                                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">Review</span>
                                            </div>
                                            <p class="mt-2 text-xs leading-5 text-slate-600">Needs collections or support follow-up.</p>
                                        </div>
                                        <div class="rounded-[22px] border border-slate-200 bg-white p-4 shadow-sm">
                                            <div class="flex items-start justify-between gap-3">
                                                <div>
                                                    <p class="text-xs font-medium text-slate-500">Open tickets</p>
                                                    <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">9</p>
                                                </div>
                                                <span class="inline-flex rounded-full bg-violet-50 px-2.5 py-1 text-[10px] font-semibold text-violet-700 ring-1 ring-inset ring-violet-200">Queue</span>
                                            </div>
                                            <p class="mt-2 text-xs leading-5 text-slate-600">Support workload is steady.</p>
                                        </div>
                                    </section>

                                    <section class="grid grid-cols-[minmax(0,1.25fr)_minmax(220px,0.75fr)] gap-4">
                                        <div class="space-y-4">
                                            <div class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <h4 class="text-sm font-semibold text-slate-900">Service mix</h4>
                                                        <p class="text-xs text-slate-500">Subscription and connection breakdown.</p>
                                                    </div>
                                                    <span class="text-xs font-semibold text-sky-700">View subscriptions</span>
                                                </div>

                                                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                                                    <div class="rounded-2xl bg-slate-50 p-4">
                                                        <div class="flex items-center justify-between">
                                                            <p class="text-xs font-semibold text-slate-800">Subscription statuses</p>
                                                            <span class="text-[10px] font-medium text-slate-500">1,248 total</span>
                                                        </div>
                                                        <div class="mt-3 space-y-3">
                                                            <div class="space-y-1.5">
                                                                <div class="flex items-center justify-between text-[11px]">
                                                                    <span class="font-medium text-slate-700">Active</span>
                                                                    <span class="text-slate-500">82%</span>
                                                                </div>
                                                                <div class="h-2 rounded-full bg-slate-200">
                                                                    <div class="h-2 w-[82%] rounded-full bg-emerald-500"></div>
                                                                </div>
                                                            </div>
                                                            <div class="space-y-1.5">
                                                                <div class="flex items-center justify-between text-[11px]">
                                                                    <span class="font-medium text-slate-700">Pending</span>
                                                                    <span class="text-slate-500">11%</span>
                                                                </div>
                                                                <div class="h-2 rounded-full bg-slate-200">
                                                                    <div class="h-2 w-[11%] rounded-full bg-amber-500"></div>
                                                                </div>
                                                            </div>
                                                            <div class="space-y-1.5">
                                                                <div class="flex items-center justify-between text-[11px]">
                                                                    <span class="font-medium text-slate-700">Suspended</span>
                                                                    <span class="text-slate-500">7%</span>
                                                                </div>
                                                                <div class="h-2 rounded-full bg-slate-200">
                                                                    <div class="h-2 w-[7%] rounded-full bg-rose-500"></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="rounded-2xl bg-slate-50 p-4">
                                                        <div class="flex items-center justify-between">
                                                            <p class="text-xs font-semibold text-slate-800">Connection types</p>
                                                            <span class="text-[10px] font-medium text-slate-500">Provisioning footprint</span>
                                                        </div>
                                                        <div class="mt-3 space-y-3">
                                                            <div class="rounded-2xl border border-slate-200 bg-white p-3">
                                                                <div class="flex items-center justify-between">
                                                                    <span class="text-xs font-semibold text-slate-900">PPPoE</span>
                                                                    <span class="text-sm font-semibold text-slate-900">58%</span>
                                                                </div>
                                                                <div class="mt-2 h-2 rounded-full bg-slate-200">
                                                                    <div class="h-2 w-[58%] rounded-full bg-sky-500"></div>
                                                                </div>
                                                            </div>
                                                            <div class="rounded-2xl border border-slate-200 bg-white p-3">
                                                                <div class="flex items-center justify-between">
                                                                    <span class="text-xs font-semibold text-slate-900">Hotspot</span>
                                                                    <span class="text-sm font-semibold text-slate-900">27%</span>
                                                                </div>
                                                                <div class="mt-2 h-2 rounded-full bg-slate-200">
                                                                    <div class="h-2 w-[27%] rounded-full bg-violet-500"></div>
                                                                </div>
                                                            </div>
                                                            <div class="rounded-2xl border border-slate-200 bg-white p-3">
                                                                <div class="flex items-center justify-between">
                                                                    <span class="text-xs font-semibold text-slate-900">Static</span>
                                                                    <span class="text-sm font-semibold text-slate-900">15%</span>
                                                                </div>
                                                                <div class="mt-2 h-2 rounded-full bg-slate-200">
                                                                    <div class="h-2 w-[15%] rounded-full bg-emerald-500"></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="space-y-4">
                                            <div class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <h4 class="text-sm font-semibold text-slate-900">Router health</h4>
                                                        <p class="text-xs text-slate-500">Live device posture.</p>
                                                    </div>
                                                    <span class="text-xs font-semibold text-sky-700">View routers</span>
                                                </div>
                                                <div class="mt-4 space-y-3">
                                                    <div class="rounded-2xl border border-slate-200 p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div>
                                                                <p class="text-xs font-semibold text-slate-900">Tower-A Core</p>
                                                                <p class="mt-1 text-[10px] text-slate-500">172 active sessions</p>
                                                            </div>
                                                            <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">Online</span>
                                                        </div>
                                                    </div>
                                                    <div class="rounded-2xl border border-slate-200 p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div>
                                                                <p class="text-xs font-semibold text-slate-900">Hilltop Relay</p>
                                                                <p class="mt-1 text-[10px] text-slate-500">Telemetry stale for 4m</p>
                                                            </div>
                                                            <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">Warning</span>
                                                        </div>
                                                    </div>
                                                    <div class="rounded-2xl border border-slate-200 p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div>
                                                                <p class="text-xs font-semibold text-slate-900">Backbone POP</p>
                                                                <p class="mt-1 text-[10px] text-slate-500">Peak throughput 6.2 Gbps</p>
                                                            </div>
                                                            <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">Online</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <h4 class="text-sm font-semibold text-slate-900">Recent activity</h4>
                                                        <p class="text-xs text-slate-500">Operational timeline.</p>
                                                    </div>
                                                    <span class="text-xs font-semibold text-sky-700">Manage users</span>
                                                </div>
                                                <div class="mt-4 space-y-3">
                                                    <div class="flex gap-3">
                                                        <div class="mt-0.5 h-9 w-9 rounded-2xl bg-sky-50 ring-1 ring-inset ring-sky-200"></div>
                                                        <div>
                                                            <p class="text-xs font-semibold text-slate-900">New customer activated</p>
                                                            <p class="mt-1 text-[11px] leading-4 text-slate-600">Fiber 200 plan provisioned automatically.</p>
                                                        </div>
                                                    </div>
                                                    <div class="flex gap-3">
                                                        <div class="mt-0.5 h-9 w-9 rounded-2xl bg-emerald-50 ring-1 ring-inset ring-emerald-200"></div>
                                                        <div>
                                                            <p class="text-xs font-semibold text-slate-900">Payment settled</p>
                                                            <p class="mt-1 text-[11px] leading-4 text-slate-600">Monthly invoice marked paid via gateway.</p>
                                                        </div>
                                                    </div>
                                                    <div class="flex gap-3">
                                                        <div class="mt-0.5 h-9 w-9 rounded-2xl bg-rose-50 ring-1 ring-inset ring-rose-200"></div>
                                                        <div>
                                                            <p class="text-xs font-semibold text-slate-900">Router alert received</p>
                                                            <p class="mt-1 text-[11px] leading-4 text-slate-600">Backhaul packet loss crossed threshold.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </section>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mx-auto h-5 w-36 rounded-b-[999px] bg-slate-800 shadow-[0_16px_35px_rgba(15,23,42,0.25)]"></div>
                <div class="mx-auto h-10 w-24 rounded-b-3xl bg-gradient-to-b from-slate-700 via-slate-800 to-slate-950"></div>
                <div class="mx-auto h-4 w-48 rounded-full bg-slate-950/60 blur-sm"></div>
            </div>
        </div>
    </section>

    <!-- Problem Section -->
    <section class=" py-20">
        <div class="mx-auto px-4 sm:px-6 lg:px-8  max-w-7xl">
            <div class="max-w-3xl mx-auto">
                <h2 class="text-3xl font-bold text-gray-900 text-center mb-4">
                    Running an ISP Should Not Be This Hard
                </h2>
                <p class="text-lg text-gray-600 text-center mb-12">
                    Most ISPs rely on fragile scripts, self-hosted Radius servers, and manual MikroTik configuration.
                </p>

                <div class="grid md:grid-cols-2 gap-6">
                    <div class="bg-red-50 border border-red-100 rounded-2xl p-6">
                        <h3 class="font-semibold text-gray-900 mb-4">The Problems:</h3>
                        <ul class="space-y-3 text-gray-600">
                            <li class="flex items-start gap-2">
                                <span class="text-red-500 mt-1">✗</span>
                                <span>Network outages that go unnoticed</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-red-500 mt-1">✗</span>
                                <span>Authentication failures</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-red-500 mt-1">✗</span>
                                <span>Slow provisioning of new customers</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-red-500 mt-1">✗</span>
                                <span>Lack of visibility across routers and towers</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-red-500 mt-1">✗</span>
                                <span>Complex infrastructure that is hard to maintain</span>
                            </li>
                        </ul>
                    </div>

                    <div class="bg-green-50 border border-green-100 rounded-2xl p-6">
                        <h3 class="font-semibold text-gray-900 mb-4">The Solution:</h3>
                        <p class="text-gray-600 leading-relaxed">
                            SkyBase Cloud solves these problems by providing a centralized, cloud-based control plane for your ISP.
                        </p>
                        <ul class="space-y-3 text-gray-600 mt-4">
                            <li class="flex items-start gap-2">
                                <span class="text-green-500 mt-1">✓</span>
                                <span>Instant alerts when devices fail</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-green-500 mt-1">✓</span>
                                <span>Reliable Radius authentication</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-green-500 mt-1">✓</span>
                                <span>Provision customers in seconds</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-green-500 mt-1">✓</span>
                                <span>Full network visibility</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-green-500 mt-1">✓</span>
                                <span>Zero infrastructure management</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="bg-gray-50 py-20">
        <div class="mx-auto px-4 sm:px-6 lg:px-8  max-w-7xl">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">
                    Everything You Need to Operate Your ISP
                </h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    A complete platform built specifically for MikroTik-based networks
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="bg-white border border-gray-200 rounded-2xl p-6">
                    <div class="w-12 h-12 bg-blue-100 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Centralized MikroTik Management</h3>
                    <p class="text-gray-600 mb-4">Manage all your MikroTik routers from one dashboard.</p>
                    <ul class="space-y-2 text-lg text-gray-500">
                        <li>• Connect routers via MikroTik API</li>
                        <li>• Monitor router health and performance</li>
                        <li>• View traffic and uptime</li>
                        <li>• Manage hundreds of routers</li>
                    </ul>
                </div>

                <!-- Feature 2 -->
                <div class="bg-white border border-gray-200 rounded-2xl p-6">
                    <div class="w-12 h-12 bg-green-100 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Cloud Radius Authentication</h3>
                    <p class="text-gray-600 mb-4">Reliable PPPoE and Hotspot authentication without managing your own server.</p>
                    <ul class="space-y-2 text-lg text-gray-500">
                        <li>• Secure customer authentication</li>
                        <li>• Session tracking and accounting</li>
                        <li>• Profile-based speed limits</li>
                        <li>• High availability architecture</li>
                    </ul>
                </div>

                <!-- Feature 3 -->
                <div class="bg-white border border-gray-200 rounded-2xl p-6">
                    <div class="w-12 h-12 bg-purple-100 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Real-Time Monitoring</h3>
                    <p class="text-gray-600 mb-4">See exactly what is happening in your network.</p>
                    <ul class="space-y-2 text-lg text-gray-500">
                        <li>• Router online/offline status</li>
                        <li>• CPU, memory, and traffic monitoring</li>
                        <li>• Instant alerts when devices go offline</li>
                        <li>• Historical metrics and graphs</li>
                    </ul>
                </div>

                <!-- Feature 4 -->
                <div class="bg-white border border-gray-200 rounded-2xl p-6">
                    <div class="w-12 h-12 bg-orange-100 rounded-2xl flex items-center justify-center mb-4 ">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Automated Provisioning</h3>
                    <p class="text-gray-600 mb-4">Provision new customers in seconds.</p>
                    <ul class="space-y-2 text-lg text-gray-500">
                        <li>• Automatic Radius user creation</li>
                        <li>• Assign plans and profiles instantly</li>
                        <li>• Activate or suspend customers remotely</li>
                        <li>• Eliminate manual configuration errors</li>
                    </ul>
                </div>

                <!-- Feature 5 -->
                <div class="bg-white border border-gray-200 rounded-2xl p-6">
                    <div class="w-12 h-12 bg-indigo-100 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Multi-User Access Control</h3>
                    <p class="text-gray-600 mb-4">Allow your team to work securely.</p>
                    <ul class="space-y-2 text-lg text-gray-500">
                        <li>• Owner, Admin, Support, Read-Only roles</li>
                        <li>• Secure tenant isolation</li>
                        <li>• Full audit logging</li>
                        <li>• Granular permissions</li>
                    </ul>
                </div>

                <!-- Feature 6 -->
                <div class="bg-white border border-gray-200 rounded-2xl p-6">
                    <div class="w-12 h-12 bg-cyan-100 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">White-Label Ready</h3>
                    <p class="text-gray-600 mb-4">SkyBase Cloud adapts to your brand.</p>
                    <ul class="space-y-2 text-lg text-gray-500">
                        <li>• Custom logo</li>
                        <li>• Custom domain</li>
                        <li>• Branded interface for your ISP</li>
                        <li>• Custom CSS support</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section class=" py-20">
        <div class="mx-auto px-4 sm:px-6 lg:px-8  max-w-7xl">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Built for Reliability and Scale</h2>
                <p class="text-lg text-gray-600">Why ISPs choose SkyBase Cloud</p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="border-l-4 border-blue-600 bg-gray-50 rounded-r-2xl p-6">
                    <h3 class="font-semibold text-gray-900 mb-2">Reduce Operational Complexity</h3>
                    <p class="text-gray-600 text-lg">Replace multiple tools with one unified platform.</p>
                </div>

                <div class="border-l-4 border-blue-600 bg-gray-50 rounded-r-2xl p-6">
                    <h3 class="font-semibold text-gray-900 mb-2">Improve Network Visibility</h3>
                    <p class="text-gray-600 text-lg">Know immediately when devices fail or customers disconnect.</p>
                </div>

                <div class="border-l-4 border-blue-600 bg-gray-50 rounded-r-2xl p-6">
                    <h3 class="font-semibold text-gray-900 mb-2">Save Engineering Time</h3>
                    <p class="text-gray-600 text-lg">Automate repetitive tasks and eliminate manual provisioning.</p>
                </div>

                <div class="border-l-4 border-blue-600 bg-gray-50 rounded-r-2xl p-6">
                    <h3 class="font-semibold text-gray-900 mb-2">Scale Without Infrastructure Headaches</h3>
                    <p class="text-gray-600 text-lg">No need to manage servers, backups, or Radius infrastructure.</p>
                </div>

                <div class="border-l-4 border-blue-600 bg-gray-50 rounded-r-2xl p-6">
                    <h3 class="font-semibold text-gray-900 mb-2">Increase Network Reliability</h3>
                    <p class="text-gray-600 text-lg">Detect and resolve problems faster.</p>
                </div>

                <div class="border-l-4 border-blue-600 bg-gray-50 rounded-r-2xl p-6">
                    <h3 class="font-semibold text-gray-900 mb-2">Lower Total Cost of Ownership</h3>
                    <p class="text-gray-600 text-lg">Pay for what you use. No upfront infrastructure costs.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class=" bg-gray-50 py-20">
        <div class="mx-auto px-4 sm:px-6 lg:px-8  max-w-7xl">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Get Started in Minutes</h2>
                <p class="text-lg text-gray-600">Three simple steps to transform your ISP operations</p>
            </div>

            <div class=" mx-auto">
                <div class="flex flex-col md:flex-row gap-8">
                    <!-- Step 1 -->
                    <div class="flex-1">
                        <div class="bg-white border border-gray-200 rounded-2xl p-6 h-full">
                            <div class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold text-lg mb-4">1</div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Create Your Account</h3>
                            <p class="text-gray-600 text-lg">Sign up and create your ISP workspace.</p>
                        </div>
                    </div>

                    <!-- Step 2 -->
                    <div class="flex-1">
                        <div class="bg-white border border-gray-200 rounded-2xl p-6 h-full">
                            <div class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold text-lg mb-4">2</div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Connect Your Routers</h3>
                            <p class="text-gray-600 text-lg">Add MikroTik routers using API credentials.</p>
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div class="flex-1">
                        <div class="bg-white border border-gray-200 rounded-2xl p-6 h-full">
                            <div class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold text-lg mb-4">3</div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Start Managing</h3>
                            <p class="text-gray-600 text-lg">Provision customers, monitor devices, and control your network.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Target Audience Section -->
    <section class=" py-20">
        <div class="mx-auto px-4 sm:px-6 lg:px-8  max-w-7xl">
            <div class="mx-auto text-center">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Designed for WISPs, ISPs, and Network Operators</h2>
                <p class="text-lg text-gray-600 mb-8">SkyBase Cloud is ideal for:</p>

                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <div class="bg-gray-50 border border-gray-200 rounded-2xl p-4">
                        <p class="text-gray-900 font-medium text-lg">Wireless ISPs (WISPs)</p>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-2xl p-4">
                        <p class="text-gray-900 font-medium text-lg">Fiber ISPs</p>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-2xl p-4">
                        <p class="text-gray-900 font-medium text-lg">MikroTik Networks</p>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-2xl p-4">
                        <p class="text-gray-900 font-medium text-lg">Multi-Router Operators</p>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-2xl p-4">
                        <p class="text-gray-900 font-medium text-lg">Growing ISPs</p>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-2xl p-4">
                        <p class="text-gray-900 font-medium text-lg">MikroTik Hotspots</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Comparison Section -->
    <section class=" bg-gray-50 py-20">
        <div class="mx-auto px-4 sm:px-6 lg:px-8  max-w-7xl">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Why Choose SkyBase Cloud</h2>
                <p class="text-lg text-gray-600">Unlike traditional ISP management software</p>
            </div>

            <div class="mx-auto">
                <div class="grid md:grid-cols-5 gap-4 items-center mb-8">
                    <div class="md:col-span-1"></div>
                    <div class="md:col-span-2 bg-blue-600 text-white rounded-2xl p-4 text-center font-semibold">
                        SkyBase Cloud
                    </div>
                    <div class="md:col-span-2 bg-gray-200 rounded-2xl p-4 text-center font-semibold text-gray-700">
                        Traditional Solutions
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="grid md:grid-cols-5 gap-4 items-center">
                        <div class="font-medium text-gray-900 text-lg">Deployment</div>
                        <div class="md:col-span-2 text-center text-green-600 font-medium">Cloud-native</div>
                        <div class="md:col-span-2 text-center text-gray-500">Self-hosted</div>
                    </div>

                    <div class="grid md:grid-cols-5 gap-4 items-center">
                        <div class="font-medium text-gray-900 text-lg">Setup Time</div>
                        <div class="md:col-span-2 text-center text-green-600 font-medium">Minutes</div>
                        <div class="md:col-span-2 text-center text-gray-500">Days/Weeks</div>
                    </div>

                    <div class="grid md:grid-cols-5 gap-4 items-center">
                        <div class="font-medium text-gray-900 text-lg">Scalability</div>
                        <div class="md:col-span-2 text-center text-green-600 font-medium">Unlimited</div>
                        <div class="md:col-span-2 text-center text-gray-500">Limited by hardware</div>
                    </div>

                    <div class="grid md:grid-cols-5 gap-4 items-center">
                        <div class="font-medium text-gray-900 text-lg">MikroTik Focus</div>
                        <div class="md:col-span-2 text-center text-green-600 font-medium">Built for MikroTik</div>
                        <div class="md:col-span-2 text-center text-gray-500">Generic</div>
                    </div>

                    <div class="grid md:grid-cols-5 gap-4 items-center">
                        <div class="font-medium text-gray-900 text-lg">Maintenance</div>
                        <div class="md:col-span-2 text-center text-green-600 font-medium">We handle it</div>
                        <div class="md:col-span-2 text-center text-gray-500">You manage it</div>
                    </div>
                </div>

                <div class="mt-12 p-6 bg-blue-50 border border-blue-200 rounded-2xl">
                    <p class="text-center text-gray-900 font-medium">No server installation required. No complex configuration. Just connect your routers and start managing.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Preview Section -->
    <section id="pricing" class="py-20">
        <div class="mx-auto px-4 sm:px-6 lg:px-8  max-w-7xl">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Simple, Transparent Pricing</h2>
                <p class="text-lg text-gray-600">Flexible pricing based on the size of your network</p>
                <p class="text-gray-500 mt-2">Start small and scale as you grow.</p>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('pricing') }}" class="inline-flex items-center justify-center px-6 py-3 text-lg font-medium text-white bg-blue-600 rounded-2xl hover:bg-blue-700 transition-colors">View Pricing</a>
                <a href="{{ route('auth.register') }}" class="inline-flex items-center justify-center px-6 py-3 text-lg font-medium text-gray-700 bg-white border border-gray-300 rounded-2xl hover:bg-gray-50 transition-colors">Start Free Trial</a>
            </div>
        </div>
    </section>

    <!-- Trust Section -->
    <section class=" bg-gray-50 py-20">
        <div class="mx-auto px-4 sm:px-6 lg:px-8  max-w-7xl">
            <div class="mx-auto text-center">
                <h2 class="text-3xl font-bold text-gray-900 mb-6">Built for Real-World ISP Operations</h2>
                <p class="text-lg text-gray-600 leading-relaxed">
                    SkyBase Cloud is built by network engineers with real experience running ISP infrastructure. The platform is designed for reliability, security, and scalability.
                </p>
            </div>
        </div>
    </section>

    <!-- Final CTA Section -->
    <section class=" bg-blue-600 py-20">
        <div class="mx-auto px-4 sm:px-6 lg:px-8  max-w-7xl">
            <div class="mx-auto text-center">
                <h2 class="text-3xl font-bold text-white mb-4">Start Managing Your ISP the Modern Way</h2>
                <p class="text-xl text-blue-100 mb-8">Join ISPs using SkyBase Cloud to simplify operations and improve reliability.</p>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="{{ route('auth.register') }}" class="inline-flex items-center justify-center px-10 py-4 text-base font-semibold text-white bg-blue-600 rounded-2xl hover:bg-blue-700 transition-colors">Start Free Trial</a>
                    <a href="#demo" class="inline-flex items-center justify-center px-10 py-4 text-base font-semibold text-white border-2 border-white rounded-2xl hover:bg-white hover:text-blue-600 transition-colors">Book a Demo</a>
                </div>
            </div>
        </div>
    </section>

@endsection
