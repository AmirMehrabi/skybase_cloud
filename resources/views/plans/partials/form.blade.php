<!-- Plan Form -->
<div x-data="planForm()" class="space-y-8">
    <!-- Section 1: Basic Information -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Basic Information</h3>
            <p class="text-sm text-gray-500 mt-1">General plan details</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Plan Name -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Plan Name <span class="text-red-500">*</span></label>
                <input type="text" x-model="form.name" placeholder="e.g., Home 50 Mbps" class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Internal Name -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Internal Name <span class="text-red-500">*</span></label>
                <input type="text" x-model="form.internalName" placeholder="e.g., home_50" class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Category -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                <select x-model="form.category" class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Select category</option>
                    <option value="Residential">Residential</option>
                    <option value="Business">Business</option>
                    <option value="Enterprise">Enterprise</option>
                </select>
            </div>

            <!-- Description -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea x-model="form.description" rows="3" placeholder="Brief description of the plan..." class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"></textarea>
            </div>

            <!-- Status -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select x-model="form.status" class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="archived">Archived</option>
                </select>
            </div>

            <!-- Visibility -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Visibility</label>
                <select x-model="form.visibility" class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="public">Public</option>
                    <option value="private">Private</option>
                    <option value="hidden">Hidden</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Section 2: Service Type -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Service Type</h3>
            <p class="text-sm text-gray-500 mt-1">Network configuration</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Service Type -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Service Type <span class="text-red-500">*</span></label>
                <select x-model="form.type" class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Select type</option>
                    <option value="pppoe">PPPoE</option>
                    <option value="hotspot">Hotspot</option>
                    <option value="static">Static IP</option>
                    <option value="dhcp">DHCP</option>
                    <option value="fiber">Fiber</option>
                    <option value="wireless">Wireless</option>
                </select>
            </div>

            <!-- Router Profile -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Router Profile</label>
                <input type="text" x-model="form.routerProfile" placeholder="e.g., Mikrotik PPPoE Basic" class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- IP Pool -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">IP Pool</label>
                <input type="text" x-model="form.ipPool" placeholder="e.g., Dynamic Pool" class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Priority -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                <input type="number" x-model="form.priority" min="1" max="10" placeholder="1-10" class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                <p class="mt-1 text-xs text-gray-500">1 (lowest) to 10 (highest)</p>
            </div>
        </div>
    </div>

    <!-- Section 3: Speed Configuration -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Speed Configuration</h3>
            <p class="text-sm text-gray-500 mt-1">Bandwidth settings</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Download Speed -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Download Speed <span class="text-red-500">*</span></label>
                <div class="flex gap-2">
                    <input type="number" x-model="form.downloadSpeed" min="0" placeholder="50" class="flex-1 block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <select x-model="form.bandwidthUnit" class="block w-24 px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="Kbps">Kbps</option>
                        <option value="Mbps">Mbps</option>
                        <option value="Gbps">Gbps</option>
                    </select>
                </div>
            </div>

            <!-- Upload Speed -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Upload Speed <span class="text-red-500">*</span></label>
                <div class="flex gap-2">
                    <input type="number" x-model="form.uploadSpeed" min="0" placeholder="10" class="flex-1 block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <select x-model="form.bandwidthUnit" class="block w-24 px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="Kbps">Kbps</option>
                        <option value="Mbps">Mbps</option>
                        <option value="Gbps">Gbps</option>
                    </select>
                </div>
            </div>

            <!-- Burst Speeds -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Burst Speeds (Optional)</label>
                <div class="flex gap-2">
                    <input type="text" x-model="form.burstSpeeds" placeholder="80/20" class="flex-1 block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <select disabled class="block w-24 px-3 py-2.5 text-sm border border-gray-300 rounded-lg bg-gray-50 text-gray-500">
                        <option>Mbps</option>
                    </select>
                </div>
                <p class="mt-1 text-xs text-gray-500">Format: download/upload</p>
            </div>
        </div>
    </div>

    <!-- Section 4: Data Limit -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Data Limit</h3>
                    <p class="text-sm text-gray-500 mt-1">Usage restrictions</p>
                </div>
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" x-model="form.unlimited" class="sr-only peer">
                    <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    <span class="ml-3 text-sm font-medium text-gray-900">Unlimited</span>
                </label>
            </div>
        </div>

        <div x-show="!form.unlimited" x-transition class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Data Limit -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Data Limit</label>
                <div class="flex gap-2">
                    <input type="number" x-model="form.dataLimit" min="0" placeholder="500" class="flex-1 block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <select x-model="form.dataUnit" class="block w-24 px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="MB">MB</option>
                        <option value="GB">GB</option>
                        <option value="TB">TB</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 5: Billing Configuration -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Billing Configuration</h3>
            <p class="text-sm text-gray-500 mt-1">Pricing and billing settings</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Price -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Price <span class="text-red-500">*</span></label>
                <div class="flex gap-2">
                    <input type="number" x-model="form.price" min="0" step="0.01" placeholder="25.00" class="flex-1 block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <select x-model="form.currency" class="block w-24 px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="USD">USD</option>
                        <option value="EUR">EUR</option>
                        <option value="IRR">IRR</option>
                    </select>
                </div>
            </div>

            <!-- Billing Cycle -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Billing Cycle <span class="text-red-500">*</span></label>
                <select x-model="form.billingCycle" class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Select cycle</option>
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                    <option value="quarterly">Quarterly</option>
                    <option value="yearly">Yearly</option>
                </select>
            </div>

            <!-- Setup Fee -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Setup Fee</label>
                <div class="flex gap-2">
                    <input type="number" x-model="form.setupFee" min="0" step="0.01" placeholder="10.00" class="flex-1 block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <span class="inline-flex items-center px-3 py-2.5 text-sm text-gray-500 bg-gray-50 border border-gray-300 rounded-lg w-24 justify-center" x-text="form.currency"></span>
                </div>
            </div>

            <!-- Tax Profile -->
            <div class="md:col-span-3">
                <label class="block text-sm font-medium text-gray-700 mb-2">Tax Profile</label>
                <input type="text" x-model="form.taxProfile" placeholder="e.g., Standard VAT" class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>
    </div>

    <!-- Section 6: Contract -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Contract</h3>
            <p class="text-sm text-gray-500 mt-1">Contract requirements</p>
        </div>

        <div class="flex items-center gap-4 mb-4">
            <label class="inline-flex items-center cursor-pointer">
                <input type="checkbox" x-model="form.contractRequired" class="sr-only peer">
                <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                <span class="ml-3 text-sm font-medium text-gray-900">Contract Required</span>
            </label>
        </div>

        <div x-show="form.contractRequired" x-transition class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Contract Duration -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Contract Duration (months)</label>
                <input type="number" x-model="form.contractDuration" min="1" placeholder="12" class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>
    </div>

    <!-- Section 7: Availability -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Availability</h3>
            <p class="text-sm text-gray-500 mt-1">Plan availability dates</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Available From -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Available From</label>
                <input type="date" x-model="form.availableFrom" class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Available To -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Available To</label>
                <input type="date" x-model="form.availableTo" class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                <p class="mt-1 text-xs text-gray-500">Leave empty for no end date</p>
            </div>
        </div>
    </div>

    <!-- Section 8: Notes -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Notes</h3>
            <p class="text-sm text-gray-500 mt-1">Additional information</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
            <textarea x-model="form.notes" rows="4" placeholder="Any additional notes or special instructions..." class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"></textarea>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="flex items-center justify-end gap-4 pt-6 border-t border-gray-200">
        <a href="{{ route('plans.index') }}" class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
            Cancel
        </a>
        <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            Save Plan
        </button>
    </div>
