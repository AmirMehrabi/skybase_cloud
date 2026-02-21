<?php

namespace App\Http\Requests\Setting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGeneralSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Company Information
            'company_name' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'business_license' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'support_phone' => ['nullable', 'string', 'max:255'],
            'support_email' => ['required', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],

            // Localization
            'timezone' => ['required', 'string', 'timezone'],
            'date_format' => ['required', 'in:m/d/Y,d/m/Y,Y-m-d,d.m.Y,F j, Y,j F Y'],
            'time_format' => ['required', 'in:12h,24h'],
            'first_day_of_week' => ['required', 'in:sunday,monday'],
            'currency' => ['required', 'string', 'max:3'],
            'currency_symbol_position' => ['required', 'in:before,after'],
            'thousands_separator' => ['required', 'in:,,., ,_'],
            'decimal_separator' => ['required', 'in:.,,'],
            'locale' => ['required', 'string', 'max:5'],

            // System
            'maintenance_mode' => ['nullable', 'boolean'],
            'custom_domain' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'company_name' => 'company name',
            'business_license' => 'business license',
            'tax_id' => 'tax ID',
            'website_url' => 'website URL',
            'support_phone' => 'support phone',
            'support_email' => 'support email',
        ];
    }
}
