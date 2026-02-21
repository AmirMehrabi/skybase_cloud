<form action="{{ route('settings.update.general') }}" method="POST">
    @csrf
    @method('PUT')

    <!-- Company Information Section -->
    <x-settings-section title="Company Information" description="Basic information about your company">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Company Name -->
            <div class="col-span-2 md:col-span-1">
                <x-input.text
                    id="company_name"
                    name="company_name"
                    label="Company Name"
                    :value="old('company_name', $tenant->company_name)"
                    :required="true"
                />
            </div>

            <!-- Tagline -->
            <div class="col-span-2 md:col-span-1">
                <x-input.text
                    id="tagline"
                    name="tagline"
                    label="Tagline / Slogan"
                    :value="old('tagline', $tenant->tagline)"
                />
            </div>

            <!-- Business License -->
            <div class="col-span-2 md:col-span-1">
                <x-input.text
                    id="business_license"
                    name="business_license"
                    label="Business License / Registration Number"
                    :value="old('business_license', $tenant->business_license)"
                />
            </div>

            <!-- Tax ID -->
            <div class="col-span-2 md:col-span-1">
                <x-input.text
                    id="tax_id"
                    name="tax_id"
                    label="Tax ID / VAT Number"
                    :value="old('tax_id', $tenant->tax_id)"
                />
            </div>

            <!-- Website URL -->
            <div class="col-span-2 md:col-span-1">
                <x-input.text
                    id="website_url"
                    name="website_url"
                    label="Website URL"
                    :value="old('website_url', $tenant->website_url)"
                    placeholder="https://example.com"
                />
            </div>

            <!-- Support Phone -->
            <div class="col-span-2 md:col-span-1">
                <x-input.text
                    id="support_phone"
                    name="support_phone"
                    label="Support Phone"
                    :value="old('support_phone', $tenant->support_phone)"
                    placeholder="+1 234 567 8900"
                />
            </div>

            <!-- Support Email -->
            <div class="col-span-2 md:col-span-1">
                <x-input.text
                    id="support_email"
                    name="support_email"
                    label="Support Email"
                    :value="old('support_email', $tenant->email)"
                    :required="true"
                />
            </div>

            <!-- Address -->
            <div class="col-span-2">
                <x-input.textarea
                    id="address"
                    name="address"
                    label="Physical Address"
                    :value="old('address', $tenant->address)"
                    :rows="2"
                />
            </div>

            <!-- City -->
            <div class="col-span-2 md:col-span-1">
                <x-input.text
                    id="city"
                    name="city"
                    label="City"
                    :value="old('city', $tenant->city)"
                />
            </div>

            <!-- State -->
            <div class="col-span-2 md:col-span-1">
                <x-input.text
                    id="state"
                    name="state"
                    label="State / Province"
                    :value="old('state', $tenant->state)"
                />
            </div>

            <!-- ZIP -->
            <div class="col-span-2 md:col-span-1">
                <x-input.text
                    id="zip"
                    name="zip"
                    label="ZIP / Postal Code"
                    :value="old('zip', $tenant->zip)"
                />
            </div>

            <!-- Country -->
            <div class="col-span-2 md:col-span-1">
                <x-input.text
                    id="country"
                    name="country"
                    label="Country"
                    :value="old('country', $tenant->country)"
                />
            </div>
        </div>
    </x-settings-section>

    <!-- Localization Section -->
    <x-settings-section title="Localization" description="Configure date, time, and number formatting">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Timezone -->
            <div class="col-span-2 md:col-span-1">
                <x-input.select
                    id="timezone"
                    name="timezone"
                    label="Timezone"
                    :options="$timezones"
                    :value="old('timezone', $tenant->timezone)"
                    placeholder="Select Timezone"
                    :required="true"
                />
            </div>

            <!-- Currency -->
            <div class="col-span-2 md:col-span-1">
                <x-input.select
                    id="currency"
                    name="currency"
                    label="Currency"
                    :options="$currencies"
                    :value="old('currency', $tenant->currency)"
                    placeholder="Select Currency"
                    :required="true"
                />
            </div>

            <!-- Date Format -->
            <div class="col-span-2 md:col-span-1">
                <x-input.select
                    id="date_format"
                    name="date_format"
                    label="Date Format"
                    :options="['m/d/Y' => 'MM/DD/YYYY', 'd/m/Y' => 'DD/MM/YYYY', 'Y-m-d' => 'YYYY-MM-DD', 'd.m.Y' => 'DD.MM.YYYY', 'F j, Y' => 'January 1, 2025', 'j F Y' => '1 January 2025']"
                    :value="old('date_format', $tenant->date_format)"
                    :required="true"
                />
            </div>

            <!-- Time Format -->
            <div class="col-span-2 md:col-span-1">
                <x.input.select
                    id="time_format"
                    name="time_format"
                    label="Time Format"
                    :options="['12h' => '12 Hour (AM/PM)', '24h' => '24 Hour']"
                    :value="old('time_format', $tenant->time_format)"
                    :required="true"
                />
            </div>

            <!-- First Day of Week -->
            <div class="col-span-2 md:col-span-1">
                <x.input.select
                    id="first_day_of_week"
                    name="first_day_of_week"
                    label="First Day of Week"
                    :options="['sunday' => 'Sunday', 'monday' => 'Monday']"
                    :value="old('first_day_of_week', $tenant->first_day_of_week)"
                    :required="true"
                />
            </div>

            <!-- Currency Symbol Position -->
            <div class="col-span-2 md:col-span-1">
                <x.input.select
                    id="currency_symbol_position"
                    name="currency_symbol_position"
                    label="Currency Symbol Position"
                    :options="['before' => '$100.00', 'after' => '100.00$']"
                    :value="old('currency_symbol_position', $tenant->currency_symbol_position)"
                    :required="true"
                />
            </div>

            <!-- Thousands Separator -->
            <div class="col-span-2 md:col-span-1">
                <x.input.select
                    id="thousands_separator"
                    name="thousands_separator"
                    label="Thousands Separator"
                    :options="[',' => 'Comma (1,000)', '.' => 'Period (1.000)', ' ' => 'Space (1 000)', '_' => 'Underscore (1_000)']"
                    :value="old('thousands_separator', $tenant->thousands_separator)"
                    :required="true"
                />
            </div>

            <!-- Decimal Separator -->
            <div class="col-span-2 md:col-span-1">
                <x.input.select
                    id="decimal_separator"
                    name="decimal_separator"
                    label="Decimal Separator"
                    :options="['.' => 'Period (0.99)', ',' => 'Comma (0,99)']"
                    :value="old('decimal_separator', $tenant->decimal_separator)"
                    :required="true"
                />
            </div>

            <!-- Locale -->
            <div class="col-span-2 md:col-span-1">
                <x.input.select
                    id="locale"
                    name="locale"
                    label="Language / Locale"
                    :options="$locales"
                    :value="old('locale', $tenant->locale)"
                    placeholder="Select Language"
                    :required="true"
                />
            </div>
        </div>
    </x-settings-section>

    <!-- System Section -->
    <x-settings-section title="System" description="System-wide settings">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Maintenance Mode -->
            <div class="col-span-2">
                <x-input.checkbox
                    id="maintenance_mode"
                    name="maintenance_mode"
                    label="Maintenance Mode"
                    :checked="old('maintenance_mode', $tenant->maintenance_mode)"
                    help="When enabled, only administrators can access the system. Users will see a maintenance message."
                />
            </div>

            <!-- Custom Domain -->
            <div class="col-span-2 md:col-span-1">
                <x.input.text
                    id="custom_domain"
                    name="custom_domain"
                    label="Custom Domain"
                    :value="old('custom_domain', $tenant->custom_domain)"
                    placeholder="app.yourcompany.com"
                />
                <p class="mt-1 text-sm text-gray-500">Optional: Point your custom domain to this tenant.</p>
            </div>
        </div>
    </x-settings-section>

    <!-- Save Button -->
    <div class="flex justify-end">
        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            Save General Settings
        </button>
    </div>
</form>
