<?php

namespace App\Http\Requests\Setting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBrandingSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Theme Colors
            'primary_color' => ['required', 'regex:/^#?[A-Fa-f0-9]{6}$/'],
            'secondary_color' => ['required', 'regex:/^#?[A-Fa-f0-9]{6}$/'],
            'accent_color' => ['required', 'regex:/^#?[A-Fa-f0-9]{6}$/'],
            'dark_mode_enabled' => ['nullable', 'boolean'],
            'custom_css' => ['nullable', 'string', 'max:10000'],

            // Assets (images)
            'company_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'company_logo_dark' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'favicon' => ['nullable', 'image', 'mimes:png,ico,svg', 'max:1024'],
            'login_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'email_header_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'email_footer_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'invoice_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'login_background' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
        ];
    }

    public function attributes(): array
    {
        return [
            'primary_color' => 'primary color',
            'secondary_color' => 'secondary color',
            'accent_color' => 'accent color',
            'custom_css' => 'custom CSS',
            'company_logo' => 'company logo',
            'company_logo_dark' => 'dark company logo',
            'favicon' => 'favicon',
        ];
    }
}
