@section('title', "Customer Profile - {$customer['first_name']} {$customer['last_name']}")

<div class="space-y-6">
    <!-- Profile Header Card -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-2xl p-6 text-white">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-4">
                <!-- Avatar -->
                <div class="w-20 h-20 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-2xl font-bold">
                    {{ strtoupper(substr($customer['first_name'] ?? 'U', 0, 1)) }}
                </div>
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold">{{ $customer['first_name'] }} {{ $customer['last_name'] }}</h1>
                        <x-ui.badge :status="$customer['status']">{{ ucfirst($customer['status']) }}</x-ui.badge>
                    </div>
                    <p class="text-blue-100 text-sm mt-1">{{ $customer['customer_code'] }}</p>
                    <p class="text-blue-100 text-sm">{{ $customer['email'] }} • {{ $customer['mobile'] }}</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('customers.edit', $customer['id']) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm font-medium transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit
                </a>
                <button class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm font-medium transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Create Invoice
                </button>
                <button class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-500 hover:bg-yellow-600 rounded-lg text-sm font-medium transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Suspend
                </button>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 pt-6 border-t border-white/20">
            <div>
                <p class="text-blue-100 text-xs uppercase tracking-wider">Current Plan</p>
                <p class="text-lg font-semibold mt-1">{{ $customer['plan'] }}</p>
            </div>
            <div>
                <p class="text-blue-100 text-xs uppercase tracking-wider">Balance</p>
                <p class="text-lg font-semibold mt-1 {{ $customer['balance'] < 0 ? 'text-green-300' : 'text-red-300' }}">
                    ${{ number_format($customer['balance'], 2) }}
                </p>
            </div>
            <div>
                <p class="text-blue-100 text-xs uppercase tracking-wider">IP Address</p>
                <p class="text-lg font-semibold mt-1 font-mono">{{ $customer['ip_address'] }}</p>
            </div>
            <div>
                <p class="text-blue-100 text-xs uppercase tracking-wider">Billing Cycle</p>
                <p class="text-lg font-semibold mt-1 capitalize">{{ $customer['billing_cycle'] }}</p>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div>
        <nav class="border-b border-gray-200">
            <div class="flex space-x-8 overflow-x-auto">
                @foreach($tabs as $key => $label)
                    <button
                        wire:click="$set('activeTab', '{{ $key }}')"
                        class="{{ $activeTab === $key ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </nav>

        <!-- Tab Content -->
        <div class="mt-6">
            @switch($activeTab)
                @case('overview')
                    <!-- Overview Tab -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Basic Info Card -->
                        <x-ui.card title="Basic Information">
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Customer Code</span>
                                    <span class="font-medium text-gray-900">{{ $customer['customer_code'] }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Type</span>
                                    <span class="font-medium text-gray-900 capitalize">{{ $customer['type'] }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">National ID</span>
                                    <span class="font-medium text-gray-900">{{ $customer['national_id'] }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Status</span>
                                    <span class="font-medium text-gray-900 capitalize">{{ $customer['status'] }}</span>
                                </div>
                            </div>
                        </x-ui.card>

                        <!-- Contact Info Card -->
                        <x-ui.card title="Contact Information">
                            <div class="space-y-3 text-sm">
                                <div>
                                    <span class="text-gray-500 block">Email</span>
                                    <span class="font-medium text-gray-900">{{ $customer['email'] }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 block">Phone</span>
                                    <span class="font-medium text-gray-900">{{ $customer['phone'] }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 block">Mobile</span>
                                    <span class="font-medium text-gray-900">{{ $customer['mobile'] }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 block">WhatsApp</span>
                                    <span class="font-medium text-gray-900">{{ $customer['whatsapp'] }}</span>
                                </div>
                            </div>
                        </x-ui.card>

                        <!-- Address Info Card -->
                        <x-ui.card title="Address Information">
                            <div class="space-y-3 text-sm">
                                <div>
                                    <span class="text-gray-500 block">Address</span>
                                    <span class="font-medium text-gray-900">{{ $customer['address'] }}</span>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <span class="text-gray-500 block">City</span>
                                        <span class="font-medium text-gray-900">{{ $customer['city'] }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 block">State</span>
                                        <span class="font-medium text-gray-900">{{ $customer['state'] }}</span>
                                    </div>
                                </div>
                                <div>
                                    <span class="text-gray-500 block">Country</span>
                                    <span class="font-medium text-gray-900">{{ $customer['country'] }}</span>
                                </div>
                            </div>
                        </x-ui.card>

                        <!-- Financial Summary Card -->
                        <x-ui.card title="Financial Summary">
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Balance</span>
                                    <span class="font-semibold {{ $customer['balance'] < 0 ? 'text-green-600' : 'text-red-600' }}">
                                        ${{ number_format($customer['balance'], 2) }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Credit Limit</span>
                                    <span class="font-medium text-gray-900">${{ number_format($customer['credit_limit'], 2) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Billing Cycle</span>
                                    <span class="font-medium text-gray-900 capitalize">{{ $customer['billing_cycle'] }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Tax Exempt</span>
                                    <span class="font-medium text-gray-900">{{ $customer['tax_exempt'] ? 'Yes' : 'No' }}</span>
                                </div>
                            </div>
                        </x-ui.card>

                        <!-- Network Assignment Card -->
                        <x-ui.card title="Network Assignment">
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Plan</span>
                                    <span class="font-medium text-gray-900">{{ $customer['plan'] }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Site</span>
                                    <span class="font-medium text-gray-900">{{ $customer['site'] }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Router</span>
                                    <span class="font-medium text-gray-900">{{ $customer['router'] }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">IP Address</span>
                                    <span class="font-medium text-gray-900 font-mono">{{ $customer['ip_address'] }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">MAC Address</span>
                                    <span class="font-medium text-gray-900 font-mono">{{ $customer['mac_address'] }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">PPPoE Username</span>
                                    <span class="font-medium text-gray-900">{{ $customer['pppoe_username'] }}</span>
                                </div>
                            </div>
                        </x-ui.card>
                    </div>

                @case('services')
                    <!-- Services Tab -->
                    <x-ui.card>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Service ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Plan</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Router</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">IP</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Activated</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Data Used</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($this->services as $service)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 text-sm font-medium text-gray-900">#SRV-{{ $service['id'] }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-700">{{ $service['plan'] }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-700">{{ $service['router'] }}</td>
                                            <td class="px-6 py-4 text-sm font-mono text-gray-700">{{ $service['ip'] }}</td>
                                            <td class="px-6 py-4">
                                                <x-ui.badge :status="$service['status']">{{ ucfirst($service['status']) }}</x-ui-badge>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500">{{ $service['activated_at'] }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-700">{{ $service['data_used'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </x-ui.card>

                @case('invoices')
                    <!-- Invoices Tab -->
                    <x-ui.card>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Invoice</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Due Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($this->invoices as $invoice)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 text-sm font-medium text-blue-600">{{ $invoice['number'] }}</td>
                                            <td class="px-6 py-4 text-sm font-semibold text-gray-900">${{ number_format($invoice['amount'], 2) }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-500">{{ $invoice['due_date'] }}</td>
                                            <td class="px-6 py-4">
                                                <x-ui.badge :status="$invoice['status']">{{ ucfirst($invoice['status']) }}</x-ui-badge>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <button class="text-blue-600 hover:text-blue-800 text-sm font-medium">View</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </x-ui.card>

                @case('usage')
                    <!-- Usage Tab -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <x-ui.card title="Monthly Data Usage">
                            <div class="h-64 flex items-center justify-center bg-gray-50 rounded-xl">
                                <div class="text-center">
                                    <svg class="w-16 h-16 text-gray-300 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                    <p class="text-sm text-gray-500 mt-2">Data usage chart placeholder</p>
                                </div>
                            </div>
                        </x-ui.card>

                        <x-ui.card title="Session History">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Session 1</p>
                                        <p class="text-xs text-gray-500">Feb 17, 2024 - 10:30 AM</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-gray-900">2h 45m</p>
                                        <p class="text-xs text-gray-500">1.2 GB</p>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Session 2</p>
                                        <p class="text-xs text-gray-500">Feb 16, 2024 - 2:15 PM</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-gray-900">4h 20m</p>
                                        <p class="text-xs text-gray-500">3.8 GB</p>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Session 3</p>
                                        <p class="text-xs text-gray-500">Feb 15, 2024 - 9:00 AM</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-gray-900">5h 10m</p>
                                        <p class="text-xs text-gray-500">4.5 GB</p>
                                    </div>
                                </div>
                            </div>
                        </x-ui.card>
                    </div>

                @case('tickets')
                    <!-- Tickets Tab -->
                    <x-ui.card>
                        <div class="text-center py-12">
                            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mt-4">No Support Tickets</h3>
                            <p class="text-sm text-gray-500 mt-1">This customer has no open or closed tickets.</p>
                            <button class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Create Ticket
                            </button>
                        </div>
                    </x-ui.card>

                @case('activity')
                    <!-- Activity Log Tab -->
                    <x-ui.card title="Activity Timeline">
                        <div class="space-y-6">
                            @foreach($this->activityLog as $index => $activity)
                                <div class="flex gap-4">
                                    <div class="flex flex-col items-center">
                                        <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center">
                                            @if($activity['icon'] === 'payment')
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                </svg>
                                            @elseif($activity['icon'] === 'check')
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            @elseif($activity['icon'] === 'upgrade')
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                                </svg>
                                            @elseif($activity['icon'] === 'invoice')
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                            @else
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                </svg>
                                            @endif
                                        </div>
                                        @if($loop->last)
                                            <div class="w-0.5 h-0 bg-gray-200 mt-2"></div>
                                        @else
                                            <div class="w-0.5 flex-1 bg-gray-200 mt-2"></div>
                                        @endif
                                    </div>
                                    <div class="flex-1 pb-6">
                                        <div class="flex items-center justify-between">
                                            <p class="text-sm font-medium text-gray-900">{{ $activity['action'] }}</p>
                                            <p class="text-xs text-gray-500">{{ $activity['time'] }}</p>
                                        </div>
                                        <p class="text-sm text-gray-500 mt-1">{{ $activity['description'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </x-ui.card>
            @endswitch
        </div>
    </div>
</div>
