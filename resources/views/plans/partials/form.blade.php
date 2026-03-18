<!-- Plan Form -->
<div x-data="planForm()" @submit.prevent="validateAndSubmit">
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">
                        There were {{ count($errors->all()) }} error(s) with your submission:
                    </h3>
                    <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <div class="space-y-8">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900">Basic Information</h3>
                <p class="text-sm text-gray-500 mt-1">General plan details</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2" :class="{ 'has-error': frontendErrors.name }">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Plan Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" x-model="form.name" @blur="validateField('name')" placeholder="e.g., Home 50 Mbps" :class="frontendErrors.name || {{ $errors->has('name') ? 'true' : 'false' }} ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'" class="block w-full px-4 py-2.5 text-sm border rounded-lg">
                    @error('name')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <template x-if="frontendErrors.name && !{{ $errors->has('name') ? 'true' : 'false' }}">
                        <p class="mt-1.5 text-sm text-red-600" x-text="frontendErrors.name"></p>
                    </template>
                </div>

                <div :class="{ 'has-error': frontendErrors.internalName }">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Internal Name <span class="text-red-500">*</span></label>
                    <input type="text" name="internal_name" x-model="form.internalName" @blur="validateField('internalName')" placeholder="e.g., home_50" :class="frontendErrors.internalName || {{ $errors->has('internal_name') ? 'true' : 'false' }} ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'" class="block w-full px-4 py-2.5 text-sm border rounded-lg">
                    @error('internal_name')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <template x-if="frontendErrors.internalName && !{{ $errors->has('internal_name') ? 'true' : 'false' }}">
                        <p class="mt-1.5 text-sm text-red-600" x-text="frontendErrors.internalName"></p>
                    </template>
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
            <div :class="{ 'has-error': frontendErrors.type }">
                <label class="block text-sm font-medium text-gray-700 mb-2">Service Type <span class="text-red-500">*</span></label>
                <select name="type" x-model="form.type" @blur="validateField('type')" :class="frontendErrors.type || {{ $errors->has('type') ? 'true' : 'false' }} ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'" class="block w-full px-4 py-2.5 text-sm border rounded-lg">
                    <option value="">Select type</option>
                    <option value="pppoe">PPPoE</option>
                    <option value="hotspot">Hotspot</option>
                    <option value="static">Static IP</option>
                    <option value="dhcp">DHCP</option>
                    <option value="fiber">Fiber</option>
                    <option value="wireless">Wireless</option>
                </select>
                @error('type')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <template x-if="frontendErrors.type && !{{ $errors->has('type') ? 'true' : 'false' }}">
                    <p class="mt-1.5 text-sm text-red-600" x-text="frontendErrors.type"></p>
                </template>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Router Profile</label>
                <input type="text" name="router_profile" x-model="form.routerProfile" placeholder="e.g., Mikrotik PPPoE Basic" class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">IP Pool</label>
                <input type="text" name="ip_pool" x-model="form.ipPool" placeholder="e.g., Dynamic Pool" class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div :class="{ 'has-error': frontendErrors.priority }">
                <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                <input type="number" name="priority" x-model="form.priority" @blur="validateField('priority')" min="1" max="10" placeholder="1-10" :class="frontendErrors.priority || {{ $errors->has('priority') ? 'true' : 'false' }} ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'" class="block w-full px-4 py-2.5 text-sm border rounded-lg">
                <p class="mt-1 text-xs text-gray-500">1 (lowest) to 10 (highest)</p>
                @error('priority')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <template x-if="frontendErrors.priority && !{{ $errors->has('priority') ? 'true' : 'false' }}">
                    <p class="mt-1.5 text-sm text-red-600" x-text="frontendErrors.priority"></p>
                </template>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Speed Configuration</h3>
            <p class="text-sm text-gray-500 mt-1">Bandwidth and data settings</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div :class="{ 'has-error': frontendErrors.downloadSpeed }">
                <label class="block text-sm font-medium text-gray-700 mb-2">Download Speed <span class="text-red-500">*</span></label>
                <div class="flex gap-2">
                    <input type="number" name="download_speed" x-model="form.downloadSpeed" @blur="validateField('downloadSpeed')" min="0" placeholder="50" :class="frontendErrors.downloadSpeed || {{ $errors->has('download_speed') ? 'true' : 'false' }} ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'" class="flex-1 block w-full px-4 py-2.5 text-sm border rounded-lg">
                    <select name="bandwidth_unit" x-model="form.bandwidthUnit" class="block w-24 px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="Kbps">Kbps</option>
                        <option value="Mbps">Mbps</option>
                        <option value="Gbps">Gbps</option>
                    </select>
                </div>
                @error('download_speed')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <template x-if="frontendErrors.downloadSpeed && !{{ $errors->has('download_speed') ? 'true' : 'false' }}">
                    <p class="mt-1.5 text-sm text-red-600" x-text="frontendErrors.downloadSpeed"></p>
                </template>
            </div>

            <div :class="{ 'has-error': frontendErrors.uploadSpeed }">
                <label class="block text-sm font-medium text-gray-700 mb-2">Upload Speed <span class="text-red-500">*</span></label>
                <input type="number" name="upload_speed" x-model="form.uploadSpeed" @blur="validateField('uploadSpeed')" min="0" placeholder="10" :class="frontendErrors.uploadSpeed || {{ $errors->has('upload_speed') ? 'true' : 'false' }} ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'" class="block w-full px-4 py-2.5 text-sm border rounded-lg">
                @error('upload_speed')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <template x-if="frontendErrors.uploadSpeed && !{{ $errors->has('upload_speed') ? 'true' : 'false' }}">
                    <p class="mt-1.5 text-sm text-red-600" x-text="frontendErrors.uploadSpeed"></p>
                </template>
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

            <div x-show="!form.unlimited" class="md:col-span-2" :class="{ 'has-error': frontendErrors.dataLimit }">
                <label class="block text-sm font-medium text-gray-700 mb-2">Data Limit</label>
                <div class="flex gap-2">
                    <input type="number" name="data_limit" x-model="form.dataLimit" @blur="validateField('dataLimit')" min="0" placeholder="500" :class="frontendErrors.dataLimit || {{ $errors->has('data_limit') ? 'true' : 'false' }} ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'" class="flex-1 block w-full px-4 py-2.5 text-sm border rounded-lg">
                    <select name="data_unit" x-model="form.dataUnit" class="block w-24 px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="MB">MB</option>
                        <option value="GB">GB</option>
                        <option value="TB">TB</option>
                    </select>
                </div>
                @error('data_limit')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <template x-if="frontendErrors.dataLimit && !{{ $errors->has('data_limit') ? 'true' : 'false' }}">
                    <p class="mt-1.5 text-sm text-red-600" x-text="frontendErrors.dataLimit"></p>
                </template>
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
            <div :class="{ 'has-error': frontendErrors.price }">
                <label class="block text-sm font-medium text-gray-700 mb-2">Price <span class="text-red-500">*</span></label>
                <div class="flex gap-2">
                    <input type="number" name="price" x-model="form.price" @blur="validateField('price')" min="0" step="0.01" placeholder="25.00" :class="frontendErrors.price || {{ $errors->has('price') ? 'true' : 'false' }} ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'" class="flex-1 block w-full px-4 py-2.5 text-sm border rounded-lg">
                    <select name="currency" x-model="form.currency" class="block w-24 px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="USD">USD</option>
                        <option value="EUR">EUR</option>
                        <option value="GBP">GBP</option>
                    </select>
                </div>
                @error('price')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <template x-if="frontendErrors.price && !{{ $errors->has('price') ? 'true' : 'false' }}">
                    <p class="mt-1.5 text-sm text-red-600" x-text="frontendErrors.price"></p>
                </template>
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

            <div x-show="form.contractRequired" :class="{ 'has-error': frontendErrors.contractDuration }">
                <label class="block text-sm font-medium text-gray-700 mb-2">Contract Duration (months)</label>
                <input type="number" name="contract_duration" x-model="form.contractDuration" @blur="validateField('contractDuration')" min="1" placeholder="12" :class="frontendErrors.contractDuration || {{ $errors->has('contract_duration') ? 'true' : 'false' }} ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'" class="block w-full px-4 py-2.5 text-sm border rounded-lg">
                @error('contract_duration')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <template x-if="frontendErrors.contractDuration && !{{ $errors->has('contract_duration') ? 'true' : 'false' }}">
                    <p class="mt-1.5 text-sm text-red-600" x-text="frontendErrors.contractDuration"></p>
                </template>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Available From</label>
                <input type="date" name="available_from" x-model="form.availableFrom" class="block w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div :class="{ 'has-error': frontendErrors.availableTo }">
                <label class="block text-sm font-medium text-gray-700 mb-2">Available To</label>
                <input type="date" name="available_to" x-model="form.availableTo" @blur="validateField('availableTo')" :class="frontendErrors.availableTo || {{ $errors->has('available_to') ? 'true' : 'false' }} ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'" class="block w-full px-4 py-2.5 text-sm border rounded-lg">
                @error('available_to')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <template x-if="frontendErrors.availableTo && !{{ $errors->has('available_to') ? 'true' : 'false' }}">
                    <p class="mt-1.5 text-sm text-red-600" x-text="frontendErrors.availableTo"></p>
                </template>
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
        <button type="submit" :disabled="isSubmitting" class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
            <svg x-show="isSubmitting" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span x-text="isSubmitting ? 'Saving...' : 'Save Plan'"></span>
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
        },
        frontendErrors: {},
        isSubmitting: false,

        validateField(field) {
            this.frontendErrors[field] = '';

            switch(field) {
                case 'name':
                    if (!this.form.name || this.form.name.trim() === '') {
                        this.frontendErrors.name = 'The plan name is required.';
                    } else if (this.form.name.length > 255) {
                        this.frontendErrors.name = 'The plan name must not exceed 255 characters.';
                    }
                    break;

                case 'internalName':
                    if (!this.form.internalName || this.form.internalName.trim() === '') {
                        this.frontendErrors.internalName = 'The internal name is required.';
                    } else if (!/^[a-z0-9_]+$/.test(this.form.internalName)) {
                        this.frontendErrors.internalName = 'The internal name must contain only lowercase letters, numbers, and underscores.';
                    } else if (this.form.internalName.length > 255) {
                        this.frontendErrors.internalName = 'The internal name must not exceed 255 characters.';
                    }
                    break;

                case 'type':
                    if (!this.form.type) {
                        this.frontendErrors.type = 'The service type is required.';
                    }
                    break;

                case 'downloadSpeed':
                    if (!this.form.downloadSpeed || this.form.downloadSpeed === '') {
                        this.frontendErrors.downloadSpeed = 'The download speed is required.';
                    } else if (parseFloat(this.form.downloadSpeed) < 0) {
                        this.frontendErrors.downloadSpeed = 'The download speed must be at least 0.';
                    }
                    break;

                case 'uploadSpeed':
                    if (!this.form.uploadSpeed || this.form.uploadSpeed === '') {
                        this.frontendErrors.uploadSpeed = 'The upload speed is required.';
                    } else if (parseFloat(this.form.uploadSpeed) < 0) {
                        this.frontendErrors.uploadSpeed = 'The upload speed must be at least 0.';
                    }
                    break;

                case 'price':
                    if (!this.form.price || this.form.price === '') {
                        this.frontendErrors.price = 'The price is required.';
                    } else if (parseFloat(this.form.price) < 0) {
                        this.frontendErrors.price = 'The price must be at least 0.';
                    }
                    break;

                case 'priority':
                    if (this.form.priority !== '' && this.form.priority !== null) {
                        const priority = parseInt(this.form.priority);
                        if (isNaN(priority) || priority < 1 || priority > 10) {
                            this.frontendErrors.priority = 'The priority must be between 1 and 10.';
                        }
                    }
                    break;
            }
        },

        validateAll() {
            this.frontendErrors = {};
            let isValid = true;

            // Validate all required fields
            const requiredFields = ['name', 'internalName', 'type', 'downloadSpeed', 'uploadSpeed', 'price'];
            requiredFields.forEach(field => {
                this.validateField(field);
                if (this.frontendErrors[field]) {
                    isValid = false;
                }
            });

            // Conditional validation for data limit
            if (!this.form.unlimited) {
                if (!this.form.dataLimit || this.form.dataLimit === '') {
                    this.frontendErrors.dataLimit = 'The data limit is required when unlimited is disabled.';
                    isValid = false;
                } else if (parseFloat(this.form.dataLimit) < 0) {
                    this.frontendErrors.dataLimit = 'The data limit must be at least 0.';
                    isValid = false;
                }
            }

            // Conditional validation for contract duration
            if (this.form.contractRequired) {
                if (!this.form.contractDuration || this.form.contractDuration === '') {
                    this.frontendErrors.contractDuration = 'The contract duration is required when contract is required.';
                    isValid = false;
                } else if (parseInt(this.form.contractDuration) < 1) {
                    this.frontendErrors.contractDuration = 'The contract duration must be at least 1 month.';
                    isValid = false;
                }
            }

            // Date validation
            if (this.form.availableFrom && this.form.availableTo) {
                const fromDate = new Date(this.form.availableFrom);
                const toDate = new Date(this.form.availableTo);
                if (toDate < fromDate) {
                    this.frontendErrors.availableTo = 'The available to date must be after or equal to the available from date.';
                    isValid = false;
                }
            }

            return isValid;
        },

        validateAndSubmit() {
            if (this.isSubmitting) return;

            if (this.validateAll()) {
                this.isSubmitting = true;
                // Allow the form to submit normally
                this.$el.submit();
            } else {
                // Scroll to first error
                this.$nextTick(() => {
                    const firstError = document.querySelector('.border-red-500');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                });
            }
        }
    };
}
</script>