</div>

<script>
function planForm() {
    return {
        form: {
            name: '{{ $plan['name'] ?? '' }}',
            internalName: '{{ $plan['internal_name'] ?? '' }}',
            description: '{{ $plan['description'] ?? '' }}',
            status: '{{ $plan['status'] ?? 'active' }}',
            visibility: '{{ $plan['visibility'] ?? 'public' }}',
            category: '{{ $plan['category'] ?? '' }}',
            type: '{{ $plan['type'] ?? '' }}',
            routerProfile: '{{ $plan['router_profile'] ?? '' }}',
            ipPool: '{{ $plan['ip_pool'] ?? '' }}',
            priority: '{{ $plan['priority'] ?? 5 }}',
            downloadSpeed: '{{ $plan['download_speed'] ?? '' }}',
            uploadSpeed: '{{ $plan['upload_speed'] ?? '' }}',
            bandwidthUnit: '{{ $plan['bandwidth_unit'] ?? 'Mbps' }}',
            burstSpeeds: '{{ $plan['burst_download'] ?? '' }}/{{ $plan['burst_upload'] ?? '' }}',
            unlimited: {{ $plan['unlimited'] ?? 'false' }},
            dataLimit: '{{ $plan['data_limit'] ?? '' }}',
            dataUnit: '{{ $plan['data_unit'] ?? 'GB' }}',
            price: '{{ $plan['price'] ?? '' }}',
            currency: '{{ $plan['currency'] ?? 'USD' }}',
            billingCycle: '{{ $plan['billing_cycle'] ?? '' }}',
            setupFee: '{{ $plan['setup_fee'] ?? '' }}',
            taxProfile: '{{ $plan['tax_profile'] ?? '' }}',
            contractRequired: {{ $plan['contract_required'] ?? 'false' }},
            contractDuration: '{{ $plan['contract_duration'] ?? '' }}',
            availableFrom: '{{ $plan['available_from'] ?? '' }}',
            availableTo: '{{ $plan['available_to'] ?? '' }}',
            notes: '{{ $plan['notes'] ?? '' }}',
        }
    };
}
</script>
