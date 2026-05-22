@extends('layouts.admin')

@section('title', 'Subscription Details')

@php
$subscription = [
    'id' => $subscription->id,
    'subscription_code' => $subscription->subscription_code,
    'customer' => [
        'id' => $subscription->customer?->id,
        'name' => $subscription->customer?->full_name ?? 'N/A',
        'email' => $subscription->customer?->email ?? 'N/A',
        'phone' => $subscription->customer?->phone ?? $subscription->customer?->mobile ?? 'N/A',
    ],
    'plan_name' => $subscription->plan?->name ?? 'N/A',
    'plan_price' => (float) ($subscription->plan?->price ?? 0),
    'billing_cycle' => $subscription->billing_cycle ?? 'monthly',
    'status' => $subscription->status,
    'site' => $subscription->site ?? $subscription->customer?->site ?? 'N/A',
    'router' => $subscription->router?->name ?? 'N/A',
    'ip_address' => $subscription->ip_address ?? 'N/A',
    'mac_address' => $subscription->mac_address ?? 'N/A',
    'pppoe_username' => $subscription->pppoe_username ?? 'N/A',
    'pppoe_password' => $subscription->pppoe_password ?? 'N/A',
    'start_date' => optional($subscription->start_date ?? $subscription->activation_date ?? $subscription->created_at)->toDateString(),
    'end_date' => optional($subscription->end_date ?? $subscription->created_at?->copy()->addYear())->toDateString(),
    'billing_enabled' => (bool) $subscription->billing_enabled,
    'grace_period_days' => $subscription->effectiveGracePeriodDays(),
    'next_billing_date' => optional($subscription->next_billing_date ?? $subscription->created_at?->copy()->addMonth())->toDateString(),
    'last_billing_date' => optional($subscription->last_billed_at ?? $subscription->created_at)->toDateString(),
    'auto_renew' => true,
    'contract_start' => optional($subscription->start_date ?? $subscription->activation_date ?? $subscription->created_at)->toDateString(),
    'contract_end' => optional($subscription->end_date ?? $subscription->created_at?->copy()->addYear())->toDateString(),
    'installation_fee' => 0.00,
    'discount_percentage' => 0,
    'tax_percentage' => 0,
    'monthly_price' => (float) ($subscription->plan?->price ?? $subscription->total_price ?? 0),
    'total_price' => (float) $subscription->total_price,
    'balance' => (float) ($subscription->customer?->balance ?? 0),
    'data_quota' => 0,
    'data_used' => 0,
    'created_at' => optional($subscription->created_at)->toDateString(),
    'termination_fee' => 0.00,
    'speed_download' => 0,
    'speed_upload' => 0,
    'burst_mode' => false,
    'throttle_over_quota' => false,
];

$invoices = [
    [
        'id' => 1,
        'invoice_number' => 'INV-2025-0150',
        'amount' => 97.19,
        'due_date' => '2025-02-15',
        'status' => 'paid',
        'paid_date' => '2025-02-14',
    ],
    [
        'id' => 2,
        'invoice_number' => 'INV-2025-0145',
        'amount' => 97.19,
        'due_date' => '2025-01-15',
        'status' => 'paid',
        'paid_date' => '2025-01-14',
    ],
    [
        'id' => 3,
        'invoice_number' => 'INV-2024-0189',
        'amount' => 97.19,
        'due_date' => '2024-12-15',
        'status' => 'paid',
        'paid_date' => '2024-12-14',
    ],
    [
        'id' => 4,
        'invoice_number' => 'INV-2024-0132',
        'amount' => 97.19,
        'due_date' => '2024-11-15',
        'status' => 'paid',
        'paid_date' => '2024-11-13',
    ],
];

