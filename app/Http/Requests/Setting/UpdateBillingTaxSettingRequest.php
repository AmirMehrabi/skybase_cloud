<?php

namespace App\Http\Requests\Setting;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBillingTaxSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tax_enabled' => ['nullable', 'boolean'],
            'tax_name' => ['required', 'string', 'max:100'],
            'tax_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'show_tax_id_on_invoice' => ['nullable', 'boolean'],
            'invoice_note' => ['nullable', 'string', 'max:1000'],
            'sync_existing_subscription_items' => ['nullable', 'boolean'],
        ];
    }
}
