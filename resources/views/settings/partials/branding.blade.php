<form action="{{ route('settings.update.branding') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <!-- Theme Section -->
    <x-settings-section title="Theme Colors" description="Customize your application's color scheme">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Primary Color -->
            <div>
                <label for="primary_color" class="block text-sm font-medium text-gray-700">
                    Primary Color <span class="text-red-500">*</span>
                </label>
                <div class="mt-1 flex items-center gap-3">
                    <input type="color" name="primary_color" id="primary_color"
                           value="{{ old('primary_color', $tenant->primary_color) }}"
                           class="h-10 w-20 rounded border border-gray-300 cursor-pointer">
                    <input type="text" value="{{ old('primary_color', $tenant->primary_color) }}"
                           class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                           readonly>
                </div>
                @error('primary_color')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Secondary Color -->
            <div>
                <label for="secondary_color" class="block text-sm font-medium text-gray-700">
                    Secondary Color <span class="text-red-500">*</span>
                </label>
                <div class="mt-1 flex items-center gap-3">
                    <input type="color" name="secondary_color" id="secondary_color"
                           value="{{ old('secondary_color', $tenant->secondary_color) }}"
                           class="h-10 w-20 rounded border border-gray-300 cursor-pointer">
                    <input type="text" value="{{ old('secondary_color', $tenant->secondary_color) }}"
                           class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                           readonly>
                </div>
                @error('secondary_color')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Accent Color -->
            <div>
                <label for="accent_color" class="block text-sm font-medium text-gray-700">
                    Accent Color <span class="text-red-500">*</span>
                </label>
                <div class="mt-1 flex items-center gap-3">
                    <input type="color" name="accent_color" id="accent_color"
                           value="{{ old('accent_color', $tenant->accent_color) }}"
                           class="h-10 w-20 rounded border border-gray-300 cursor-pointer">
                    <input type="text" value="{{ old('accent_color', $tenant->accent_color) }}"
                           class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                           readonly>
                </div>
                @error('accent_color')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Dark Mode -->
        <div class="mt-6">
            <x.input.checkbox
                id="dark_mode_enabled"
                name="dark_mode_enabled"
                label="Enable Dark Mode"
                :checked="old('dark_mode_enabled', $tenant->dark_mode_enabled)"
                help="Allow users to switch to dark mode theme."
            />
        </div>

        <!-- Custom CSS -->
        <div class="mt-6">
            <x.input.textarea
                id="custom_css"
                name="custom_css"
                label="Custom CSS"
                :value="old('custom_css', $tenant->custom_css)"
                :rows="8"
                placeholder="/* Custom CSS */&#10;.custom-class {&#10;  color: #3b82f6;&#10;}"
            />
            <p class="mt-1 text-xs text-gray-500">Add custom CSS to further customize the appearance. Use with caution.</p>
        </div>
    </x-settings-section>

    <!-- Logo & Assets Section -->
    <x-settings-section title="Logo & Assets" description="Upload your company logos and images">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Company Logo (Light) -->
            <div>
                <label for="company_logo" class="block text-sm font-medium text-gray-700">Company Logo (Light Theme)</label>
                <p class="mt-1 text-xs text-gray-500">Recommended: PNG or SVG, max 2MB</p>
                @if($tenant->company_logo)
                    <div class="mt-2 relative">
                        <img src="{{ Storage::url($tenant->company_logo) }}" alt="Company Logo" class="h-20 w-auto object-contain border rounded p-2 bg-gray-50">
                        <a href="{{ route('settings.delete.asset', ['asset' => 'company_logo']) }}"
                           class="mt-2 inline-flex items-center text-sm text-red-600 hover:text-red-800"
                           onclick="return confirm('Are you sure you want to delete this logo?')">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Delete
                        </a>
                    </div>
                @endif
                <input type="file" name="company_logo" id="company_logo" accept="image/png,image/jpeg,image/jpeg,image/svg+xml,image/webp"
                       class="mt-2 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                @error('company_logo')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Company Logo (Dark) -->
            <div>
                <label for="company_logo_dark" class="block text-sm font-medium text-gray-700">Company Logo (Dark Theme)</label>
                <p class="mt-1 text-xs text-gray-500">Recommended: PNG or SVG with light colors, max 2MB</p>
                @if($tenant->company_logo_dark)
                    <div class="mt-2 relative">
                        <img src="{{ Storage::url($tenant->company_logo_dark) }}" alt="Company Logo Dark" class="h-20 w-auto object-contain border rounded p-2 bg-gray-800">
                        <a href="{{ route('settings.delete.asset', ['asset' => 'company_logo_dark']) }}"
                           class="mt-2 inline-flex items-center text-sm text-red-600 hover:text-red-800"
                           onclick="return confirm('Are you sure you want to delete this logo?')">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Delete
                        </a>
                    </div>
                @endif
                <input type="file" name="company_logo_dark" id="company_logo_dark" accept="image/png,image/jpeg,image/jpeg,image/svg+xml,image/webp"
                       class="mt-2 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                @error('company_logo_dark')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Favicon -->
            <div>
                <label for="favicon" class="block text-sm font-medium text-gray-700">Favicon</label>
                <p class="mt-1 text-xs text-gray-500">Recommended: PNG or ICO, 32x32 or 64x64, max 1MB</p>
                @if($tenant->favicon)
                    <div class="mt-2 relative">
                        <img src="{{ Storage::url($tenant->favicon) }}" alt="Favicon" class="h-8 w-8 object-contain border rounded p-1 bg-gray-50">
                        <a href="{{ route('settings.delete.asset', ['asset' => 'favicon']) }}"
                           class="mt-2 inline-flex items-center text-sm text-red-600 hover:text-red-800"
                           onclick="return confirm('Are you sure you want to delete this favicon?')">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Delete
                        </a>
                    </div>
                @endif
                <input type="file" name="favicon" id="favicon" accept="image/png,image/vnd.microsoft.icon,image/svg+xml"
                       class="mt-2 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                @error('favicon')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Login Logo -->
            <div>
                <label for="login_logo" class="block text-sm font-medium text-gray-700">Login Page Logo</label>
                <p class="mt-1 text-xs text-gray-500">Recommended: PNG or SVG, max 2MB</p>
                @if($tenant->login_logo)
                    <div class="mt-2 relative">
                        <img src="{{ Storage::url($tenant->login_logo) }}" alt="Login Logo" class="h-20 w-auto object-contain border rounded p-2 bg-gray-50">
                        <a href="{{ route('settings.delete.asset', ['asset' => 'login_logo']) }}"
                           class="mt-2 inline-flex items-center text-sm text-red-600 hover:text-red-800"
                           onclick="return confirm('Are you sure you want to delete this logo?')">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Delete
                        </a>
                    </div>
                @endif
                <input type="file" name="login_logo" id="login_logo" accept="image/png,image/jpeg,image/jpeg,image/svg+xml,image/webp"
                       class="mt-2 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                @error('login_logo')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email Header Logo -->
            <div>
                <label for="email_header_logo" class="block text-sm font-medium text-gray-700">Email Header Logo</label>
                <p class="mt-1 text-xs text-gray-500">Recommended: PNG or SVG, max 2MB</p>
                @if($tenant->email_header_logo)
                    <div class="mt-2 relative">
                        <img src="{{ Storage::url($tenant->email_header_logo) }}" alt="Email Header Logo" class="h-20 w-auto object-contain border rounded p-2 bg-gray-50">
                        <a href="{{ route('settings.delete.asset', ['asset' => 'email_header_logo']) }}"
                           class="mt-2 inline-flex items-center text-sm text-red-600 hover:text-red-800"
                           onclick="return confirm('Are you sure you want to delete this logo?')">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Delete
                        </a>
                    </div>
                @endif
                <input type="file" name="email_header_logo" id="email_header_logo" accept="image/png,image/jpeg,image/jpeg,image/svg+xml,image/webp"
                       class="mt-2 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                @error('email_header_logo')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email Footer Logo -->
            <div>
                <label for="email_footer_logo" class="block text-sm font-medium text-gray-700">Email Footer Logo</label>
                <p class="mt-1 text-xs text-gray-500">Recommended: PNG or SVG, max 2MB</p>
                @if($tenant->email_footer_logo)
                    <div class="mt-2 relative">
                        <img src="{{ Storage::url($tenant->email_footer_logo) }}" alt="Email Footer Logo" class="h-20 w-auto object-contain border rounded p-2 bg-gray-50">
                        <a href="{{ route('settings.delete.asset', ['asset' => 'email_footer_logo']) }}"
                           class="mt-2 inline-flex items-center text-sm text-red-600 hover:text-red-800"
                           onclick="return confirm('Are you sure you want to delete this logo?')">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Delete
                        </a>
                    </div>
                @endif
                <input type="file" name="email_footer_logo" id="email_footer_logo" accept="image/png,image/jpeg,image/jpeg,image/svg+xml,image/webp"
                       class="mt-2 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                @error('email_footer_logo')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Invoice Logo -->
            <div>
                <label for="invoice_logo" class="block text-sm font-medium text-gray-700">Invoice Logo</label>
                <p class="mt-1 text-xs text-gray-500">Recommended: High-res PNG or SVG, max 2MB</p>
                @if($tenant->invoice_logo)
                    <div class="mt-2 relative">
                        <img src="{{ Storage::url($tenant->invoice_logo) }}" alt="Invoice Logo" class="h-20 w-auto object-contain border rounded p-2 bg-gray-50">
                        <a href="{{ route('settings.delete.asset', ['asset' => 'invoice_logo']) }}"
                           class="mt-2 inline-flex items-center text-sm text-red-600 hover:text-red-800"
                           onclick="return confirm('Are you sure you want to delete this logo?')">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Delete
                        </a>
                    </div>
                @endif
                <input type="file" name="invoice_logo" id="invoice_logo" accept="image/png,image/jpeg,image/jpeg,image/svg+xml,image/webp"
                       class="mt-2 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                @error('invoice_logo')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Login Background -->
            <div class="col-span-2">
                <label for="login_background" class="block text-sm font-medium text-gray-700">Login Page Background Image</label>
                <p class="mt-1 text-xs text-gray-500">Recommended: JPG or PNG, 1920x1080, max 5MB</p>
                @if($tenant->login_background)
                    <div class="mt-2 relative inline-block">
                        <img src="{{ Storage::url($tenant->login_background) }}" alt="Login Background" class="h-32 w-auto object-cover border rounded">
                        <a href="{{ route('settings.delete.asset', ['asset' => 'login_background']) }}"
                           class="ml-2 inline-flex items-center text-sm text-red-600 hover:text-red-800"
                           onclick="return confirm('Are you sure you want to delete this background?')">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Delete
                        </a>
                    </div>
                @endif
                <input type="file" name="login_background" id="login_background" accept="image/png,image/jpeg,image/webp"
                       class="mt-2 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                @error('login_background')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </x-settings-section>

    <!-- Save Button -->
    <div class="flex justify-end">
        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            Save Branding Settings
        </button>
    </div>
</form>

<script>
    // Update color text inputs when color picker changes
    document.querySelectorAll('input[type="color"]').forEach(picker => {
        picker.addEventListener('input', function() {
            this.nextElementSibling.value = this.value;
        });
    });
</script>