$dailySessions = [
    ['date' => '2025-02-16', 'duration' => '24h', 'download' => '12.5 GB', 'upload' => '2.1 GB'],
    ['date' => '2025-02-15', 'duration' => '24h', 'download' => '15.2 GB', 'upload' => '3.4 GB'],
    ['date' => '2025-02-14', 'duration' => '24h', 'download' => '11.8 GB', 'upload' => '1.9 GB'],
    ['date' => '2025-02-13', 'duration' => '24h', 'download' => '14.3 GB', 'upload' => '2.8 GB'],
    ['date' => '2025-02-12', 'duration' => '24h', 'download' => '13.7 GB', 'upload' => '2.5 GB'],
];

function getStatusBadgeClass($status)
{
    $classes = [
        'active' => 'bg-green-100 text-green-800 border-green-200',
        'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
        'suspended' => 'bg-red-100 text-red-800 border-red-200',
        'cancelled' => 'bg-gray-100 text-gray-800 border-gray-200',
        'expired' => 'bg-gray-100 text-gray-800 border-gray-200',
        'paid' => 'bg-green-100 text-green-800 border-green-200',
    ];

    return $classes[$status] ?? 'bg-gray-100 text-gray-800 border-gray-200';
}
@endphp

@section('content')
<div
    x-data="{
        tab: 'overview',
        copiedField: null,
        async copyCredential(field, value) {
            if (! value || value === 'N/A') {
                return;
            }

            try {
                await navigator.clipboard.writeText(value);
                this.copiedField = field;
                setTimeout(() => {
                    if (this.copiedField === field) {
                        this.copiedField = null;
                    }
                }, 1200);
            } catch (error) {
                this.copiedField = null;
            }
        },
    }"
    class="space-y-6"
