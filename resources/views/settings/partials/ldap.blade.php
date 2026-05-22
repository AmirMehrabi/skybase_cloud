@php
    $connection = $ldapSettings['connection'];
    $organizationSync = $ldapSettings['organization_sync'];
    $customerSync = $ldapSettings['customer_sync'];
    $subscriptionSync = $ldapSettings['subscription_sync'];
    $syncStatus = $ldapSettings['sync_status'];
    $hosts = implode("\n", $connection['hosts'] ?? []);
    $excludedOuDns = old('organization_excluded_ou_dns', $organizationSync['excluded_ou_dns'] ?? []);
@endphp

<div class="space-y-6">
    <form
        action="{{ route('settings.update.ldap') }}"
        method="POST"
        class="space-y-6"
        x-data="ldapOuPicker({
            discoverUrl: @js(route('settings.discover.ldap-organizational-units')),
            csrfToken: @js(csrf_token()),
            excludedDns: @js(array_values((array) $excludedOuDns))
        })"
    >
        @csrf
        @method('PUT')
        <template x-for="dn in excludedDns" :key="'excluded-ou-' + dn">
            <input type="hidden" name="organization_excluded_ou_dns[]" :value="dn">
        </template>

        <x-ui.card title="LDAP Sync" subtitle="Configure one-way Active Directory / LDAP synchronization for tenant organizations, customers, and subscriptions.">
            <div class="mb-5 flex items-center justify-between gap-4 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                <div>
                    <p class="text-sm font-medium text-gray-900">Sync status</p>
                    <p class="text-sm text-gray-500">When enabled, SkyBase polls LDAP and updates local tenant records.</p>
                </div>
                <div class="flex items-center gap-3">
                    <x-ui.badge :status="$connection['enabled'] ? 'active' : 'inactive'">
                        {{ $connection['enabled'] ? 'Enabled' : 'Disabled' }}
                    </x-ui.badge>
                    <x-ui.input.checkbox
                        name="enabled"
                        label="Enabled"
                        :checked="old('enabled', $connection['enabled'])"
                    />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                <div class="md:col-span-2">
                    <label for="hosts" class="mb-1.5 block text-sm font-medium text-gray-700">LDAP Hosts</label>
                    <textarea
                        id="hosts"
                        name="hosts"
                        rows="3"
                        class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="ldap1.example.com&#10;ldap2.example.com"
                    >{{ old('hosts', $hosts) }}</textarea>
                    @error('hosts')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @else
                        <p class="mt-1 text-sm text-gray-500">Enter one host per line or comma-separated.</p>
                    @enderror
                </div>

                <x-ui.input.text name="port" type="number" label="Port" :value="old('port', $connection['port'])" :error="$errors->first('port')" />
                <x-ui.input.text name="base_dn" label="Base DN" :value="old('base_dn', $connection['base_dn'])" placeholder="dc=example,dc=com" :error="$errors->first('base_dn')" />
                <x-ui.input.text name="username" label="Bind DN / Username" :value="old('username', $connection['username'])" placeholder="cn=readonly,dc=example,dc=com" :error="$errors->first('username')" />
                <x-ui.input.password name="password" label="Bind Password" placeholder="{{ filled($connection['password']) ? 'Leave blank to keep current password' : '' }}" :error="$errors->first('password')" />
                <x-ui.input.text name="timeout" type="number" label="Timeout Seconds" :value="old('timeout', $connection['timeout'])" :error="$errors->first('timeout')" />
                <x-ui.input.text name="sync_interval_minutes" type="number" label="Sync Interval Minutes" :value="old('sync_interval_minutes', $connection['sync_interval_minutes'])" :error="$errors->first('sync_interval_minutes')" />
                <x-ui.input.select
                    name="missing_action"
                    label="Missing LDAP Records"
                    :options="['mark_inactive' => 'Mark inactive / cancelled', 'ignore' => 'Do nothing', 'soft_delete' => 'Soft delete']"
                    :value="old('missing_action', $connection['missing_action'])"
                    :error="$errors->first('missing_action')"
                    :placeholder="null"
                />
            </div>

            <div class="mt-5 flex flex-wrap gap-5">
                <x-ui.input.checkbox name="use_tls" label="Use LDAPS / TLS" :checked="old('use_tls', $connection['use_tls'])" />
                <x-ui.input.checkbox name="use_starttls" label="Use STARTTLS" :checked="old('use_starttls', $connection['use_starttls'])" />
            </div>
        </x-ui.card>

        <x-ui.card title="Organizational Units" subtitle="Fetch Active Directory OUs, keep the ones that should sync selected, and skip only the branches you do not want.">
            <div class="space-y-5">
                <div class="flex flex-col gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-900">OU discovery</p>
                        <p class="mt-1 text-sm text-gray-500">Searches under the LDAP Base DN above. Newly discovered OUs are included automatically unless unchecked.</p>
                    </div>
                    <button type="button" x-on:click="fetchOus" x-bind:disabled="loading" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60">
                        <span x-show="! loading">Fetch OUs</span>
                        <span x-cloak x-show="loading">Fetching...</span>
                    </button>
                </div>

                <div x-cloak x-show="error" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" x-text="error"></div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="rounded-lg border border-gray-200 bg-white p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Discovered</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900" x-text="ous.length"></p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Selected</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900" x-text="selectedCount()"></p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Skipped</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900" x-text="excludedDns.length"></p>
                    </div>
                </div>

                <div x-show="ous.length === 0" class="rounded-lg border border-dashed border-gray-300 bg-white p-5 text-sm text-gray-600">
                    <p class="font-medium text-gray-900">No OUs fetched yet.</p>
                    <p class="mt-1">Save your LDAP connection details or fetch now using the current form values. Existing skipped OU selections are preserved when you save.</p>
                </div>

                <div x-cloak x-show="ous.length > 0" class="space-y-3">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div class="md:w-80">
                            <label for="ou_search" class="sr-only">Search OUs</label>
                            <input id="ou_search" type="search" x-model="query" placeholder="Search OUs" class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" x-on:click="selectVisible" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">Select visible</button>
                            <button type="button" x-on:click="skipVisible" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">Skip visible</button>
                        </div>
                    </div>

                    <div class="max-h-96 overflow-y-auto rounded-lg border border-gray-200">
                        <template x-for="ou in filteredOus()" :key="ou.dn">
                            <label class="flex cursor-pointer gap-3 border-b border-gray-100 px-4 py-3 last:border-b-0 hover:bg-gray-50">
                                <input type="checkbox" class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" x-bind:checked="isSelected(ou)" x-on:change="toggleOu(ou, $event.target.checked)">
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-medium text-gray-900" x-text="ou.name"></span>
                                    <span class="mt-0.5 block truncate text-xs text-gray-500" x-text="ou.path"></span>
                                    <span class="mt-0.5 block truncate text-xs text-gray-400" x-text="ou.dn"></span>
                                </span>
                            </label>
                        </template>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="Organization Mapping" subtitle="Organizations are created from the selected OUs. Map the Organization fields from AD OU attributes.">
            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                <x-ui.input.text name="organization_unique_attribute" label="Unique Attribute" :value="old('organization_unique_attribute', $organizationSync['unique_attribute'])" placeholder="objectGUID" :error="$errors->first('organization_unique_attribute')" />
                <x-ui.input.text name="organization_match_attribute" label="Organization Match Attribute" :value="old('organization_match_attribute', $organizationSync['match_attribute'])" placeholder="objectGUID" :error="$errors->first('organization_match_attribute')" />
                <x-ui.input.text name="organization_map_code" label="code <-" :value="old('organization_map_code', $organizationSync['map']['code'])" placeholder="ou" :error="$errors->first('organization_map_code')" />
                <x-ui.input.text name="organization_map_name" label="name <-" :value="old('organization_map_name', $organizationSync['map']['name'])" placeholder="ou" :error="$errors->first('organization_map_name')" />
                <x-ui.input.text name="organization_map_description" label="description <-" :value="old('organization_map_description', $organizationSync['map']['description'])" placeholder="description" :error="$errors->first('organization_map_description')" />
                <x-ui.input.text name="organization_map_status" label="status <-" :value="old('organization_map_status', $organizationSync['map']['status'])" placeholder="accountStatus" :error="$errors->first('organization_map_status')" />
            </div>
        </x-ui.card>

        <x-ui.card title="Customer Mapping" subtitle="Map LDAP customer entries into App\Models\Customer.">
            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                <x-ui.input.text name="customer_base_dn" label="Customer Base DN Fallback" :value="old('customer_base_dn', $customerSync['base_dn'])" placeholder="ou=customers,dc=example,dc=com" :error="$errors->first('customer_base_dn')" />
                <x-ui.input.text name="customer_filter" label="Customer Filter" :value="old('customer_filter', $customerSync['filter'])" placeholder="(objectClass=inetOrgPerson)" :error="$errors->first('customer_filter')" />
                <x-ui.input.text name="customer_unique_attribute" label="Unique Attribute" :value="old('customer_unique_attribute', $customerSync['unique_attribute'])" placeholder="uid" :error="$errors->first('customer_unique_attribute')" />
                <x-ui.input.text name="customer_match_attribute" label="Customer Match Attribute" :value="old('customer_match_attribute', $customerSync['match_attribute'])" placeholder="uid" :error="$errors->first('customer_match_attribute')" />
                <x-ui.input.text name="customer_organization_attribute" label="Organization Link Attribute" :value="old('customer_organization_attribute', $customerSync['organization_attribute'])" placeholder="department" :error="$errors->first('customer_organization_attribute')" />
                <x-ui.input.select
                    name="customer_organization_match_field"
                    label="Matches Organization Field"
                    :options="['code' => 'code', 'name' => 'name', 'ldap_guid' => 'ldap_guid']"
                    :value="old('customer_organization_match_field', $customerSync['organization_match_field'])"
                    :error="$errors->first('customer_organization_match_field')"
                    :placeholder="null"
                />
                <x-ui.input.text name="customer_map_customer_code" label="customer_code <-" :value="old('customer_map_customer_code', $customerSync['map']['customer_code'])" placeholder="uid" :error="$errors->first('customer_map_customer_code')" />
                <x-ui.input.text name="customer_map_name" label="name <-" :value="old('customer_map_name', $customerSync['map']['name'])" placeholder="cn" :error="$errors->first('customer_map_name')" />
                <x-ui.input.text name="customer_map_email" label="email <-" :value="old('customer_map_email', $customerSync['map']['email'])" placeholder="mail" :error="$errors->first('customer_map_email')" />
                <x-ui.input.text name="customer_map_phone" label="phone <-" :value="old('customer_map_phone', $customerSync['map']['phone'])" placeholder="telephoneNumber" :error="$errors->first('customer_map_phone')" />
                <x-ui.input.text name="customer_map_mobile" label="mobile <-" :value="old('customer_map_mobile', $customerSync['map']['mobile'])" placeholder="mobile" :error="$errors->first('customer_map_mobile')" />
                <x-ui.input.text name="customer_map_status" label="status <-" :value="old('customer_map_status', $customerSync['map']['status'])" placeholder="accountStatus" :error="$errors->first('customer_map_status')" />
            </div>
        </x-ui.card>

        <x-ui.card title="Subscription Mapping" subtitle="Optional mapping for App\Models\Subscription. Leave the base DN or filter blank to sync customers only.">
            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                <x-ui.input.text name="subscription_base_dn" label="Subscription Base DN" :value="old('subscription_base_dn', $subscriptionSync['base_dn'])" placeholder="ou=subscriptions,dc=example,dc=com" :error="$errors->first('subscription_base_dn')" />
                <x-ui.input.text name="subscription_filter" label="Subscription Filter" :value="old('subscription_filter', $subscriptionSync['filter'])" placeholder="(objectClass=*)" :error="$errors->first('subscription_filter')" />
                <x-ui.input.text name="subscription_unique_attribute" label="Unique Attribute" :value="old('subscription_unique_attribute', $subscriptionSync['unique_attribute'])" placeholder="uid" :error="$errors->first('subscription_unique_attribute')" />
                <x-ui.input.text name="subscription_customer_attribute" label="Customer Link Attribute" :value="old('subscription_customer_attribute', $subscriptionSync['customer_attribute'])" placeholder="customerUid" :error="$errors->first('subscription_customer_attribute')" />
                <x-ui.input.select
                    name="subscription_customer_match_field"
                    label="Matches Customer Field"
                    :options="['customer_code' => 'customer_code', 'email' => 'email', 'ldap_guid' => 'ldap_guid']"
                    :value="old('subscription_customer_match_field', $subscriptionSync['customer_match_field'])"
                    :error="$errors->first('subscription_customer_match_field')"
                    :placeholder="null"
                />
                <x-ui.input.text name="subscription_map_subscription_code" label="subscription_code <-" :value="old('subscription_map_subscription_code', $subscriptionSync['map']['subscription_code'])" placeholder="uid" :error="$errors->first('subscription_map_subscription_code')" />
                <x-ui.input.text name="subscription_map_pppoe_username" label="pppoe_username <-" :value="old('subscription_map_pppoe_username', $subscriptionSync['map']['pppoe_username'])" placeholder="uid" :error="$errors->first('subscription_map_pppoe_username')" />
                <x-ui.input.text name="subscription_map_pppoe_password" label="pppoe_password <-" :value="old('subscription_map_pppoe_password', $subscriptionSync['map']['pppoe_password'])" placeholder="userPassword" :error="$errors->first('subscription_map_pppoe_password')" />
                <x-ui.input.text name="subscription_map_ip_address" label="ip_address <-" :value="old('subscription_map_ip_address', $subscriptionSync['map']['ip_address'])" placeholder="framedIPAddress" :error="$errors->first('subscription_map_ip_address')" />
                <x-ui.input.text name="subscription_map_mac_address" label="mac_address <-" :value="old('subscription_map_mac_address', $subscriptionSync['map']['mac_address'])" placeholder="macAddress" :error="$errors->first('subscription_map_mac_address')" />
                <x-ui.input.text name="subscription_map_status" label="status <-" :value="old('subscription_map_status', $subscriptionSync['map']['status'])" placeholder="serviceStatus" :error="$errors->first('subscription_map_status')" />
            </div>
        </x-ui.card>

        <div class="flex justify-end">
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Save LDAP Settings
            </button>
        </div>
    </form>

    <x-ui.card title="Sync Operations" subtitle="Test the connection, preview changes, or run a tenant sync now.">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Last successful sync</p>
                <p class="mt-1 text-sm font-medium text-gray-900">{{ $syncStatus['last_success_at'] ?? 'Never' }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Last run</p>
                <p class="mt-1 text-sm font-medium text-gray-900">{{ $syncStatus['last_run_at'] ?? 'Never' }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Last error</p>
                <p class="mt-1 text-sm font-medium text-gray-900">{{ $syncStatus['last_error'] ?? 'None' }}</p>
            </div>
        </div>

        <div class="mt-5 flex flex-wrap gap-3">
            <form action="{{ route('settings.test.ldap') }}" method="POST">
                @csrf
                <button type="submit" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                    Test Connection
                </button>
            </form>
            <form action="{{ route('settings.preview.ldap') }}" method="POST">
                @csrf
                <button type="submit" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                    Preview Sync
                </button>
            </form>
            <form action="{{ route('settings.sync.ldap') }}" method="POST">
                @csrf
                <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-emerald-700">
                    Run Sync Now
                </button>
            </form>
        </div>
    </x-ui.card>

    <x-ui.card title="Mapping Guide" subtitle="Use LDAP attribute names on the right side of each mapping field.">
        <div class="grid grid-cols-1 gap-4 text-sm text-gray-600 md:grid-cols-2">
            <div>
                <p class="font-medium text-gray-900">Organization linking</p>
                <p class="mt-1">For Active Directory, map organizations from OUs/groups first, then set Customer Organization Link Attribute to a customer attribute such as department, company, memberOf, or extensionAttribute.</p>
            </div>
            <div>
                <p class="font-medium text-gray-900">Customer minimums</p>
                <p class="mt-1">Map a stable unique attribute to both Unique Attribute and customer_code. Common choices are uid, employeeNumber, customerNumber, or sAMAccountName.</p>
            </div>
            <div>
                <p class="font-medium text-gray-900">Subscription linking</p>
                <p class="mt-1">Set Customer Link Attribute to the LDAP attribute on the subscription entry that contains the customer_code, email, or ldap_guid of the customer.</p>
            </div>
        </div>
    </x-ui.card>
</div>

@once
    <style>
        [x-cloak] { display: none !important; }
    </style>
    <script>
        function ldapOuPicker(config) {
            return {
                discoverUrl: config.discoverUrl,
                csrfToken: config.csrfToken,
                excludedDns: config.excludedDns || [],
                ous: [],
                query: '',
                loading: false,
                error: null,

                async fetchOus() {
                    this.loading = true;
                    this.error = null;

                    try {
                        const formData = new FormData(this.$root);

                        const response = await fetch(this.discoverUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                            },
                            body: formData,
                        });

                        const data = await response.json();

                        if (! response.ok) {
                            this.error = data.message || 'Unable to fetch organizational units.';
                            return;
                        }

                        this.ous = data.organizational_units || [];
                        this.syncExcludedFromFetchedOus();
                    } catch (error) {
                        this.error = 'Unable to fetch organizational units.';
                    } finally {
                        this.loading = false;
                    }
                },

                filteredOus() {
                    const search = this.query.trim().toLowerCase();

                    if (! search) {
                        return this.ous;
                    }

                    return this.ous.filter((ou) => {
                        return [ou.name, ou.path, ou.dn].some((value) => String(value || '').toLowerCase().includes(search));
                    });
                },

                selectedCount() {
                    if (this.ous.length === 0) {
                        return 'All new';
                    }

                    return this.ous.filter((ou) => this.isSelected(ou)).length;
                },

                isSelected(ou) {
                    return ! this.excludedDns.includes(ou.dn);
                },

                toggleOu(ou, selected) {
                    if (selected) {
                        this.excludedDns = this.excludedDns.filter((dn) => dn !== ou.dn);
                        return;
                    }

                    if (! this.excludedDns.includes(ou.dn)) {
                        this.excludedDns = [...this.excludedDns, ou.dn];
                    }
                },

                selectVisible() {
                    const visibleDns = this.filteredOus().map((ou) => ou.dn);
                    this.excludedDns = this.excludedDns.filter((dn) => ! visibleDns.includes(dn));
                },

                skipVisible() {
                    const skipped = this.filteredOus().map((ou) => ou.dn);
                    this.excludedDns = Array.from(new Set([...this.excludedDns, ...skipped]));
                },

                syncExcludedFromFetchedOus() {
                    const fetchedDns = this.ous.map((ou) => ou.dn);
                    const staleExcludedDns = this.excludedDns.filter((dn) => ! fetchedDns.includes(dn));
                    const fetchedExcludedDns = this.ous.filter((ou) => ! ou.selected).map((ou) => ou.dn);

                    this.excludedDns = Array.from(new Set([...staleExcludedDns, ...fetchedExcludedDns]));
                },
            };
        }
    </script>
@endonce
