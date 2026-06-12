<?php

namespace App\Http\Requests;

use App\Models\IpAddress;
use App\Models\Subscription;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'name' => 'required|string|max:255',
            'service_type' => 'required|in:hotspot,pppoe,vpn',
            'plan_id' => 'required|exists:plans,id',
            'router_id' => 'required|exists:routers,id',
            'access_point_id' => 'nullable|exists:access_points,id',
            'site' => 'nullable|string|max:255',
            'connection_type' => 'required|in:pppoe,dhcp,static',
            // PPP credentials (required for PPP connections)
            'pppoe_username' => $connectionType === 'pppoe' ? 'required|string|max:255' : 'nullable|string|max:255',
            'pppoe_password' => $connectionType === 'pppoe' ? 'required|string|max:255' : 'nullable|string|max:255',
            // MAC address (required for DHCP)
            'mac_address' => $connectionType === 'dhcp' ? 'required|mac_address' : 'nullable|mac_address',
            // IP management
            'ip_management' => 'nullable|in:system,router',
            'ip_pool_id' => $ipManagement === 'system' ? 'required|exists:ip_pools,id' : 'nullable|exists:ip_pools,id',
            'ip_address' => 'nullable|ip|max:255',
            'ip_routes' => 'nullable|array',
            'ip_routes.*' => 'array',
            'ip_routes.*.ip_pool_id' => 'nullable|integer|exists:ip_pools,id',
            'ip_routes.*.ip_address' => 'nullable|ip|max:255',
            'ip_routes.*.cidr' => 'nullable|integer|min:1|max:32',
            'billing_cycle' => 'required|in:monthly,quarterly,yearly',
            'billing_enabled' => 'boolean',
            'grace_period_days' => 'nullable|integer|min:0|max:365',
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $routes = collect($this->input('ip_routes', []))
                ->filter(fn (mixed $route): bool => is_array($route) && (filled($route['ip_pool_id'] ?? null) || filled($route['ip_address'] ?? null)))
                ->values();

            if ($routes->isEmpty()) {
                return;
            }

            if ($this->input('ip_management') !== 'system') {
                $validator->errors()->add('ip_routes', 'IP routes are only available when IP Management is set to System Managed.');

                return;
            }

            $tenantId = tenant_id() ?? $this->user()?->tenant_id;
            $destinations = [];

            foreach ($routes as $index => $route) {
                $poolId = $route['ip_pool_id'] ?? null;
                $ipAddress = $route['ip_address'] ?? null;
                $cidr = (int) ($route['cidr'] ?? 32);

                if (blank($poolId)) {
                    $validator->errors()->add("ip_routes.{$index}.ip_pool_id", 'Please select an IPAM pool for each IP route.');
                }

                if (blank($ipAddress)) {
                    $validator->errors()->add("ip_routes.{$index}.ip_address", 'Please select an IP address for each IP route.');
                }

                if (blank($poolId) || blank($ipAddress)) {
                    continue;
                }

                $destination = $ipAddress.'/'.$cidr;
                if (in_array($destination, $destinations, true)) {
                    $validator->errors()->add("ip_routes.{$index}.ip_address", 'Duplicate IP route destinations are not allowed.');
                }
                $destinations[] = $destination;

                $ip = IpAddress::query()
                    ->where('tenant_id', $tenantId)
                    ->where('ip_pool_id', $poolId)
                    ->where('ip_address', $ipAddress)
                    ->first();

                if (! $ip) {
                    $validator->errors()->add("ip_routes.{$index}.ip_address", 'The selected IP route address does not belong to the selected IPAM pool.');

                    continue;
                }

                if (! $ip->isAvailable()) {
                    $validator->errors()->add("ip_routes.{$index}.ip_address", 'The selected IP route address is not available.');
                }
            }
        });
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
            'name.required' => 'Please enter a subscription name.',
            'service_type.required' => 'Please select a subscription type.',
            'service_type.in' => 'Invalid subscription type selected.',
            'plan_id.required' => 'Please select a service plan.',
            'plan_id.exists' => 'The selected plan is invalid.',
            'router_id.required' => 'Please select a router/NAS.',
            'router_id.exists' => 'The selected router is invalid.',
            'connection_type.required' => 'Please select a connection type.',
            'connection_type.in' => 'Invalid connection type selected.',
            'pppoe_username.required' => 'PPP username is required for PPP connections.',
            'pppoe_password.required' => 'PPP password is required for PPP connections.',
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
        $customerId = $this->input('customer_id');

        $this->merge([
            'items' => $this->input('items', []),
            'name' => $this->filled('name') ? $this->input('name') : ($customerId ? Subscription::defaultNameForCustomer((int) $customerId) : null),
            'service_type' => $this->input('service_type', 'hotspot'),
            'billing_enabled' => $this->boolean('billing_enabled', true),
        ]);
    }
}
