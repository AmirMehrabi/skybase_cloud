<!-- Plan Form -->
<div x-data="planForm()" class="space-y-8">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Basic Information</h3>
            <p class="text-sm text-gray-500 mt-1">General plan details</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Plan Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" x-model="form.name" placeholder="e.g., Home 50 Mbps" class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Internal Name <span class="text-red-500">*</span></label>
                <input type="text" name="internal_name" x-model="form.internalName" placeholder="e.g., home_50" class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                <select name="category" x-model="form.category" class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Select category</option>
                    <option value="Residential">Residential</option>
                    <option value="Business">Business</option>
                    <option value="Enterprise">Enterprise</option>
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description" x-model="form.description" rows="3" placeholder="Brief description of the plan..." class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" x-model="form.status" class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="archived">Archived</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Visibility</label>
                <select name="visibility" x-model="form.visibility" class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="public">Public</option>
                    <option value="private">Private</option>
                    <option value="hidden">Hidden</option>
                </select>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Service Type</h3>
            <p class="text-sm text-gray-500 mt-1">Network configuration</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Service Type <span class="text-red-500">*</span></label>
                <select name="type" x-model="form.type" class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Select type</option>
                    <option value="pppoe">PPPoE</option>
                    <option value="hotspot">Hotspot</option>
                    <option value="static">Static IP</option>
                    <option value="dhcp">DHCP</option>
                    <option value="fiber">Fiber</option>
                    <option value="wireless">Wireless</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Router Profile</label>
                <input type="text" name="router_profile" x-model="form.routerProfile" placeholder="e.g., Mikrotik PPPoE Basic" class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">IP Pool</label>
                <input type="text" name="ip_pool" x-model="form.ipPool" placeholder="e.g., Dynamic Pool" class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                <input type="number" name="priority" x-model="form.priority" min="1" max="10" placeholder="1-10" class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                <p class="mt-1 text-xs text-gray-500">1 (lowest) to 10 (highest)</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Speed Configuration</h3>
            <p class="text-sm text-gray-500 mt-1">Bandwidth and data settings</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Download Speed</label>
                <div class="flex gap-2">
                    <input type="number" name="download_speed" x-model="form.downloadSpeed" min="0" placeholder="50" class="flex-1 block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <select name="bandwidth_unit" x-model="form.bandwidthUnit" class="block w-24 px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="Kbps">Kbps</option>
                        <option value="Mbps">Mbps</option>
                        <option value="Gbps">Gbps</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Upload Speed</label>
                <input type="number" name="upload_speed" x-model="form.uploadSpeed" min="0" placeholder="10" class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Burst Download</label>
                <input type="number" name="burst_download" x-model="form.burstDownload" min="0" placeholder="80" class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Burst Upload</label>
                <input type="number" name="burst_upload" x-model="form.burstUpload" min="0" placeholder="20" class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="md:col-span-2">
                <div class="flex items-center justify-between rounded-xl border border-gray-200 p-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Unlimited Data</label>
                        <p class="text-xs text-gray-500 mt-1">Enable if this plan has no data cap</p>
                    </div>
                    <div>
                        <input type="hidden" name="unlimited" value="0">
                        <input type="checkbox" name="unlimited" value="1" x-model="form.unlimited" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <div x-show="!form.unlimited" class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Data Limit</label>
                <div class="flex gap-2">
                    <input type="number" name="data_limit" x-model="form.dataLimit" min="0" placeholder="500" class="flex-1 block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <select name="data_unit" x-model="form.dataUnit" class="block w-24 px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="MB">MB</option>
                        <option value="GB">GB</option>
                        <option value="TB">TB</option>
                    </select>
                </div>
            </div>
            <input x-show="form.unlimited" type="hidden" name="data_unit" :value="form.dataUnit">
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Billing</h3>
            <p class="text-sm text-gray-500 mt-1">Pricing and billing settings</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Price</label>
                <div class="flex gap-2">
                    <input type="number" name="price" x-model="form.price" min="0" step="0.01" placeholder="25.00" class="flex-1 block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <select name="currency" x-model="form.currency" class="block w-24 px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="USD">USD</option>
                        <option value="EUR">EUR</option>
                        <option value="GBP">GBP</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Billing Cycle</label>
                <select name="billing_cycle" x-model="form.billingCycle" class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                    <option value="quarterly">Quarterly</option>
                    <option value="yearly">Yearly</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Setup Fee</label>
                <input type="number" name="setup_fee" x-model="form.setupFee" min="0" step="0.01" placeholder="10.00" class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tax Profile</label>
                <input type="text" name="tax_profile" x-model="form.taxProfile" placeholder="e.g., Standard VAT" class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Contract & Availability</h3>
            <p class="text-sm text-gray-500 mt-1">Terms and schedule</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2 flex items-center justify-between rounded-xl border border-gray-200 p-4">
                <div>
                    <label class="text-sm font-medium text-gray-700">Contract Required</label>
                    <p class="text-xs text-gray-500 mt-1">Enable if customers must sign a contract</p>
                </div>
                <div>
                    <input type="hidden" name="contract_required" value="0">
                    <input type="checkbox" name="contract_required" value="1" x-model="form.contractRequired" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                </div>
            </div>

            <div x-show="form.contractRequired">
                <label class="block text-sm font-medium text-gray-700 mb-2">Contract Duration (months)</label>
                <input type="number" name="contract_duration" x-model="form.contractDuration" min="1" placeholder="12" class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Available From</label>
                <input type="date" name="available_from" x-model="form.availableFrom" class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Available To</label>
                <input type="date" name="available_to" x-model="form.availableTo" class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                <textarea name="notes" x-model="form.notes" rows="4" placeholder="Additional notes or internal remarks..." class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"></textarea>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-end gap-3">
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
            name: @js(old('name', data_get($plan, 'name', ''))),
            internalName: @js(old('internal_name', data_get($plan, 'internal_name', ''))),
            description: @js(old('description', data_get($plan, 'description', ''))),
            status: @js(old('status', data_get($plan, 'status', 'active'))),
            visibility: @js(old('visibility', data_get($plan, 'visibility', 'public'))),
            category: @js(old('category', data_get($plan, 'category', ''))),
            type: @js(old('type', data_get($plan, 'type', ''))),
            routerProfile: @js(old('router_profile', data_get($plan, 'router_profile', ''))),
            ipPool: @js(old('ip_pool', data_get($plan, 'ip_pool', ''))),
            priority: @js(old('priority', data_get($plan, 'priority', 5))),
            downloadSpeed: @js(old('download_speed', data_get($plan, 'download_speed', ''))),
            uploadSpeed: @js(old('upload_speed', data_get($plan, 'upload_speed', ''))),
            bandwidthUnit: @js(old('bandwidth_unit', data_get($plan, 'bandwidth_unit', 'Mbps'))),
            burstDownload: @js(old('burst_download', data_get($plan, 'burst_download', ''))),
            burstUpload: @js(old('burst_upload', data_get($plan, 'burst_upload', ''))),
            unlimited: @js((bool) old('unlimited', data_get($plan, 'unlimited', false))),
            dataLimit: @js(old('data_limit', data_get($plan, 'data_limit', ''))),
            dataUnit: @js(old('data_unit', data_get($plan, 'data_unit', 'GB'))),
            price: @js(old('price', data_get($plan, 'price', ''))),
            currency: @js(old('currency', data_get($plan, 'currency', 'USD'))),
            billingCycle: @js(old('billing_cycle', data_get($plan, 'billing_cycle', 'monthly'))),
            setupFee: @js(old('setup_fee', data_get($plan, 'setup_fee', ''))),
            taxProfile: @js(old('tax_profile', data_get($plan, 'tax_profile', ''))),
            contractRequired: @js((bool) old('contract_required', data_get($plan, 'contract_required', false))),
            contractDuration: @js(old('contract_duration', data_get($plan, 'contract_duration', ''))),
            availableFrom: @js(old('available_from', data_get($plan, 'available_from') instanceof \Carbon\CarbonInterface ? data_get($plan, 'available_from')->format('Y-m-d') : data_get($plan, 'available_from', ''))),
            availableTo: @js(old('available_to', data_get($plan, 'available_to') instanceof \Carbon\CarbonInterface ? data_get($plan, 'available_to')->format('Y-m-d') : data_get($plan, 'available_to', ''))),
            notes: @js(old('notes', data_get($plan, 'notes', ''))),
        }
    };
}
</script>
