<?php

namespace App\Support\Validation;

class ClientValidationRules
{
    /**
     * @return array<string, array<int, string>>
     */
    public static function customer(bool $includeStatus = false): array
    {
        $rules = [
            'customer_type' => ['required', 'in:individual,business'],
            'organization_id' => [],
            'first_name' => ['required_if:customer_type,individual', 'max:255'],
            'last_name' => ['required_if:customer_type,individual', 'max:255'],
            'company_name' => ['required_if:customer_type,business', 'max:255'],
            'national_id' => ['max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['max:255'],
            'mobile' => ['required', 'max:255'],
            'whatsapp' => ['max:255'],
            'address_line1' => ['required', 'max:255'],
            'address_line2' => ['max:255'],
            'city' => ['required', 'max:255'],
            'state' => ['max:255'],
            'postal_code' => ['max:255'],
            'country' => ['required', 'max:255'],
            'billing_type' => ['required', 'in:prepaid,postpaid'],
            'balance' => ['numeric', 'min:-99999999.99', 'max:99999999.99'],
            'credit_limit' => ['numeric', 'min:0', 'max:99999999.99'],
            'password' => ['min:8', 'max:72'],
        ];

        if ($includeStatus) {
            $rules['status'] = ['required', 'in:active,inactive,suspended'];
        }

        return $rules;
    }
}
