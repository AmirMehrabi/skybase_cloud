<?php

namespace App\Http\Controllers;

use App\Http\Requests\Setting\TestEmailSettingRequest;
use App\Http\Requests\Setting\UpdateBrandingSettingRequest;
use App\Http\Requests\Setting\UpdateEmailSettingRequest;
use App\Http\Requests\Setting\UpdateGeneralSettingRequest;
use App\Http\Requests\Setting\UpdateLdapSettingRequest;
use App\Models\Setting;
use App\Models\Tenant;
use App\Services\Ldap\LdapConnectionFactory;
use App\Services\Ldap\LdapSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class SettingController extends Controller
{
    public function index(): View
    {
        $tenant = $this->getTenant();

        $timezones = \DateTimeZone::listIdentifiers();
        $currencies = $this->getCurrencies();
        $locales = $this->getLocales();
        $emailSettings = $this->getEmailSettings($tenant->id);
        $ldapSettings = app(LdapSyncService::class)->settingsForTenant($tenant->id);

        return view('settings.index', compact('tenant', 'timezones', 'currencies', 'locales', 'emailSettings', 'ldapSettings'));
    }

    public function updateEmail(UpdateEmailSettingRequest $request): RedirectResponse
    {
        $tenant = $this->getTenant();
        $current = $this->getEmailSettings($tenant->id);

        $incomingPassword = $request->filled('incoming_password')
            ? $request->string('incoming_password')->toString()
            : $current['incoming']['password'];

        $outgoingPassword = $request->filled('outgoing_password')
            ? $request->string('outgoing_password')->toString()
            : $current['outgoing']['password'];

        Setting::updateOrCreate(
            ['tenant_id' => $tenant->id, 'key' => 'email.incoming'],
            [
                'value' => [
                    'active' => $request->boolean('incoming_active'),
                    'protocol' => $request->input('incoming_protocol'),
                    'host' => $request->input('incoming_host'),
                    'port' => $request->filled('incoming_port') ? $request->integer('incoming_port') : null,
                    'encryption' => $request->input('incoming_encryption'),
                    'username' => $request->input('incoming_username'),
                    'password' => $incomingPassword,
                    'mailbox' => $request->input('incoming_mailbox', 'INBOX'),
                ],
                'type' => 'json',
                'group' => 'email',
            ]
        );

        Setting::updateOrCreate(
            ['tenant_id' => $tenant->id, 'key' => 'email.outgoing'],
            [
                'value' => [
                    'active' => $request->boolean('outgoing_active'),
                    'host' => $request->input('outgoing_host'),
                    'port' => $request->filled('outgoing_port') ? $request->integer('outgoing_port') : null,
                    'encryption' => $request->input('outgoing_encryption'),
                    'username' => $request->input('outgoing_username'),
                    'password' => $outgoingPassword,
                    'from_email' => $request->input('outgoing_from_email'),
                    'from_name' => $request->input('outgoing_from_name'),
                ],
                'type' => 'json',
                'group' => 'email',
            ]
        );

        return redirect()
            ->route('settings.index', ['tab' => 'email'])
            ->with('success', 'Email settings updated successfully.');
    }

    public function testEmail(TestEmailSettingRequest $request): RedirectResponse
    {
        $tenant = $this->getTenant();
        $direction = $request->validated('direction');
        $settings = $this->getEmailSettings($tenant->id)[$direction];

        try {
            $this->testEmailConnection($settings, $direction);
        } catch (Throwable $exception) {
            return redirect()
                ->route('settings.index', ['tab' => 'email'])
                ->with('error', ucfirst($direction).' email test failed: '.$exception->getMessage());
        }

        return redirect()
            ->route('settings.index', ['tab' => 'email'])
            ->with('success', ucfirst($direction).' email connection tested successfully.');
    }

    public function updateGeneral(UpdateGeneralSettingRequest $request): RedirectResponse
    {
        $tenant = $this->getTenant();

        // Company Information
        $tenant->update([
            'company_name' => $request->company_name,
            'tagline' => $request->tagline,
            'business_license' => $request->business_license,
            'tax_id' => $request->tax_id,
            'website_url' => $request->website_url,
            'support_phone' => $request->support_phone,
            'email' => $request->support_email,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'zip' => $request->zip,
            'country' => $request->country,
        ]);

        // Localization
        $tenant->update([
            'timezone' => $request->timezone,
            'date_format' => $request->date_format,
            'time_format' => $request->time_format,
            'first_day_of_week' => $request->first_day_of_week,
            'currency' => $request->currency,
            'currency_symbol_position' => $request->currency_symbol_position,
            'thousands_separator' => $request->thousands_separator,
            'decimal_separator' => $request->decimal_separator,
            'locale' => $request->locale,
        ]);

        // System
        $tenant->update([
            'maintenance_mode' => $request->boolean('maintenance_mode'),
            'custom_domain' => $request->custom_domain,
        ]);

        return redirect()
            ->route('settings.index', ['tab' => 'general'])
            ->with('success', 'General settings updated successfully.');
    }

    public function updateBranding(UpdateBrandingSettingRequest $request): RedirectResponse
    {
        $tenant = $this->getTenant();

        // Handle file uploads
        $files = [
            'company_logo' => $request->file('company_logo'),
            'company_logo_dark' => $request->file('company_logo_dark'),
            'favicon' => $request->file('favicon'),
            'login_logo' => $request->file('login_logo'),
            'email_header_logo' => $request->file('email_header_logo'),
            'email_footer_logo' => $request->file('email_footer_logo'),
            'invoice_logo' => $request->file('invoice_logo'),
            'login_background' => $request->file('login_background'),
        ];

        foreach ($files as $field => $file) {
            if ($file) {
                // Delete old file if exists
                if ($tenant->$field) {
                    Storage::disk('public')->delete($tenant->$field);
                }

                $path = $file->store('settings/'.$tenant->id, 'public');
                $tenant->$field = $path;
            }
        }

        // Update branding fields
        $tenant->update([
            'primary_color' => $request->primary_color,
            'secondary_color' => $request->secondary_color,
            'accent_color' => $request->accent_color,
            'dark_mode_enabled' => $request->boolean('dark_mode_enabled'),
            'custom_css' => $request->custom_css,
        ]);

        // Update file paths separately (already set above)
        $tenant->save();

        return redirect()
            ->route('settings.index', ['tab' => 'branding'])
            ->with('success', 'Branding settings updated successfully.');
    }

    public function updateLdap(UpdateLdapSettingRequest $request): RedirectResponse
    {
        $tenant = $this->getTenant();
        $settings = $this->ldapSettingsFromRequest($request, $tenant->id);

        Setting::updateOrCreate(
            ['tenant_id' => $tenant->id, 'key' => 'ldap.connection'],
            ['value' => $settings['connection'], 'type' => 'json', 'group' => 'ldap']
        );

        Setting::updateOrCreate(
            ['tenant_id' => $tenant->id, 'key' => 'ldap.customer_sync'],
            ['value' => $settings['customer_sync'], 'type' => 'json', 'group' => 'ldap']
        );

        Setting::updateOrCreate(
            ['tenant_id' => $tenant->id, 'key' => 'ldap.organization_sync'],
            ['value' => $settings['organization_sync'], 'type' => 'json', 'group' => 'ldap']
        );

        Setting::updateOrCreate(
            ['tenant_id' => $tenant->id, 'key' => 'ldap.subscription_sync'],
            ['value' => $settings['subscription_sync'], 'type' => 'json', 'group' => 'ldap']
        );

        return redirect()
            ->route('settings.index', ['tab' => 'ldap'])
            ->with('success', 'LDAP settings updated successfully.');
    }

    public function testLdap(LdapConnectionFactory $connections): RedirectResponse
    {
        $this->authorizeLdapSettings();

        $tenant = $this->getTenant();
        $settings = app(LdapSyncService::class)->settingsForTenant($tenant->id);

        try {
            $connections->test($tenant->id, $settings['connection']);
        } catch (Throwable $exception) {
            return redirect()
                ->route('settings.index', ['tab' => 'ldap'])
                ->with('error', 'LDAP connection test failed: '.$exception->getMessage());
        }

        return redirect()
            ->route('settings.index', ['tab' => 'ldap'])
            ->with('success', 'LDAP connection tested successfully.');
    }

    public function discoverLdapOrganizationalUnits(Request $request, LdapConnectionFactory $connections, LdapSyncService $sync): JsonResponse
    {
        $this->authorizeLdapSettings();

        $request->validate([
            'hosts' => ['required', 'string', 'max:1000'],
            'port' => ['nullable', 'integer', 'between:1,65535'],
            'base_dn' => ['required', 'string', 'max:1000'],
            'username' => ['nullable', 'string', 'max:1000'],
            'password' => ['nullable', 'string', 'max:1000'],
            'timeout' => ['nullable', 'integer', 'between:1,60'],
            'use_tls' => ['nullable', 'boolean'],
            'use_starttls' => ['nullable', 'boolean'],
            'organization_excluded_ou_dns' => ['nullable', 'array'],
            'organization_excluded_ou_dns.*' => ['string', 'max:1000'],
        ]);

        $tenant = $this->getTenant();
        $settings = $sync->settingsForTenant($tenant->id);
        $settings['connection'] = $this->ldapConnectionSettingsFromRequest($request, $tenant->id);
        $settings['organization_sync']['excluded_ou_dns'] = $this->excludedOuDnsFromRequest($request);

        try {
            $connection = $connections->register($tenant->id, $settings['connection']);
            $organizationalUnits = $sync->discoverOrganizationalUnits($connection, $settings)
                ->map(fn (array $ou): array => [
                    'dn' => $ou['dn'],
                    'guid' => $ou['guid'],
                    'name' => $ou['name'],
                    'path' => $ou['path'],
                    'selected' => $ou['selected'],
                ])
                ->values();
        } catch (Throwable $exception) {
            return response()->json([
                'message' => 'OU discovery failed: '.$exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'organizational_units' => $organizationalUnits,
            'count' => $organizationalUnits->count(),
            'selected_count' => $organizationalUnits->where('selected', true)->count(),
        ]);
    }

    public function previewLdap(LdapSyncService $sync): RedirectResponse
    {
        $this->authorizeLdapSettings();

        $tenant = $this->getTenant();

        try {
            $result = $sync->syncTenant($tenant, dryRun: true);
        } catch (Throwable $exception) {
            return redirect()
                ->route('settings.index', ['tab' => 'ldap'])
                ->with('error', 'LDAP preview failed: '.$exception->getMessage());
        }

        return redirect()
            ->route('settings.index', ['tab' => 'ldap'])
            ->with('success', $this->ldapResultMessage('LDAP preview completed.', $result));
    }

    public function syncLdap(LdapSyncService $sync): RedirectResponse
    {
        $this->authorizeLdapSettings();

        $tenant = $this->getTenant();

        try {
            $result = $sync->syncTenant($tenant);
        } catch (Throwable $exception) {
            return redirect()
                ->route('settings.index', ['tab' => 'ldap'])
                ->with('error', 'LDAP sync failed: '.$exception->getMessage());
        }

        return redirect()
            ->route('settings.index', ['tab' => 'ldap'])
            ->with('success', $this->ldapResultMessage('LDAP sync completed.', $result));
    }

    public function deleteAsset(string $asset): RedirectResponse
    {
        $tenant = $this->getTenant();

        $allowedAssets = [
            'company_logo',
            'company_logo_dark',
            'favicon',
            'login_logo',
            'email_header_logo',
            'email_footer_logo',
            'invoice_logo',
            'login_background',
        ];

        if (! \in_array($asset, $allowedAssets, true)) {
            return redirect()
                ->route('settings.index', ['tab' => 'branding'])
                ->with('error', 'Invalid asset.');
        }

        if ($tenant->$asset) {
            Storage::disk('public')->delete($tenant->$asset);
            $tenant->$asset = null;
            $tenant->save();
        }

        return redirect()
            ->route('settings.index', ['tab' => 'branding'])
            ->with('success', 'Asset deleted successfully.');
    }

    private function getCurrencies(): array
    {
        return [
            'USD' => 'USD - US Dollar ($)',
            'EUR' => 'EUR - Euro (€)',
            'GBP' => 'GBP - British Pound (£)',
            'JPY' => 'JPY - Japanese Yen (¥)',
            'CAD' => 'CAD - Canadian Dollar (C$)',
            'AUD' => 'AUD - Australian Dollar (A$)',
            'CHF' => 'CHF - Swiss Franc (Fr)',
            'CNY' => 'CNY - Chinese Yuan (¥)',
            'INR' => 'INR - Indian Rupee (₹)',
            'BRL' => 'BRL - Brazilian Real (R$)',
            'MXN' => 'MXN - Mexican Peso ($)',
            'ZAR' => 'ZAR - South African Rand (R)',
            'NGN' => 'NGN - Nigerian Naira (₦)',
            'EGP' => 'EGP - Egyptian Pound (£E)',
            'KES' => 'KES - Kenyan Shilling (KSh)',
            'GHS' => 'GHS - Ghanaian Cedi (GH₵)',
            'UGX' => 'UGX - Ugandan Shilling (USh)',
            'TZS' => 'TZS - Tanzanian Shilling (TSh)',
            'RWF' => 'RWF - Rwandan Franc (RwF)',
            'BIF' => 'BIF - Burundian Franc (FBu)',
            'CDF' => 'CDF - Congolese Franc (FC)',
            'AOA' => 'AOA - Angolan Kwanza (Kz)',
            'ZMW' => 'ZMW - Zambian Kwacha (ZK)',
            'SZL' => 'SZL - Swazi Lilangeni (L)',
            'LSL' => 'LSL - Lesotho Loti (L)',
            'NAD' => 'NAD - Namibian Dollar (N$)',
            'BWP' => 'BWP - Botswana Pula (P)',
            'MZN' => 'MZN - Mozambican Metical (MT)',
            'MWK' => 'MWK - Malawian Kwacha (MK)',
            'SDG' => 'SDG - Sudanese Pound (ج.س.)',
            'ETB' => 'ETB - Ethiopian Birr (Br)',
            'SOS' => 'SOS - Somali Shilling (Sh)',
            'DJF' => 'DJF - Djiboutian Franc (Fdj)',
            'ERN' => 'ERN - Eritrean Nakfa (Nfk)',
            'SSP' => 'SSP - South Sudanese Pound (£)',
            'GMD' => 'GMD - Gambian Dalasi (D)',
            'CVE' => 'CVE - Cape Verdean Escudo ($)',
            'SLL' => 'SLL - Sierra Leonean Leone (Le)',
            'LRD' => 'LRD - Liberian Dollar (L$)',
            'GNF' => 'GNF - Guinean Franc (FG)',
            'XOF' => 'XOF - West African CFA Franc (CFA)',
            'XAF' => 'XAF - Central African CFA Franc (FCFA)',
            'XCF' => 'XCF - Comorian Franc (CF)',
            'SCR' => 'SCR - Seychellois Rupee (₨)',
            'MGA' => 'MGA - Malagasy Ariary (Ar)',
            'MUR' => 'MUR - Mauritian Rupee (₨)',
        ];
    }

    private function getLocales(): array
    {
        return [
            'en' => 'English',
            'es' => 'Spanish',
            'fr' => 'French',
            'pt' => 'Portuguese',
            'ar' => 'Arabic',
            'sw' => 'Swahili',
            'zh' => 'Chinese',
            'hi' => 'Hindi',
        ];
    }

    /**
     * @return array{incoming: array<string, mixed>, outgoing: array<string, mixed>}
     */
    private function getEmailSettings(string $tenantId): array
    {
        $incomingDefaults = [
            'active' => false,
            'protocol' => 'imap',
            'host' => null,
            'port' => 993,
            'encryption' => 'ssl',
            'username' => null,
            'password' => null,
            'mailbox' => 'INBOX',
        ];

        $outgoingDefaults = [
            'active' => false,
            'host' => null,
            'port' => 587,
            'encryption' => 'tls',
            'username' => null,
            'password' => null,
            'from_email' => null,
            'from_name' => null,
        ];

        return [
            'incoming' => array_merge(
                $incomingDefaults,
                Setting::get('email.incoming', [], $tenantId) ?? []
            ),
            'outgoing' => array_merge(
                $outgoingDefaults,
                Setting::get('email.outgoing', [], $tenantId) ?? []
            ),
        ];
    }

    /**
     * @return array{connection: array<string, mixed>, organization_sync: array<string, mixed>, customer_sync: array<string, mixed>, subscription_sync: array<string, mixed>}
     */
    private function ldapSettingsFromRequest(UpdateLdapSettingRequest $request, string $tenantId): array
    {
        return [
            'connection' => $this->ldapConnectionSettingsFromRequest($request, $tenantId),
            'organization_sync' => [
                'base_dn' => $request->input('organization_base_dn'),
                'filter' => $request->input('organization_filter'),
                'unique_attribute' => $request->input('organization_unique_attribute', 'objectGUID'),
                'match_attribute' => $request->input('organization_match_attribute', $request->input('organization_unique_attribute', 'objectGUID')),
                'excluded_ou_dns' => $this->excludedOuDnsFromRequest($request),
                'map' => [
                    'code' => $request->input('organization_map_code'),
                    'name' => $request->input('organization_map_name'),
                    'description' => $request->input('organization_map_description'),
                    'status' => $request->input('organization_map_status'),
                ],
            ],
            'customer_sync' => [
                'base_dn' => $request->input('customer_base_dn'),
                'filter' => $request->input('customer_filter', '(objectClass=*)'),
                'unique_attribute' => $request->input('customer_unique_attribute', 'uid'),
                'match_attribute' => $request->input('customer_match_attribute', $request->input('customer_unique_attribute', 'uid')),
                'organization_attribute' => $request->input('customer_organization_attribute'),
                'organization_match_field' => $request->input('customer_organization_match_field', 'code'),
                'map' => [
                    'name' => $request->input('customer_map_name'),
                    'email' => $request->input('customer_map_email'),
                    'phone' => $request->input('customer_map_phone'),
                    'mobile' => $request->input('customer_map_mobile'),
                    'customer_code' => $request->input('customer_map_customer_code'),
                    'status' => $request->input('customer_map_status'),
                ],
            ],
            'subscription_sync' => [
                'base_dn' => $request->input('subscription_base_dn'),
                'filter' => $request->input('subscription_filter'),
                'unique_attribute' => $request->input('subscription_unique_attribute', 'uid'),
                'customer_attribute' => $request->input('subscription_customer_attribute', 'uid'),
                'customer_match_field' => $request->input('subscription_customer_match_field', 'customer_code'),
                'map' => [
                    'subscription_code' => $request->input('subscription_map_subscription_code'),
                    'pppoe_username' => $request->input('subscription_map_pppoe_username'),
                    'pppoe_password' => $request->input('subscription_map_pppoe_password'),
                    'ip_address' => $request->input('subscription_map_ip_address'),
                    'mac_address' => $request->input('subscription_map_mac_address'),
                    'status' => $request->input('subscription_map_status'),
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function ldapConnectionSettingsFromRequest(Request $request, string $tenantId): array
    {
        $current = app(LdapSyncService::class)->settingsForTenant($tenantId);
        $password = $request->filled('password')
            ? $request->string('password')->toString()
            : $current['connection']['password'];

        return [
            'enabled' => $request->boolean('enabled'),
            'hosts' => $this->splitHosts($request->string('hosts')->toString()),
            'port' => $request->filled('port') ? $request->integer('port') : 389,
            'base_dn' => $request->input('base_dn'),
            'username' => $request->input('username'),
            'password' => $password,
            'timeout' => $request->filled('timeout') ? $request->integer('timeout') : 5,
            'use_tls' => $request->boolean('use_tls'),
            'use_starttls' => $request->boolean('use_starttls'),
            'sync_interval_minutes' => $request->filled('sync_interval_minutes') ? $request->integer('sync_interval_minutes') : 15,
            'missing_action' => $request->input('missing_action', 'mark_inactive'),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function excludedOuDnsFromRequest(Request $request): array
    {
        return collect($request->input('organization_excluded_ou_dns', []))
            ->filter(fn ($dn): bool => filled($dn))
            ->map(fn ($dn): string => (string) $dn)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function splitHosts(string $hosts): array
    {
        return preg_split('/[\s,]+/', $hosts, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function ldapResultMessage(string $prefix, array $result): string
    {
        return sprintf(
            '%s Organizations: %d created, %d updated, %d skipped, %d missing. Customers: %d created, %d updated, %d skipped, %d missing. Subscriptions: %d created, %d updated, %d skipped, %d missing.',
            $prefix,
            $result['organizations']['created'],
            $result['organizations']['updated'],
            $result['organizations']['skipped'],
            $result['organizations']['missing'],
            $result['customers']['created'],
            $result['customers']['updated'],
            $result['customers']['skipped'],
            $result['customers']['missing'],
            $result['subscriptions']['created'],
            $result['subscriptions']['updated'],
            $result['subscriptions']['skipped'],
            $result['subscriptions']['missing'],
        );
    }

    private function authorizeLdapSettings(): void
    {
        abort_unless(auth()->user()?->isAdmin() === true, 403);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function testEmailConnection(array $settings, string $direction): void
    {
        $host = trim((string) ($settings['host'] ?? ''));
        $port = (int) ($settings['port'] ?? 0);
        $encryption = $settings['encryption'] ?? 'none';

        if ($host === '' || $port < 1 || $port > 65535) {
            throw new \RuntimeException('Host and port are required before testing.');
        }

        $scheme = $encryption === 'ssl' ? 'ssl' : 'tcp';
        $remoteSocket = "{$scheme}://{$host}:{$port}";
        $errorCode = 0;
        $errorMessage = '';

        $socket = @stream_socket_client(
            $remoteSocket,
            $errorCode,
            $errorMessage,
            10,
            STREAM_CLIENT_CONNECT
        );

        if ($socket === false) {
            throw new \RuntimeException($errorMessage !== '' ? $errorMessage : "Unable to connect to {$host}:{$port}.");
        }

        stream_set_timeout($socket, 10);

        if ($direction === 'outgoing') {
            fgets($socket, 512);
        }

        fclose($socket);
    }

    private function getTenant(): Tenant
    {
        $tenant = tenant();

        if (! $tenant && auth()->check() && auth()->user()->tenant_id) {
            $tenant = Tenant::find(auth()->user()->tenant_id);
        }

        return $tenant ?? throw new \Exception('Tenant not found.');
    }
}
