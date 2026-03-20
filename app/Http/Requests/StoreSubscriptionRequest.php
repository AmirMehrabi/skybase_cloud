<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionRequest extends FormRequest
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
        $connectionType = $this->input('connection_type', 'pppoe');
        $ipManagement = $this->input('ip_management');

        return [
            'customer_id' => 'required|exists:customers,id',
            'plan_id' => 'required|exists:plans,id',
            'router_id' => 'required|exists:routers,id',
            'site' => 'nullable|string|max:255',
            'connection_type' => 'required|in:pppoe,dhcp,static',
            // PPPoE credentials (required for PPPoE)
            'pppoe_username' => $connectionType === 'pppoe' ? 'required|string|max:255' : 'nullable|string|max:255',
            'pppoe_password' => $connectionType === 'pppoe' ? 'required|string|max:255' : 'nullable|string|max:255',
            // MAC address (required for DHCP)
            'mac_address' => $connectionType === 'dhcp' ? 'required|mac_address' : 'nullable|mac_address',
            // IP management
            'ip_management' => 'nullable|in:system,router',
            'ip_pool_id' => $ipManagement === 'system' ? 'required|exists:ip_pools,id' : 'nullable|exists:ip_pools,id',
            'ip_address' => 'nullable|ip|max:255',
            'billing_cycle' => 'required|in:monthly,quarterly,yearly',
            'status' => 'required|in:pending,active,suspended,cancelled',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'notes' => 'nullable|string',
            // Line items
            'items' => 'required|array|min:1',
            'items.*.item_type' => 'required|string|in:plan,additional_service,setup_fee',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.discount_type' => 'nullable|in:none,fixed,percentage',
            'items.*.tax_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.recurring' => 'required|boolean',
            'items.*.billing_cycle' => 'nullable|in:monthly,quarterly,yearly,onetime',
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
            'customer_id.required' => 'Please select a customer.',
            'customer_id.exists' => 'The selected customer is invalid.',
            'plan_id.required' => 'Please select a service plan.',
            'plan_id.exists' => 'The selected plan is invalid.',
            'router_id.required' => 'Please select a router/NAS.',
            'router_id.exists' => 'The selected router is invalid.',
            'connection_type.required' => 'Please select a connection type.',
            'connection_type.in' => 'Invalid connection type selected.',
            'pppoe_username.required' => 'PPPoE username is required for PPPoE connections.',
            'pppoe_password.required' => 'PPPoE password is required for PPPoE connections.',
            'mac_address.required' => 'MAC address is required for DHCP connections.',
            'ip_pool_id.required' => 'Please select an IP pool for system-managed IPs.',
            'ip_pool_id.exists' => 'The selected IP pool is invalid.',
            'items.required' => 'Please add at least one line item.',
            'items.*.description.required' => 'Each line item must have a description.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'items' => $this->input('items', []),
        ]);
    }
}