>
    <!-- Top Header Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <!-- Subscription Info -->
            <div class="flex items-start gap-4">
                <div class="w-16 h-16 rounded-2xl bg-blue-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-gray-900">{{ $subscription['subscription_code'] }}</h1>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium border {{ getStatusBadgeClass($subscription['status']) }}">
                            {{ ucfirst($subscription['status']) }}
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-4 mt-2 text-sm text-gray-500">
                        <span>{{ $subscription['customer']['name'] }}</span>
                        <span>&bull;</span>
                        <span>{{ $subscription['plan_name'] }}</span>
                        <span>&bull;</span>
                        <span>${{ number_format($subscription['monthly_price'], 2) }}/mo</span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="flex items-center gap-3">
                <a href="{{ route('subscriptions.edit', $subscription['id']) }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit
                </a>

                
                @if($subscription['status'] === 'active')
                    <form method="POST" action="{{ route('subscriptions.suspend', $subscription['id']) }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-yellow-700 bg-yellow-50 border border-yellow-200 rounded-lg hover:bg-yellow-100 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Suspend
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('subscriptions.activate', $subscription['id']) }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-green-700 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Activate
                        </button>
                    </form>
                @endif

                <form method="POST" action="{{ route('subscriptions.generate-invoice', $subscription['id']) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-purple-700 bg-purple-50 border border-purple-200 rounded-lg hover:bg-purple-100 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Create Invoice
                    </button>
                </form>
            </div>
        </div>

        <!-- Key Metrics -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mt-6 pt-6 border-t border-gray-200">
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Balance</p>
                <p class="text-xl font-bold text-gray-900 mt-1">${{ number_format($subscription['balance'], 2) }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Next Billing</p>
                <p class="text-xl font-bold text-gray-900 mt-1">{{ \Carbon\Carbon::parse($subscription['next_billing_date'])->format('M d, Y') }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Data Used</p>
                <p class="text-xl font-bold text-gray-900 mt-1">{{ number_format($subscription['data_used']) }} GB</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Contract Ends</p>
                <p class="text-xl font-bold text-gray-900 mt-1">{{ \Carbon\Carbon::parse($subscription['contract_end'])->format('M d, Y') }}</p>
            </div>
        </div>
    </div>

    <!-- Billing Controls -->
    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <form method="POST" action="{{ route('subscriptions.billing.update', $subscription['id']) }}" class="grid grid-cols-1 md:grid-cols-[minmax(0,1fr)_160px_180px_auto] gap-4 md:items-end">
            @csrf
            @method('PATCH')
            <div class="flex items-center justify-between rounded-xl border border-gray-200 p-4">
                <div>
                    <label for="subscription_billing_enabled" class="text-sm font-medium text-gray-700">Billing Enabled</label>
                    <p class="text-xs text-gray-500 mt-1">Controls invoice generation and overdue suspension for this subscription.</p>
                </div>
                <div>
                    <input type="hidden" name="billing_enabled" value="0">
                    <input type="checkbox" id="subscription_billing_enabled" name="billing_enabled" value="1" @checked($subscription['billing_enabled']) class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                </div>
            </div>
            <div>
                <label for="subscription_grace_period_days" class="block text-sm font-medium text-gray-700 mb-1">Grace Days</label>
                <input type="number" id="subscription_grace_period_days" name="grace_period_days" min="0" max="365" value="{{ $subscription['grace_period_days'] }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
            </div>
            <div>
                <label for="subscription_next_billing_date" class="block text-sm font-medium text-gray-700 mb-1">Next Billing</label>
                <input type="date" id="subscription_next_billing_date" name="next_billing_date" value="{{ $subscription['next_billing_date'] }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
            </div>
            <button type="submit" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                Save Billing
            </button>
        </form>
    </div>

    <!-- Tabs Navigation -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px overflow-x-auto" aria-label="Tabs">
                <button @click="tab = 'overview'" :class="tab === 'overview' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                    Overview
                </button>
                <button @click="tab = 'billing'" :class="tab === 'billing' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                    Billing
                </button>
                <button @click="tab = 'usage'" :class="tab === 'usage' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                    Usage
                </button>
                <button @click="tab = 'contract'" :class="tab === 'contract' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                    Contract
                </button>
                <button @click="tab = 'invoices'" :class="tab === 'invoices' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                    Invoices
                </button>
                <button @click="tab = 'activity'" :class="tab === 'activity' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                    Activity Log
                </button>
            </nav>
        </div>

        <div class="p-6">
            <!-- TAB: Overview -->
            <div x-show="tab === 'overview'" x-transition class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Subscription Info -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Subscription Information</h3>
                            <p class="text-sm text-gray-500 mt-1">Basic subscription details</p>
                        </div>
                        <dl class="space-y-4">
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Subscription Code</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ $subscription['subscription_code'] }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Status</dt>
                                <dd><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ getStatusBadgeClass($subscription['status']) }}">{{ ucfirst($subscription['status']) }}</span></dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Service Plan</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ $subscription['plan_name'] }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Billing Cycle</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ ucfirst($subscription['billing_cycle']) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Service Start Date</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ \Carbon\Carbon::parse($subscription['start_date'])->format('M d, Y') }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Auto Renew</dt>
                                <dd class="text-sm font-medium text-gray-900">
                                    @if($subscription['auto_renew'])
                                        <span class="inline-flex items-center gap-1 text-green-600">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                            Enabled
                                        </span>
                                    @else
                                        <span class="text-gray-400">Disabled</span>
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Network Assignment -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Network Assignment</h3>
                            <p class="text-sm text-gray-500 mt-1">Network configuration details</p>
                        </div>
                        <dl class="space-y-4">
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Site/Location</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ $subscription['site'] }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Router/Device</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ $subscription['router'] }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">IP Address</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ $subscription['ip_address'] }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">MAC Address</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ $subscription['mac_address'] }}</dd>
                            </div>
                            <div class="pt-3 border-t border-gray-200 space-y-3">
                                <dt class="text-sm text-gray-500">PPPoE Credentials</dt>
                                <dd class="space-y-2">
                                    <button
                                        type="button"
                                        @click="copyCredential('username', @js($subscription['pppoe_username']))"
                                        class="w-full inline-flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-left hover:bg-gray-100 transition-colors"
                                    >
                                        <span class="text-xs text-gray-500 uppercase tracking-wide">Username</span>
                                        <span class="font-mono text-sm font-medium text-gray-900">{{ $subscription['pppoe_username'] }}</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="copyCredential('password', @js($subscription['pppoe_password']))"
                                        class="w-full inline-flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-left hover:bg-gray-100 transition-colors"
                                    >
                                        <span class="text-xs text-gray-500 uppercase tracking-wide">Password</span>
                                        <span class="font-mono text-sm font-medium text-gray-900">{{ $subscription['pppoe_password'] }}</span>
                                    </button>
                                    <p class="text-xs text-green-600" x-show="copiedField" x-transition>
                                        <span x-text="copiedField === 'username' ? 'Username copied' : 'Password copied'"></span>
                                    </p>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Billing Summary -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Billing Summary</h3>
                            <p class="text-sm text-gray-500 mt-1">Current billing information</p>
                        </div>
                        <dl class="space-y-4">
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Monthly Price</dt>
                                <dd class="text-sm font-medium text-gray-900">${{ number_format($subscription['monthly_price'], 2) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Discount</dt>
                                <dd class="text-sm font-medium text-green-600">-{{ $subscription['discount_percentage'] }}%</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Tax</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ $subscription['tax_percentage'] }}%</dd>
                            </div>
                            <div class="flex justify-between pt-3 border-t border-gray-200">
                                <dt class="text-sm font-medium text-gray-900">Total Monthly</dt>
                                <dd class="text-lg font-bold text-gray-900">${{ number_format($subscription['total_price'], 2) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Current Balance</dt>
                                <dd class="text-sm font-bold {{ $subscription['balance'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                                    ${{ number_format($subscription['balance'], 2) }}
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Next Billing Date</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ \Carbon\Carbon::parse($subscription['next_billing_date'])->format('M d, Y') }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Data Quota Summary -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Data Quota Summary</h3>
                            <p class="text-sm text-gray-500 mt-1">Current usage information</p>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm text-gray-600">{{ number_format($subscription['data_used']) }} GB used</span>
                                    <span class="text-sm text-gray-500">of {{ number_format($subscription['data_quota']) }} GB</span>
                                </div>
                                @php
                                    $dataQuota = (float) $subscription['data_quota'];
                                    $dataUsed = (float) $subscription['data_used'];
                                    $usagePercent = $dataQuota > 0 ? min(($dataUsed / $dataQuota) * 100, 100) : 0;
                                    $barColor = $usagePercent > 90 ? 'bg-red-500' : ($usagePercent > 70 ? 'bg-yellow-500' : 'bg-green-500');
                                @endphp
                                <div class="w-full bg-gray-200 rounded-full h-3">
                                    <div class="{{ $barColor }} h-3 rounded-full transition-all duration-500" style="width: {{ $usagePercent }}%"></div>
                                </div>
                                <p class="text-xs text-gray-500 mt-2">{{ number_format($usagePercent, 1) }}% of quota used</p>
                            </div>

                            <div class="pt-4 border-t border-gray-200 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-500">Download Speed</span>
                                    <span class="text-sm font-medium text-gray-900">{{ $subscription['speed_download'] }} Mbps</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-500">Upload Speed</span>
                                    <span class="text-sm font-medium text-gray-900">{{ $subscription['speed_upload'] }} Mbps</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-500">Burst Mode</span>
                                    <span class="text-sm font-medium text-gray-900">
                                        {{ $subscription['burst_mode'] ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: Billing -->
            <div x-show="tab === 'billing'" x-transition class="space-y-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Payment History</h3>
                        <p class="text-sm text-gray-500 mt-1">Recent payments and invoices</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Invoice Number</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Due Date</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Paid Date</th>
                                    <th class="px-6 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($invoices as $invoice)
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium text-blue-600">{{ $invoice['invoice_number'] }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium text-gray-900">${{ number_format($invoice['amount'], 2) }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($invoice['due_date'])->format('M d, Y') }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ getStatusBadgeClass($invoice['status']) }}">{{ ucfirst($invoice['status']) }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-900">{{ $invoice['paid_date'] ? \Carbon\Carbon::parse($invoice['paid_date'])->format('M d, Y') : '-' }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <button class="text-sm text-gray-500 hover:text-gray-700">View</button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB: Usage -->
            <div x-show="tab === 'usage'" x-transition class="space-y-6">
                <!-- Monthly Usage Chart Placeholder -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Monthly Usage Trends</h3>
                        <p class="text-sm text-gray-500 mt-1">Data consumption over time</p>
                    </div>
                    <div class="h-64 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl flex items-center justify-center">
                        <div class="text-center">
                            <svg class="w-16 h-16 text-blue-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            <p class="text-sm text-gray-500">Usage chart visualization</p>
                            <p class="text-xs text-gray-400 mt-1">Interactive graphs will be displayed here</p>
                        </div>
                    </div>
                </div>

                <!-- Daily Sessions -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Recent Sessions</h3>
                        <p class="text-sm text-gray-500 mt-1">Daily connection and usage data</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Duration</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Download</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Upload</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($dailySessions as $session)
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($session['date'])->format('M d, Y') }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-900">{{ $session['duration'] }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium text-green-600">{{ $session['download'] }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium text-blue-600">{{ $session['upload'] }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB: Contract -->
            <div x-show="tab === 'contract'" x-transition>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Contract Details</h3>
                            <p class="text-sm text-gray-500 mt-1">Service contract information</p>
                        </div>
                        <dl class="space-y-4">
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Contract Start</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ \Carbon\Carbon::parse($subscription['contract_start'])->format('M d, Y') }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Contract End</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ \Carbon\Carbon::parse($subscription['contract_end'])->format('M d, Y') }}</dd>
                            </div>
                            @php
                                $remainingDays = max(0, \Carbon\Carbon::parse($subscription['contract_end'])->diffInDays(\Carbon\Carbon::now()));
                            @endphp
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Remaining Days</dt>
                                <dd class="text-sm font-medium {{ $remainingDays < 30 ? 'text-red-600' : 'text-gray-900' }}">
                                    {{ $remainingDays }} days
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Auto Renew</dt>
                                <dd class="text-sm font-medium text-gray-900">
                                    {{ $subscription['auto_renew'] ? 'Yes' : 'No' }}
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Early Termination Fee</dt>
                                <dd class="text-sm font-medium text-gray-900">${{ number_format($subscription['termination_fee'], 2) }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Customer Information</h3>
                            <p class="text-sm text-gray-500 mt-1">Account holder details</p>
                        </div>
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ $subscription['customer']['name'] }}</p>
                                <p class="text-xs text-gray-500">{{ $subscription['customer']['id'] }}</p>
                            </div>
                        </div>
                        <dl class="space-y-3 pt-4 border-t border-gray-200">
                            <div class="flex items-center gap-3">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <span class="text-sm text-gray-900">{{ $subscription['customer']['email'] }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                                <span class="text-sm text-gray-900">{{ $subscription['customer']['phone'] }}</span>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- TAB: Invoices -->
            <div x-show="tab === 'invoices'" x-transition>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">All Invoices</h3>
                        <p class="text-sm text-gray-500 mt-1">Complete invoice history</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Invoice Number</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Due Date</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Paid Date</th>
                                    <th class="px-6 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($invoices as $invoice)
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium text-blue-600">{{ $invoice['invoice_number'] }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium text-gray-900">${{ number_format($invoice['amount'], 2) }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($invoice['due_date'])->format('M d, Y') }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ getStatusBadgeClass($invoice['status']) }}">{{ ucfirst($invoice['status']) }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-900">{{ $invoice['paid_date'] ? \Carbon\Carbon::parse($invoice['paid_date'])->format('M d, Y') : '-' }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <button class="text-sm text-gray-500 hover:text-gray-700">View</button>
                                            <button class="text-sm text-gray-500 hover:text-gray-700">Download</button>
                                            @if($invoice['status'] !== 'paid')
                                                <button class="text-sm text-blue-600 hover:text-blue-800">Pay Now</button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB: Activity Log -->
            <div x-show="tab === 'activity'" x-transition>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Activity Log</h3>
                        <p class="text-sm text-gray-500 mt-1">Subscription history and changes</p>
                    </div>
                    <x-activity-log :activities="$activityLog" />
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
