<?php

namespace App\Http\Controllers;

use App\Http\Requests\Setting\UpdateBrandingSettingRequest;
use App\Http\Requests\Setting\UpdateGeneralSettingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $tenant = $this->getTenant();

        $timezones = \DateTimeZone::listIdentifiers();
        $currencies = $this->getCurrencies();
        $locales = $this->getLocales();

        return view('settings.index', compact('tenant', 'timezones', 'currencies', 'locales'));
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

    private function getTenant(): \App\Models\Tenant
    {
        $tenant = tenant();

        if (! $tenant && auth()->check() && auth()->user()->tenant_id) {
            $tenant = \App\Models\Tenant::find(auth()->user()->tenant_id);
        }

        return $tenant ?? throw new \Exception('Tenant not found.');
    }
}
