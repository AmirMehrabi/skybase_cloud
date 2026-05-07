<?php

namespace App\Support\Validation;

use App\Models\Customer;
use Illuminate\Validation\Rule;

class CustomerValidation
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(?Customer $customer = null, bool $includeStatus = false): array
    {
        $tenantId = tenant_id() ?? auth()->user()?->tenant_id;
        $emailRule = Rule::unique('customers', 'email')
            ->where(fn ($query) => $query->where('tenant_id', $tenantId));

        if ($customer) {
            $emailRule->ignore($customer->id);
        }

        $rules = [
            'customer_type' => ['required', 'in:individual,business'],
            'first_name' => ['exclude_unless:customer_type,individual', 'required', 'string', 'max:255'],
            'last_name' => ['exclude_unless:customer_type,individual', 'required', 'string', 'max:255'],
            'company_name' => ['exclude_unless:customer_type,business', 'required', 'string', 'max:255'],
            'national_id' => ['exclude_unless:customer_type,individual', 'nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', $emailRule],
            'phone' => ['nullable', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:255'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'billing_type' => ['required', 'in:prepaid,postpaid'],
            'billing_enabled' => ['boolean'],
            'balance' => ['nullable', 'numeric', 'min:-99999999.99', 'max:99999999.99'],
            'credit_limit' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'tax_exempt' => ['boolean'],
        ];

        if ($includeStatus) {
            $rules['status'] = ['required', 'in:active,inactive,suspended'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'first_name.required' => 'The first name field is required for individuals.',
            'last_name.required' => 'The last name field is required for individuals.',
            'company_name.required' => 'The company name field is required for businesses.',
            'mobile.required' => 'The mobile number field is required.',
            'address_line1.required' => 'The address field is required.',
            'city.required' => 'The city field is required.',
        ];
    }
}
