@extends('layouts.layout')

@section('title', 'SkyBase Features | ISP Management Platform')
@section('meta_description', 'Discover the powerful features of SkyBase, the modern ISP management platform designed for WISPs and fiber providers. Automate billing, manage subscribers, integrate with MikroTik, and scale your network operations.')
@section('meta_keywords', 'ISP management features, WISP software features, MikroTik billing, subscriber management, automated billing, network provisioning')
@section('og_title', 'SkyBase Features | ISP Management Platform')
@section('og_description', 'Discover the powerful features of SkyBase, the modern ISP management platform designed for WISPs and fiber providers.')
@section('og_url', url('/features'))

@section('content')
<!-- Hero Section -->
    <section class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 border-b border-gray-200 py-20 sm:py-24">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto text-center">
                <h1 class="text-4xl sm:text-5xl font-bold text-gray-900 leading-tight mb-6">
                    Everything You Need to Run Your ISP
                </h1>
                <p class="text-xl text-gray-600 mb-8 leading-relaxed">
                    SkyBase combines billing, CRM, network automation, and customer management into a single powerful platform designed for modern ISPs.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="{{ route('auth.register') }}" class="inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-white bg-blue-600 rounded-2xl hover:bg-blue-700 transition-colors">
                        Start Free Trial
                    </a>
                    <a href="{{ url('/pricing') }}" class="inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-gray-700 bg-white border border-gray-300 rounded-2xl hover:bg-gray-50 transition-colors">
                        View Pricing
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Intro Section -->
    <section class="py-16 bg-white">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto text-center">
                <h2 class="text-3xl font-bold text-gray-900 mb-6">A Complete ISP Management Platform</h2>
                <p class="text-xl text-gray-600 leading-relaxed">
                    Running an ISP requires many tools: billing systems, network provisioning, monitoring, support, and customer management. SkyBase brings all of these capabilities together in one modern platform so operators can manage their entire network from a single interface.
                </p>
            </div>
        </div>
    </section>

    <!-- Feature 1: Subscriber Management -->
    <section class="py-16 bg-gray-50">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="grid md:grid-cols-2 gap-12 items-center">
                    <div>
                        <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center mb-6">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-3xl font-bold text-gray-900 mb-4">Subscriber Management</h3>
                        <p class="text-lg text-gray-600 mb-6">
                            Manage all your subscribers, services, and accounts from a centralized dashboard. SkyBase gives you complete visibility into customer information, service plans, usage, and billing history.
                        </p>
                        <ul class="space-y-3">
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-blue-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Complete customer profiles</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-blue-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Service and plan management</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-blue-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Account status automation</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-blue-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Subscriber activity history</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-blue-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Search and filtering tools</span>
                            </li>
                        </ul>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5 sm:p-6">
                        <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4 sm:p-5">
                            <div class="flex flex-col gap-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-blue-600">Customers</p>
                                        <h4 class="mt-1 text-lg font-semibold text-slate-900">Subscriber workspace</h4>
                                    </div>
                                    <div class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        New Customer
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-2xl border border-blue-100 bg-white p-4">
                                        <p class="text-xs font-medium text-slate-500">Total Customers</p>
                                        <p class="mt-2 text-2xl font-bold text-slate-900">1,248</p>
                                    </div>
                                    <div class="rounded-2xl border border-emerald-100 bg-white p-4">
                                        <p class="text-xs font-medium text-slate-500">Active</p>
                                        <p class="mt-2 text-2xl font-bold text-emerald-600">1,179</p>
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-slate-200 bg-white p-3">
                                    <div class="grid gap-3 sm:grid-cols-[minmax(0,1.5fr)_repeat(3,minmax(0,1fr))]">
                                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500">Search by name, email, phone...</div>
                                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500">Status</div>
                                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500">Plan</div>
                                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500">Router</div>
                                    </div>
                                </div>

                                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                    <div class="grid grid-cols-[minmax(0,1.2fr)_0.9fr_0.9fr] border-b border-slate-200 bg-slate-50 px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        <span>Customer</span>
                                        <span>Plan</span>
                                        <span>Status</span>
                                    </div>
                                    <div class="divide-y divide-slate-200">
                                        <div class="grid grid-cols-[minmax(0,1.2fr)_0.9fr_0.9fr] items-center px-4 py-3 text-sm">
                                            <div>
                                                <p class="font-semibold text-slate-900">Amina Telecom</p>
                                                <p class="text-xs text-slate-500">FTTH-200 / Site A</p>
                                            </div>
                                            <p class="text-slate-600">200 Mbps</p>
                                            <span class="inline-flex w-fit rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">Active</span>
                                        </div>
                                        <div class="grid grid-cols-[minmax(0,1.2fr)_0.9fr_0.9fr] items-center px-4 py-3 text-sm">
                                            <div>
                                                <p class="font-semibold text-slate-900">North Ridge Ltd</p>
                                                <p class="text-xs text-slate-500">PPPoE / Tower East</p>
                                            </div>
                                            <p class="text-slate-600">Business Pro</p>
                                            <span class="inline-flex w-fit rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">Pending</span>
                                        </div>
                                        <div class="grid grid-cols-[minmax(0,1.2fr)_0.9fr_0.9fr] items-center px-4 py-3 text-sm">
                                            <div>
                                                <p class="font-semibold text-slate-900">Hillside Homes</p>
                                                <p class="text-xs text-slate-500">MikroTik queue / POP-3</p>
                                            </div>
                                            <p class="text-slate-600">Home 50</p>
                                            <span class="inline-flex w-fit rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-200">Overdue</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Feature 2: Automated Billing -->
    <section class="py-16 bg-white">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="grid md:grid-cols-2 gap-12 items-center">
                    <div class="order-2 md:order-1 bg-white border border-gray-200 rounded-2xl shadow-sm p-5 sm:p-6">
                        <div class="rounded-[1.5rem] border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-4 sm:p-5">
                            <div class="flex flex-col gap-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-600">Billing Overview</p>
                                        <h4 class="mt-1 text-lg font-semibold text-slate-900">Revenue and invoice monitoring</h4>
                                    </div>
                                    <div class="rounded-xl bg-white px-3 py-2 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">This Month</div>
                                </div>

                                <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
                                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                        <p class="text-xs text-slate-500">Revenue</p>
                                        <p class="mt-2 text-xl font-bold text-slate-900">,240</p>
                                        <p class="mt-1 text-xs text-emerald-600">+12.5%</p>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                        <p class="text-xs text-slate-500">Outstanding</p>
                                        <p class="mt-2 text-xl font-bold text-slate-900">,930</p>
                                        <p class="mt-1 text-xs text-amber-600">38 pending</p>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                        <p class="text-xs text-slate-500">Overdue</p>
                                        <p class="mt-2 text-xl font-bold text-rose-600">,180</p>
                                        <p class="mt-1 text-xs text-rose-600">9 invoices</p>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                        <p class="text-xs text-slate-500">Paid</p>
                                        <p class="mt-2 text-xl font-bold text-slate-900">214</p>
                                        <p class="mt-1 text-xs text-slate-500">this month</p>
                                    </div>
                                </div>

                                <div class="grid gap-3 lg:grid-cols-[minmax(0,1.2fr)_0.8fr]">
                                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                        <div class="flex items-center justify-between">
                                            <p class="text-sm font-semibold text-slate-900">Collections trend</p>
                                            <p class="text-xs text-slate-500">Jan - Jun</p>
                                        </div>
                                        <div class="mt-4 flex h-32 items-end justify-between gap-2">
                                            <div class="flex flex-1 flex-col items-center gap-2"><div class="h-14 w-full rounded-t-xl bg-emerald-200"></div><span class="text-[11px] text-slate-500">Jan</span></div>
                                            <div class="flex flex-1 flex-col items-center gap-2"><div class="h-16 w-full rounded-t-xl bg-emerald-300"></div><span class="text-[11px] text-slate-500">Feb</span></div>
                                            <div class="flex flex-1 flex-col items-center gap-2"><div class="h-20 w-full rounded-t-xl bg-emerald-400"></div><span class="text-[11px] text-slate-500">Mar</span></div>
                                            <div class="flex flex-1 flex-col items-center gap-2"><div class="h-24 w-full rounded-t-xl bg-emerald-500"></div><span class="text-[11px] text-slate-500">Apr</span></div>
                                            <div class="flex flex-1 flex-col items-center gap-2"><div class="h-28 w-full rounded-t-xl bg-emerald-500"></div><span class="text-[11px] text-slate-500">May</span></div>
                                            <div class="flex flex-1 flex-col items-center gap-2"><div class="h-24 w-full rounded-t-xl bg-slate-900"></div><span class="text-[11px] text-slate-500">Jun</span></div>
                                        </div>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                        <p class="text-sm font-semibold text-slate-900">Invoice status</p>
                                        <div class="mt-4 space-y-3">
                                            <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2"><span class="text-sm text-slate-600">Paid</span><span class="font-semibold text-emerald-600">214</span></div>
                                            <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2"><span class="text-sm text-slate-600">Pending</span><span class="font-semibold text-amber-600">38</span></div>
                                            <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2"><span class="text-sm text-slate-600">Overdue</span><span class="font-semibold text-rose-600">9</span></div>
                                            <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2"><span class="text-sm text-slate-600">Autopay success</span><span class="font-semibold text-slate-900">91%</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="order-1 md:order-2">
                        <div class="w-16 h-16 bg-green-100 rounded-2xl flex items-center justify-center mb-6">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-3xl font-bold text-gray-900 mb-4">Automated Billing</h3>
                        <p class="text-lg text-gray-600 mb-6">
                            Automate your entire billing workflow. SkyBase generates invoices, processes payments, and handles service suspension or activation automatically based on billing status.
                        </p>
                        <ul class="space-y-3">
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-green-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Automated invoice generation</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-green-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Flexible billing cycles</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-green-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Payment tracking</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-green-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Service suspension automation</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-green-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Usage-based billing support</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Feature 3: MikroTik Integration -->
    <section class="py-16 bg-gray-50">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="grid md:grid-cols-2 gap-12 items-center">
                    <div>
                        <div class="w-16 h-16 bg-purple-100 rounded-2xl flex items-center justify-center mb-6">
                            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                            </svg>
                        </div>
                        <h3 class="text-3xl font-bold text-gray-900 mb-4">MikroTik Integration</h3>
                        <p class="text-lg text-gray-600 mb-6">
                            SkyBase integrates directly with MikroTik infrastructure to simplify provisioning and subscriber authentication for WISPs.
                        </p>
                        <ul class="space-y-3">
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-purple-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">PPPoE authentication</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-purple-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Hotspot authentication</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-purple-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">RADIUS integration</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-purple-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Automated service activation</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-purple-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Device synchronization</span>
                            </li>
                        </ul>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5 sm:p-6">
                        <div class="rounded-[1.5rem] border border-violet-100 bg-gradient-to-br from-violet-50 to-white p-4 sm:p-5">
                            <div class="space-y-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-violet-600">Router Fleet</p>
                                        <h4 class="mt-1 text-lg font-semibold text-slate-900">MikroTik command center</h4>
                                    </div>
                                    <span class="rounded-xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white">18 connected</span>
                                </div>

                                <div class="grid gap-3 lg:grid-cols-[minmax(0,1.1fr)_0.9fr]">
                                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                        <div class="flex items-center justify-between">
                                            <p class="text-sm font-semibold text-slate-900">Router sync status</p>
                                            <p class="text-xs text-slate-500">API + RADIUS</p>
                                        </div>
                                        <div class="mt-4 space-y-3">
                                            <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-3">
                                                <div>
                                                    <p class="text-sm font-semibold text-slate-900">Tower-A Core</p>
                                                    <p class="text-xs text-slate-500">PPPoE / Queue sync</p>
                                                </div>
                                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">Online</span>
                                            </div>
                                            <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-3">
                                                <div>
                                                    <p class="text-sm font-semibold text-slate-900">POP-West Edge</p>
                                                    <p class="text-xs text-slate-500">Hotspot auth / Profiles</p>
                                                </div>
                                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">Synced</span>
                                            </div>
                                            <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-3">
                                                <div>
                                                    <p class="text-sm font-semibold text-slate-900">Backhaul-02</p>
                                                    <p class="text-xs text-slate-500">Telemetry delayed</p>
                                                </div>
                                                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">Retrying</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="space-y-3">
                                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                            <p class="text-sm font-semibold text-slate-900">Authentication mix</p>
                                            <div class="mt-4 space-y-3">
                                                <div>
                                                    <div class="mb-1 flex items-center justify-between text-xs"><span class="text-slate-600">PPPoE</span><span class="font-semibold text-slate-900">58%</span></div>
                                                    <div class="h-2 rounded-full bg-slate-200"><div class="h-2 w-[58%] rounded-full bg-violet-500"></div></div>
                                                </div>
                                                <div>
                                                    <div class="mb-1 flex items-center justify-between text-xs"><span class="text-slate-600">Hotspot</span><span class="font-semibold text-slate-900">27%</span></div>
                                                    <div class="h-2 rounded-full bg-slate-200"><div class="h-2 w-[27%] rounded-full bg-fuchsia-400"></div></div>
                                                </div>
                                                <div>
                                                    <div class="mb-1 flex items-center justify-between text-xs"><span class="text-slate-600">Static</span><span class="font-semibold text-slate-900">15%</span></div>
                                                    <div class="h-2 rounded-full bg-slate-200"><div class="h-2 w-[15%] rounded-full bg-slate-900"></div></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="rounded-2xl border border-slate-200 bg-slate-900 p-4 text-white">
                                            <p class="text-sm font-semibold">Live session sync</p>
                                            <p class="mt-2 text-3xl font-bold">1,178</p>
                                            <p class="mt-1 text-xs text-slate-300">Active sessions mirrored from MikroTik and RADIUS.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Feature 4: Network Provisioning -->
    <section class="py-16 bg-white">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="grid md:grid-cols-2 gap-12 items-center">
                    <div class="order-2 md:order-1 bg-white border border-gray-200 rounded-2xl shadow-sm p-5 sm:p-6">
                        <div class="rounded-[1.5rem] border border-orange-100 bg-gradient-to-br from-orange-50 to-white p-4 sm:p-5">
                            <div class="space-y-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-orange-600">Provisioning Queue</p>
                                        <h4 class="mt-1 text-lg font-semibold text-slate-900">Automated service rollout</h4>
                                    </div>
                                    <span class="rounded-xl bg-white px-3 py-2 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">14 pending installs</span>
                                </div>

                                <div class="grid gap-3 sm:grid-cols-3">
                                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                        <p class="text-xs text-slate-500">Queued</p>
                                        <p class="mt-2 text-2xl font-bold text-slate-900">14</p>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                        <p class="text-xs text-slate-500">Today</p>
                                        <p class="mt-2 text-2xl font-bold text-orange-600">9</p>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                        <p class="text-xs text-slate-500">Avg time</p>
                                        <p class="mt-2 text-2xl font-bold text-slate-900">46m</p>
                                    </div>
                                </div>

                                <div class="grid gap-3 lg:grid-cols-[0.95fr_minmax(0,1.05fr)]">
                                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                        <p class="text-sm font-semibold text-slate-900">Workflow</p>
                                        <div class="mt-4 flex flex-col gap-3">
                                            <div class="rounded-xl bg-slate-50 px-3 py-3 text-sm text-slate-700">1. Assign plan and bandwidth profile</div>
                                            <div class="rounded-xl bg-slate-50 px-3 py-3 text-sm text-slate-700">2. Push PPPoE credentials to router</div>
                                            <div class="rounded-xl bg-slate-50 px-3 py-3 text-sm text-slate-700">3. Reserve IP from matching pool</div>
                                            <div class="rounded-xl bg-slate-900 px-3 py-3 text-sm font-semibold text-white">4. Activate service and notify customer</div>
                                        </div>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                        <p class="text-sm font-semibold text-slate-900">Recent jobs</p>
                                        <div class="mt-4 space-y-3">
                                            <div class="flex items-start justify-between rounded-xl bg-slate-50 px-3 py-3">
                                                <div>
                                                    <p class="text-sm font-semibold text-slate-900">Fiber-200 install / Site A</p>
                                                    <p class="text-xs text-slate-500">Queue + IP lease prepared</p>
                                                </div>
                                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">Ready</span>
                                            </div>
                                            <div class="flex items-start justify-between rounded-xl bg-slate-50 px-3 py-3">
                                                <div>
                                                    <p class="text-sm font-semibold text-slate-900">Business-Pro migration</p>
                                                    <p class="text-xs text-slate-500">Router profile update running</p>
                                                </div>
                                                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">In progress</span>
                                            </div>
                                            <div class="flex items-start justify-between rounded-xl bg-slate-50 px-3 py-3">
                                                <div>
                                                    <p class="text-sm font-semibold text-slate-900">Tower-West restore</p>
                                                    <p class="text-xs text-slate-500">Session policy resync complete</p>
                                                </div>
                                                <span class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 ring-1 ring-inset ring-sky-200">Completed</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="order-1 md:order-2">
                        <div class="w-16 h-16 bg-orange-100 rounded-2xl flex items-center justify-center mb-6">
                            <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <h3 class="text-3xl font-bold text-gray-900 mb-4">Network Provisioning</h3>
                        <p class="text-lg text-gray-600 mb-6">
                            Automate service provisioning and configuration across your network devices. SkyBase ensures new subscribers can be activated quickly without manual configuration.
                        </p>
                        <ul class="space-y-3">
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-orange-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Automated service activation</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-orange-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Network device provisioning</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-orange-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Subscriber bandwidth control</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-orange-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Service plan assignment</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-orange-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Device automation workflows</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Feature 5: Customer Portal -->
    <section class="py-16 bg-gray-50">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="grid md:grid-cols-2 gap-12 items-center">
                    <div>
                        <div class="w-16 h-16 bg-cyan-100 rounded-2xl flex items-center justify-center mb-6">
                            <svg class="w-8 h-8 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                        </div>
                        <h3 class="text-3xl font-bold text-gray-900 mb-4">Customer Self-Service Portal</h3>
                        <p class="text-lg text-gray-600 mb-6">
                            Give your subscribers a self-service portal where they can manage their accounts, pay invoices, and contact support.
                        </p>
                        <ul class="space-y-3">
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-cyan-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Invoice and payment access</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-cyan-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Service status monitoring</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-cyan-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Support ticket creation</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-cyan-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Account profile management</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-cyan-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Usage visibility</span>
                            </li>
                        </ul>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5 sm:p-6">
                        <div class="mx-auto max-w-sm rounded-[2rem] border-8 border-slate-900 bg-slate-900 p-2 shadow-[0_24px_60px_rgba(15,23,42,0.18)]">
                            <div class="overflow-hidden rounded-[1.5rem] bg-gradient-to-b from-cyan-50 via-white to-slate-50">
                                <div class="flex justify-center py-2">
                                    <div class="h-1.5 w-20 rounded-full bg-slate-900/15"></div>
                                </div>
                                <div class="space-y-4 p-4">
                                    <div class="rounded-2xl bg-cyan-600 p-4 text-white">
                                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-100">My Account</p>
                                        <h4 class="mt-2 text-xl font-semibold">Amina Telecom</h4>
                                        <p class="mt-1 text-sm text-cyan-100">Plan: Fiber 200 • Service active</p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                            <p class="text-xs text-slate-500">Current bill</p>
                                            <p class="mt-2 text-xl font-bold text-slate-900">.00</p>
                                            <p class="mt-1 text-xs text-emerald-600">Auto-pay enabled</p>
                                        </div>
                                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                            <p class="text-xs text-slate-500">Usage</p>
                                            <p class="mt-2 text-xl font-bold text-slate-900">428 GB</p>
                                            <p class="mt-1 text-xs text-slate-500">of 1 TB fair use</p>
                                        </div>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                        <div class="flex items-center justify-between">
                                            <p class="text-sm font-semibold text-slate-900">Quick actions</p>
                                            <span class="text-xs text-slate-500">24/7</span>
                                        </div>
                                        <div class="mt-3 grid grid-cols-2 gap-3">
                                            <div class="rounded-xl bg-slate-50 px-3 py-3 text-center text-sm font-medium text-slate-700">Pay Invoice</div>
                                            <div class="rounded-xl bg-slate-50 px-3 py-3 text-center text-sm font-medium text-slate-700">Open Ticket</div>
                                            <div class="rounded-xl bg-slate-50 px-3 py-3 text-center text-sm font-medium text-slate-700">View Usage</div>
                                            <div class="rounded-xl bg-slate-50 px-3 py-3 text-center text-sm font-medium text-slate-700">Update Profile</div>
                                        </div>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                        <p class="text-sm font-semibold text-slate-900">Latest invoice</p>
                                        <div class="mt-3 flex items-center justify-between rounded-xl bg-slate-50 px-3 py-3">
                                            <div>
                                                <p class="text-sm font-medium text-slate-900">INV-2026-0412</p>
                                                <p class="text-xs text-slate-500">Due on July 12</p>
                                            </div>
                                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">Paid</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Feature 6: Support Ticketing -->
    <section class="py-16 bg-white">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="grid md:grid-cols-2 gap-12 items-center">
                    <div class="order-2 md:order-1 bg-white border border-gray-200 rounded-2xl shadow-sm p-5 sm:p-6">
                        <div class="rounded-[1.5rem] border border-rose-100 bg-gradient-to-br from-rose-50 to-white p-4 sm:p-5">
                            <div class="space-y-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-rose-600">Support Desk</p>
                                        <h4 class="mt-1 text-lg font-semibold text-slate-900">Ticket queue and response timeline</h4>
                                    </div>
                                    <span class="rounded-xl bg-white px-3 py-2 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">9 open tickets</span>
                                </div>

                                <div class="grid gap-3 lg:grid-cols-[0.95fr_minmax(0,1.05fr)]">
                                    <div class="space-y-3">
                                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                            <div class="flex items-center justify-between">
                                                <p class="text-sm font-semibold text-slate-900">Open queue</p>
                                                <p class="text-xs text-slate-500">SLA monitored</p>
                                            </div>
                                            <div class="mt-4 space-y-3">
                                                <div class="rounded-xl bg-slate-50 px-3 py-3">
                                                    <div class="flex items-center justify-between gap-3">
                                                        <p class="text-sm font-semibold text-slate-900">No internet / Tower East</p>
                                                        <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-200">High</span>
                                                    </div>
                                                    <p class="mt-1 text-xs text-slate-500">Assigned to NOC • 6m ago</p>
                                                </div>
                                                <div class="rounded-xl bg-slate-50 px-3 py-3">
                                                    <div class="flex items-center justify-between gap-3">
                                                        <p class="text-sm font-semibold text-slate-900">Invoice clarification</p>
                                                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">Medium</span>
                                                    </div>
                                                    <p class="mt-1 text-xs text-slate-500">Assigned to Billing • 19m ago</p>
                                                </div>
                                                <div class="rounded-xl bg-slate-50 px-3 py-3">
                                                    <div class="flex items-center justify-between gap-3">
                                                        <p class="text-sm font-semibold text-slate-900">Router relocation request</p>
                                                        <span class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 ring-1 ring-inset ring-sky-200">Scheduled</span>
                                                    </div>
                                                    <p class="mt-1 text-xs text-slate-500">Field team dispatch tomorrow</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                        <div class="flex items-center justify-between">
                                            <p class="text-sm font-semibold text-slate-900">Conversation</p>
                                            <p class="text-xs text-slate-500">Ticket #4821</p>
                                        </div>
                                        <div class="mt-4 space-y-3">
                                            <div class="max-w-[85%] rounded-2xl rounded-tl-md bg-slate-100 px-4 py-3 text-sm text-slate-700">
                                                Customer reports intermittent drops since 8:10 AM.
                                            </div>
                                            <div class="ml-auto max-w-[85%] rounded-2xl rounded-tr-md bg-rose-600 px-4 py-3 text-sm text-white">
                                                We traced packet loss to the tower sector and escalated it to the NOC team.
                                            </div>
                                            <div class="max-w-[85%] rounded-2xl rounded-tl-md bg-slate-100 px-4 py-3 text-sm text-slate-700">
                                                Great, please let me know once service stabilizes.
                                            </div>
                                        </div>
                                        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-500">Write a reply...</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="order-1 md:order-2">
                        <div class="w-16 h-16 bg-red-100 rounded-2xl flex items-center justify-center mb-6">
                            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-3xl font-bold text-gray-900 mb-4">Support Ticketing System</h3>
                        <p class="text-lg text-gray-600 mb-6">
                            Provide better customer service with a built-in support ticketing system. Track issues, assign tickets to staff, and maintain communication with customers.
                        </p>
                        <ul class="space-y-3">
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-red-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Ticket creation and tracking</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-red-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Staff assignment</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-red-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Customer communication threads</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-red-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Status updates</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-red-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Service issue tracking</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Feature 7: Automation & Workflows -->
    <section class="py-16 bg-gray-50">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="grid md:grid-cols-2 gap-12 items-center">
                    <div>
                        <div class="w-16 h-16 bg-indigo-100 rounded-2xl flex items-center justify-center mb-6">
                            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-3xl font-bold text-gray-900 mb-4">Automation & Workflows</h3>
                        <p class="text-lg text-gray-600 mb-6">
                            Reduce manual work with automation. SkyBase automatically handles account activation, suspension, billing events, and service changes.
                        </p>
                        <ul class="space-y-3">
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-indigo-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Billing-triggered automation</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-indigo-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Service activation workflows</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-indigo-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Account suspension rules</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-indigo-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Scheduled operations</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-indigo-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Event-based triggers</span>
                            </li>
                        </ul>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5 sm:p-6">
                        <div class="rounded-[1.5rem] border border-indigo-100 bg-gradient-to-br from-indigo-50 to-white p-4 sm:p-5">
                            <div class="space-y-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-indigo-600">Workflow Engine</p>
                                        <h4 class="mt-1 text-lg font-semibold text-slate-900">Rules, triggers, and run history</h4>
                                    </div>
                                    <span class="rounded-xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white">27 active rules</span>
                                </div>

                                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                    <div class="grid gap-3 sm:grid-cols-[0.9fr_0.2fr_0.9fr_0.2fr_0.9fr] sm:items-center">
                                        <div class="rounded-2xl bg-slate-50 p-4">
                                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Trigger</p>
                                            <p class="mt-2 text-sm font-semibold text-slate-900">Invoice overdue 7 days</p>
                                        </div>
                                        <div class="hidden text-center text-slate-300 sm:block">→</div>
                                        <div class="rounded-2xl bg-slate-50 p-4">
                                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Action</p>
                                            <p class="mt-2 text-sm font-semibold text-slate-900">Throttle service profile</p>
                                        </div>
                                        <div class="hidden text-center text-slate-300 sm:block">→</div>
                                        <div class="rounded-2xl bg-indigo-600 p-4 text-white">
                                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-100">Outcome</p>
                                            <p class="mt-2 text-sm font-semibold">Send SMS and email notice</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_0.85fr]">
                                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                        <p class="text-sm font-semibold text-slate-900">Recent workflow runs</p>
                                        <div class="mt-4 space-y-3">
                                            <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-3">
                                                <div>
                                                    <p class="text-sm font-semibold text-slate-900">Auto-suspend delinquent accounts</p>
                                                    <p class="text-xs text-slate-500">Ran 5 minutes ago</p>
                                                </div>
                                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">Success</span>
                                            </div>
                                            <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-3">
                                                <div>
                                                    <p class="text-sm font-semibold text-slate-900">Create PPPoE secret on activation</p>
                                                    <p class="text-xs text-slate-500">Ran 11 minutes ago</p>
                                                </div>
                                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">Success</span>
                                            </div>
                                            <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-3">
                                                <div>
                                                    <p class="text-sm font-semibold text-slate-900">Reassign exhausted IP pools</p>
                                                    <p class="text-xs text-slate-500">Ran 28 minutes ago</p>
                                                </div>
                                                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">Review</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                        <p class="text-sm font-semibold text-slate-900">Event volume</p>
                                        <div class="mt-4 space-y-3">
                                            <div class="flex items-center justify-between text-sm"><span class="text-slate-600">Billing rules</span><span class="font-semibold text-slate-900">312</span></div>
                                            <div class="flex items-center justify-between text-sm"><span class="text-slate-600">Provisioning</span><span class="font-semibold text-slate-900">187</span></div>
                                            <div class="flex items-center justify-between text-sm"><span class="text-slate-600">Notifications</span><span class="font-semibold text-slate-900">540</span></div>
                                            <div class="flex items-center justify-between text-sm"><span class="text-slate-600">Escalations</span><span class="font-semibold text-slate-900">14</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Feature 8: Reports & Analytics -->
    <section class="py-16 bg-white">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="grid md:grid-cols-2 gap-12 items-center">
                    <div class="order-2 md:order-1 bg-white border border-gray-200 rounded-2xl shadow-sm p-5 sm:p-6">
                        <div class="rounded-[1.5rem] border border-teal-100 bg-gradient-to-br from-teal-50 to-white p-4 sm:p-5">
                            <div class="space-y-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-teal-600">Reports</p>
                                        <h4 class="mt-1 text-lg font-semibold text-slate-900">Financial and usage analytics</h4>
                                    </div>
                                    <span class="rounded-xl bg-white px-3 py-2 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">Export CSV</span>
                                </div>

                                <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
                                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                        <p class="text-xs text-slate-500">Revenue</p>
                                        <p class="mt-2 text-xl font-bold text-slate-900">.2k</p>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                        <p class="text-xs text-slate-500">ARPU</p>
                                        <p class="mt-2 text-xl font-bold text-slate-900">.67</p>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                        <p class="text-xs text-slate-500">Total usage</p>
                                        <p class="mt-2 text-xl font-bold text-slate-900">68.4 TB</p>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                        <p class="text-xs text-slate-500">Active users</p>
                                        <p class="mt-2 text-xl font-bold text-slate-900">1,179</p>
                                    </div>
                                </div>

                                <div class="grid gap-3 lg:grid-cols-[minmax(0,1.1fr)_0.9fr]">
                                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                        <div class="flex items-center justify-between">
                                            <p class="text-sm font-semibold text-slate-900">Monthly revenue</p>
                                            <p class="text-xs text-slate-500">Collection rate</p>
                                        </div>
                                        <div class="mt-4 space-y-3">
                                            <div class="flex items-center gap-3"><span class="w-10 text-xs text-slate-500">Apr</span><div class="h-3 flex-1 rounded-full bg-slate-200"><div class="h-3 w-[72%] rounded-full bg-teal-400"></div></div><span class="text-xs font-semibold text-slate-900">k</span></div>
                                            <div class="flex items-center gap-3"><span class="w-10 text-xs text-slate-500">May</span><div class="h-3 flex-1 rounded-full bg-slate-200"><div class="h-3 w-[84%] rounded-full bg-teal-500"></div></div><span class="text-xs font-semibold text-slate-900">k</span></div>
                                            <div class="flex items-center gap-3"><span class="w-10 text-xs text-slate-500">Jun</span><div class="h-3 flex-1 rounded-full bg-slate-200"><div class="h-3 w-[93%] rounded-full bg-slate-900"></div></div><span class="text-xs font-semibold text-slate-900">k</span></div>
                                        </div>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                        <p class="text-sm font-semibold text-slate-900">Usage hotspots</p>
                                        <div class="mt-4 space-y-3">
                                            <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-3"><span class="text-sm text-slate-600">Tower East</span><span class="font-semibold text-slate-900">18.2 TB</span></div>
                                            <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-3"><span class="text-sm text-slate-600">POP North</span><span class="font-semibold text-slate-900">14.7 TB</span></div>
                                            <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-3"><span class="text-sm text-slate-600">Fiber Core</span><span class="font-semibold text-slate-900">22.9 TB</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="order-1 md:order-2">
                        <div class="w-16 h-16 bg-teal-100 rounded-2xl flex items-center justify-center mb-6">
                            <svg class="w-8 h-8 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-3xl font-bold text-gray-900 mb-4">Reports & Analytics</h3>
                        <p class="text-lg text-gray-600 mb-6">
                            Understand your ISP operations with powerful reports and analytics. Monitor growth, revenue, subscriber activity, and operational performance.
                        </p>
                        <ul class="space-y-3">
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-teal-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Revenue reporting</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-teal-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Subscriber growth metrics</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-teal-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Service performance insights</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-teal-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Operational dashboards</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-teal-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Exportable reports</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Platform Section -->
    <section class="py-20 bg-gradient-to-br from-blue-600 to-indigo-700">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto text-center">
                <h2 class="text-3xl font-bold text-white mb-6">Built for Modern ISP Infrastructure</h2>
                <p class="text-xl text-blue-100 mb-12">
                    SkyBase is designed specifically for WISPs and fiber providers using modern networking equipment and authentication systems.
                </p>

                <div class="grid grid-cols-2 md:grid-cols-5 gap-6">
                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6">
                        <div class="text-white font-semibold text-lg">MikroTik Support</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6">
                        <div class="text-white font-semibold text-lg">PPPoE Authentication</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6">
                        <div class="text-white font-semibold text-lg">Hotspot Networks</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6">
                        <div class="text-white font-semibold text-lg">RADIUS Integration</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6">
                        <div class="text-white font-semibold text-lg">Scalable Cloud Architecture</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Final CTA Section -->
    <section class="py-20 bg-gray-50">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl mx-auto text-center">
                <h2 class="text-3xl font-bold text-gray-900 mb-6">Start Running Your ISP More Efficiently</h2>
                <p class="text-xl text-gray-600 mb-8">
                    SkyBase gives you the tools to manage subscribers, automate billing, and scale your ISP operations with confidence.
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="{{ route('auth.register') }}" class="inline-flex items-center justify-center px-10 py-4 text-base font-semibold text-white bg-blue-600 rounded-2xl hover:bg-blue-700 transition-colors">
                        Start Free Trial
                    </a>
                    <a href="{{ url('/pricing') }}" class="inline-flex items-center justify-center px-10 py-4 text-base font-semibold text-gray-700 bg-white border border-gray-300 rounded-2xl hover:bg-gray-50 transition-colors">
                        View Pricing
                    </a>
                </div>
            </div>
        </div>
    </section>

@endsection
