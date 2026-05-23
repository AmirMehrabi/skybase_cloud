<?php

namespace App\Http\Requests\Setting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLdapSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        $enabled = $this->boolean('enabled');
        $radiusAuthEnabled = $this->boolean('radius_auth_enabled');
        $connectionRequired = $enabled || $radiusAuthEnabled;

        return [
            'enabled' => ['nullable', 'boolean'],
            'hosts' => [$connectionRequired ? 'required' : 'nullable', 'string', 'max:1000'],
            'port' => [$connectionRequired ? 'required' : 'nullable', 'integer', 'between:1,65535'],
            'base_dn' => [$connectionRequired ? 'required' : 'nullable', 'string', 'max:1000'],
            'username' => ['nullable', 'string', 'max:1000'],
            'password' => ['nullable', 'string', 'max:1000'],
            'timeout' => ['nullable', 'integer', 'between:1,60'],
            'use_tls' => ['nullable', 'boolean'],
            'use_starttls' => ['nullable', 'boolean'],
            'sync_interval_minutes' => ['nullable', 'integer', 'min:5', 'max:1440'],
            'missing_action' => ['required', Rule::in(['mark_inactive', 'ignore', 'soft_delete'])],
            'radius_auth_enabled' => ['nullable', 'boolean'],
            'radius_auth_mode' => [$radiusAuthEnabled ? 'required' : 'nullable', Rule::in(['ldap_bind'])],
            'radius_auth_username_attribute' => [$radiusAuthEnabled ? 'required' : 'nullable', 'string', 'max:255'],

            'organization_base_dn' => ['nullable', 'string', 'max:1000'],
            'organization_filter' => ['nullable', 'string', 'max:1000'],
            'organization_unique_attribute' => ['nullable', 'string', 'max:255'],
            'organization_match_attribute' => ['nullable', 'string', 'max:255'],
            'organization_map_code' => ['nullable', 'string', 'max:255'],
            'organization_map_name' => ['nullable', 'string', 'max:255'],
            'organization_map_description' => ['nullable', 'string', 'max:255'],
            'organization_map_status' => ['nullable', 'string', 'max:255'],
            'organization_excluded_ou_dns' => ['nullable', 'array'],
            'organization_excluded_ou_dns.*' => ['string', 'max:1000'],

            'customer_base_dn' => ['nullable', 'string', 'max:1000'],
            'customer_filter' => [$enabled ? 'required' : 'nullable', 'string', 'max:1000'],
            'customer_unique_attribute' => [$enabled ? 'required' : 'nullable', 'string', 'max:255'],
            'customer_match_attribute' => ['nullable', 'string', 'max:255'],
            'customer_organization_attribute' => ['nullable', 'string', 'max:255'],
            'customer_organization_match_field' => ['nullable', Rule::in(['code', 'name', 'ldap_guid'])],
            'customer_map_name' => ['nullable', 'string', 'max:255'],
            'customer_map_email' => ['nullable', 'string', 'max:255'],
            'customer_map_phone' => ['nullable', 'string', 'max:255'],
            'customer_map_mobile' => ['nullable', 'string', 'max:255'],
            'customer_map_customer_code' => ['nullable', 'string', 'max:255'],
            'customer_map_status' => ['nullable', 'string', 'max:255'],

            'subscription_base_dn' => ['nullable', 'string', 'max:1000'],
            'subscription_filter' => ['nullable', 'string', 'max:1000'],
            'subscription_unique_attribute' => ['nullable', 'string', 'max:255'],
            'subscription_customer_attribute' => ['nullable', 'string', 'max:255'],
            'subscription_customer_match_field' => ['nullable', Rule::in(['customer_code', 'email', 'ldap_guid'])],
            'subscription_map_subscription_code' => ['nullable', 'string', 'max:255'],
            'subscription_map_pppoe_username' => ['nullable', 'string', 'max:255'],
            'subscription_map_pppoe_password' => ['nullable', 'string', 'max:255'],
            'subscription_map_ip_address' => ['nullable', 'string', 'max:255'],
            'subscription_map_mac_address' => ['nullable', 'string', 'max:255'],
            'subscription_map_status' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'base_dn' => 'base DN',
            'organization_base_dn' => 'organization base DN',
            'organization_filter' => 'organization LDAP filter',
            'organization_unique_attribute' => 'organization unique attribute',
            'customer_base_dn' => 'customer base DN',
            'customer_filter' => 'customer LDAP filter',
            'customer_unique_attribute' => 'customer unique attribute',
            'subscription_base_dn' => 'subscription base DN',
            'subscription_filter' => 'subscription LDAP filter',
            'subscription_unique_attribute' => 'subscription unique attribute',
            'subscription_customer_attribute' => 'subscription customer attribute',
            'radius_auth_username_attribute' => 'RADIUS LDAP username attribute',
        ];
    }
}
