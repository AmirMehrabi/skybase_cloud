<?php

namespace App\Http\Requests\Customer;

use Illuminate\Contracts\Validation\ValidationRule;
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $customer = $this->route('customer');

        return [
            'customer_type' => 'required|in:individual,business',
            'first_name' => 'required_if:customer_type,individual|string|max:255',
            'last_name' => 'required_if:customer_type,individual|string|max:255',
            'company_name' => 'required_if:customer_type,business|string|max:255',
            'national_id' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:customers,email,'.($customer?->id ?? 'NULL'),
            'phone' => 'nullable|string|max:255',
            'mobile' => 'required|string|max:255',
            'whatsapp' => 'nullable|string|max:255',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:255',
            'country' => 'required|string|max:255',
            'billing_type' => 'required|in:prepaid,postpaid',
            'balance' => 'nullable|numeric|min:-99999999.99|max:99999999.99',
            'credit_limit' => 'nullable|numeric|min:0|max:99999999.99',
            'tax_exempt' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required_if' => 'The first name field is required for individuals.',
            'last_name.required_if' => 'The last name field is required for individuals.',
            'company_name.required_if' => 'The company name field is required for businesses.',
            'mobile.required' => 'The mobile number field is required.',
            'address_line1.required' => 'The address field is required.',
            'city.required' => 'The city field is required.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'tax_exempt' => $this->boolean('tax_exempt'),
        ]);
    }
}
