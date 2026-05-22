<?php

namespace App\Http\Requests\Customer;

use App\Support\Validation\CustomerValidation;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return CustomerValidation::rules($this->route('customer'), includeStatus: true);
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return CustomerValidation::messages();
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'tax_exempt' => $this->boolean('tax_exempt'),
            'billing_enabled' => $this->boolean('billing_enabled', true),
            'organization_id' => $this->input('organization_id') ?: null,
        ]);
    }
}
