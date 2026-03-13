<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_type' => 'required|in:individual,business',
            'first_name' => 'required_if:customer_type,individual|string|max:255',
            'last_name' => 'required_if:customer_type,individual|string|max:255',
            'company_name' => 'required_if:customer_type,business|string|max:255',
            'national_id' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:customers,email',
            'phone' => 'nullable|string|max:255',
            'mobile' => 'required|string|max:255',
            'whatsapp' => 'nullable|string|max:255',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:255',
            'country' => 'required|string|max:255',
            'plan' => 'required|string|max:255',
            'site' => 'required|string|max:255',
            'router' => 'required|string|max:255',
            'ip_address' => 'nullable|ip|max:255',
            'pppoe_username' => 'nullable|string|max:255',
            'pppoe_password' => 'nullable|string|max:255',
            'billing_type' => 'required|in:prepaid,postpaid',
            'billing_cycle' => 'required|in:monthly,quarterly,yearly',
            'balance' => 'required|numeric|min:-99999999.99|max:99999999.99',
            'credit_limit' => 'required|numeric|min:0|max:99999999.99',
            'tax_exempt' => 'boolean',
            'status' => 'required|in:pending,active,inactive,suspended',
            'auto_activate' => 'boolean',
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
            'plan.required' => 'Please select a service plan.',
            'site.required' => 'Please select a site/location.',
            'router.required' => 'Please select a router/NAS.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'tax_exempt' => $this->boolean('tax_exempt'),
            'auto_activate' => $this->boolean('auto_activate'),
        ]);
    }
}
