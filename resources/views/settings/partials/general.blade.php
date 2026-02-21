<form action="{{ route('settings.update.general') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <!-- Company Information Section -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Company Information</h3>
            <p class="mt-1 text-sm text-gray-500">Basic information about your company</p>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Company Name -->
                <div class="col-span-2 md:col-span-1">
                    <label for="company_name" class="block text-sm font-medium text-gray-700">
                        Company Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="company_name" id="company_name" value="{{ old('company_name', $tenant->company_name) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('company_name') border-red-500 @enderror"
                           required>
                    @error('company_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tagline -->
                <div class="col-span-2 md:col-span-1">
                    <label for="tagline" class="block text-sm font-medium text-gray-700">Tagline / Slogan</label>
                    <input type="text" name="tagline" id="tagline" value="{{ old('tagline', $tenant->tagline) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('tagline') border-red-500 @enderror">
                    @error('tagline')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Business License -->
                <div class="col-span-2 md:col-span-1">
                    <label for="business_license" class="block text-sm font-medium text-gray-700">Business License / Registration Number</label>
                    <input type="text" name="business_license" id="business_license" value="{{ old('business_license', $tenant->business_license) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('business_license') border-red-500 @enderror">
                    @error('business_license')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tax ID -->
                <div class="col-span-2 md:col-span-1">
                    <label for="tax_id" class="block text-sm font-medium text-gray-700">Tax ID / VAT Number</label>
                    <input type="text" name="tax_id" id="tax_id" value="{{ old('tax_id', $tenant->tax_id) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('tax_id') border-red-500 @enderror">
                    @error('tax_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Website URL -->
                <div class="col-span-2 md:col-span-1">
                    <label for="website_url" class="block text-sm font-medium text-gray-700">Website URL</label>
                    <input type="url" name="website_url" id="website_url" value="{{ old('website_url', $tenant->website_url) }}"
                           placeholder="https://example.com"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('website_url') border-red-500 @enderror">
                    @error('website_url')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Support Phone -->
                <div class="col-span-2 md:col-span-1">
                    <label for="support_phone" class="block text-sm font-medium text-gray-700">Support Phone</label>
                    <input type="tel" name="support_phone" id="support_phone" value="{{ old('support_phone', $tenant->support_phone) }}"
                           placeholder="+1 234 567 8900"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('support_phone') border-red-500 @enderror">
                    @error('support_phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Support Email -->
                <div class="col-span-2 md:col-span-1">
                    <label for="support_email" class="block text-sm font-medium text-gray-700">
                        Support Email <span class="text-red-500">*</span>
                    </label>
                    <input type="email" name="support_email" id="support_email" value="{{ old('support_email', $tenant->email) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('support_email') border-red-500 @enderror"
                           required>
                    @error('support_email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Address -->
                <div class="col-span-2">
                    <label for="address" class="block text-sm font-medium text-gray-700">Physical Address</label>
                    <textarea name="address" id="address" rows="2"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('address') border-red-500 @enderror">{{ old('address', $tenant->address) }}</textarea>
                    @error('address')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- City -->
                <div class="col-span-2 md:col-span-1">
                    <label for="city" class="block text-sm font-medium text-gray-700">City</label>
                    <input type="text" name="city" id="city" value="{{ old('city', $tenant->city) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('city') border-red-500 @enderror">
                    @error('city')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- State -->
                <div class="col-span-2 md:col-span-1">
                    <label for="state" class="block text-sm font-medium text-gray-700">State / Province</label>
                    <input type="text" name="state" id="state" value="{{ old('state', $tenant->state) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('state') border-red-500 @enderror">
                    @error('state')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Zip -->
                <div class="col-span-2 md:col-span-1">
                    <label for="zip" class="block text-sm font-medium text-gray-700">ZIP / Postal Code</label>
                    <input type="text" name="zip" id="zip" value="{{ old('zip', $tenant->zip) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('zip') border-red-500 @enderror">
                    @error('zip')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Country -->
                <div class="col-span-2 md:col-span-1">
                    <label for="country" class="block text-sm font-medium text-gray-700">Country</label>
                    <input type="text" name="country" id="country" value="{{ old('country', $tenant->country) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('country') border-red-500 @enderror">
                    @error('country')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Localization Section -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Localization</h3>
            <p class="mt-1 text-sm text-gray-500">Configure date, time, and number formatting</p>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Timezone -->
                <div class="col-span-2 md:col-span-1">
                    <label for="timezone" class="block text-sm font-medium text-gray-700">
                        Timezone <span class="text-red-500">*</span>
                    </label>
                    <select name="timezone" id="timezone"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('timezone') border-red-500 @enderror" required>
                        <option value="">Select Timezone</option>
                        @foreach($timezones as $timezone)
                            <option value="{{ $timezone }}" {{ old('timezone', $tenant->timezone) === $timezone ? 'selected' : '' }}>
                                {{ $timezone }}
                            </option>
                        @endforeach
                    </select>
                    @error('timezone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Currency -->
                <div class="col-span-2 md:col-span-1">
                    <label for="currency" class="block text-sm font-medium text-gray-700">
                        Currency <span class="text-red-500">*</span>
                    </label>
                    <select name="currency" id="currency"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('currency') border-red-500 @enderror" required>
                        <option value="">Select Currency</option>
                        @foreach($currencies as $code => $name)
                            <option value="{{ $code }}" {{ old('currency', $tenant->currency) === $code ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                    @error('currency')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Date Format -->
                <div class="col-span-2 md:col-span-1">
                    <label for="date_format" class="block text-sm font-medium text-gray-700">
                        Date Format <span class="text-red-500">*</span>
                    </label>
                    <select name="date_format" id="date_format"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('date_format') border-red-500 @enderror" required>
                        <option value="m/d/Y" {{ old('date_format', $tenant->date_format) === 'm/d/Y' ? 'selected' : '' }}>MM/DD/YYYY</option>
                        <option value="d/m/Y" {{ old('date_format', $tenant->date_format) === 'd/m/Y' ? 'selected' : '' }}>DD/MM/YYYY</option>
                        <option value="Y-m-d" {{ old('date_format', $tenant->date_format) === 'Y-m-d' ? 'selected' : '' }}>YYYY-MM-DD</option>
                        <option value="d.m.Y" {{ old('date_format', $tenant->date_format) === 'd.m.Y' ? 'selected' : '' }}>DD.MM.YYYY</option>
                        <option value="F j, Y" {{ old('date_format', $tenant->date_format) === 'F j, Y' ? 'selected' : '' }}>January 1, 2025</option>
                        <option value="j F Y" {{ old('date_format', $tenant->date_format) === 'j F Y' ? 'selected' : '' }}>1 January 2025</option>
                    </select>
                    @error('date_format')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Time Format -->
                <div class="col-span-2 md:col-span-1">
                    <label for="time_format" class="block text-sm font-medium text-gray-700">
                        Time Format <span class="text-red-500">*</span>
                    </label>
                    <select name="time_format" id="time_format"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('time_format') border-red-500 @enderror" required>
                        <option value="12h" {{ old('time_format', $tenant->time_format) === '12h' ? 'selected' : '' }}>12 Hour (AM/PM)</option>
                        <option value="24h" {{ old('time_format', $tenant->time_format) === '24h' ? 'selected' : '' }}>24 Hour</option>
                    </select>
                    @error('time_format')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- First Day of Week -->
                <div class="col-span-2 md:col-span-1">
                    <label for="first_day_of_week" class="block text-sm font-medium text-gray-700">
                        First Day of Week <span class="text-red-500">*</span>
                    </label>
                    <select name="first_day_of_week" id="first_day_of_week"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('first_day_of_week') border-red-500 @enderror" required>
                        <option value="sunday" {{ old('first_day_of_week', $tenant->first_day_of_week) === 'sunday' ? 'selected' : '' }}>Sunday</option>
                        <option value="monday" {{ old('first_day_of_week', $tenant->first_day_of_week) === 'monday' ? 'selected' : '' }}>Monday</option>
                    </select>
                    @error('first_day_of_week')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Currency Symbol Position -->
                <div class="col-span-2 md:col-span-1">
                    <label for="currency_symbol_position" class="block text-sm font-medium text-gray-700">
                        Currency Symbol Position <span class="text-red-500">*</span>
                    </label>
                    <select name="currency_symbol_position" id="currency_symbol_position"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('currency_symbol_position') border-red-500 @enderror" required>
                        <option value="before" {{ old('currency_symbol_position', $tenant->currency_symbol_position) === 'before' ? 'selected' : '' }}>$100.00</option>
                        <option value="after" {{ old('currency_symbol_position', $tenant->currency_symbol_position) === 'after' ? 'selected' : '' }}>100.00$</option>
                    </select>
                    @error('currency_symbol_position')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Thousands Separator -->
                <div class="col-span-2 md:col-span-1">
                    <label for="thousands_separator" class="block text-sm font-medium text-gray-700">
                        Thousands Separator <span class="text-red-500">*</span>
                    </label>
                    <select name="thousands_separator" id="thousands_separator"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('thousands_separator') border-red-500 @enderror" required>
                        <option value="," {{ old('thousands_separator', $tenant->thousands_separator) === ',' ? 'selected' : '' }}>Comma (1,000)</option>
                        <option value="." {{ old('thousands_separator', $tenant->thousands_separator) === '.' ? 'selected' : '' }}>Period (1.000)</option>
                        <option value=" " {{ old('thousands_separator', $tenant->thousands_separator) === ' ' ? 'selected' : '' }}>Space (1 000)</option>
                        <option value="_" {{ old('thousands_separator', $tenant->thousands_separator) === '_' ? 'selected' : '' }}>Underscore (1_000)</option>
                    </select>
                    @error('thousands_separator')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Decimal Separator -->
                <div class="col-span-2 md:col-span-1">
                    <label for="decimal_separator" class="block text-sm font-medium text-gray-700">
                        Decimal Separator <span class="text-red-500">*</span>
                    </label>
                    <select name="decimal_separator" id="decimal_separator"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('decimal_separator') border-red-500 @enderror" required>
                        <option value="." {{ old('decimal_separator', $tenant->decimal_separator) === '.' ? 'selected' : '' }}>Period (0.99)</option>
                        <option value="," {{ old('decimal_separator', $tenant->decimal_separator) === ',' ? 'selected' : '' }}>Comma (0,99)</option>
                    </select>
                    @error('decimal_separator')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Locale -->
                <div class="col-span-2 md:col-span-1">
                    <label for="locale" class="block text-sm font-medium text-gray-700">
                        Language / Locale <span class="text-red-500">*</span>
                    </label>
                    <select name="locale" id="locale"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('locale') border-red-500 @enderror" required>
                        <option value="">Select Language</option>
                        @foreach($locales as $code => $name)
                            <option value="{{ $code }}" {{ old('locale', $tenant->locale) === $code ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                    @error('locale')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <!-- System Section -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">System</h3>
            <p class="mt-1 text-sm text-gray-500">System-wide settings</p>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Maintenance Mode -->
                <div class="col-span-2">
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="maintenance_mode" id="maintenance_mode" value="1"
                                   {{ old('maintenance_mode', $tenant->maintenance_mode) ? 'checked' : '' }}
                                   class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="maintenance_mode" class="font-medium text-gray-700">Maintenance Mode</label>
                            <p class="text-gray-500">When enabled, only administrators can access the system. Users will see a maintenance message.</p>
                        </div>
                    </div>
                </div>

                <!-- Custom Domain -->
                <div class="col-span-2 md:col-span-1">
                    <label for="custom_domain" class="block text-sm font-medium text-gray-700">Custom Domain</label>
                    <input type="text" name="custom_domain" id="custom_domain" value="{{ old('custom_domain', $tenant->custom_domain) }}"
                           placeholder="app.yourcompany.com"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('custom_domain') border-red-500 @enderror">
                    <p class="mt-1 text-sm text-gray-500">Optional: Point your custom domain to this tenant.</p>
                    @error('custom_domain')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>
    </div>

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
