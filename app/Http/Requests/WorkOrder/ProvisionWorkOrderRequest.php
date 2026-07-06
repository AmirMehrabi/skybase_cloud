<?php

namespace App\Http\Requests\WorkOrder;

use App\Models\AccessPoint;
use App\Models\Plan;
use App\Models\Router;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProvisionWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('provision', $this->route('work_order')) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $tenantId = tenant_id() ?? $this->user()?->tenant_id;
        $connectionType = $this->input('connection_type');

        return [
            'name' => ['required', 'string', 'max:255'],
            'service_type' => ['required', Rule::in(['hotspot', 'pppoe', 'vpn'])],
            'plan_id' => ['required', Rule::exists(Plan::class, 'id')],
            'router_id' => ['required', Rule::exists(Router::class, 'id')->where('tenant_id', $tenantId)],
            'access_point_id' => ['nullable', Rule::exists(AccessPoint::class, 'id')->where('tenant_id', $tenantId)],
            'connection_type' => ['required', Rule::in(['pppoe', 'dhcp', 'static'])],
            'pppoe_username' => [
                $connectionType === 'pppoe' ? 'required' : 'nullable',
                'string',
                'max:255',
                Rule::unique('subscriptions', 'pppoe_username')->where('tenant_id', $tenantId),
            ],
            'pppoe_password' => [$connectionType === 'pppoe' ? 'required' : 'nullable', 'string', 'max:255'],
            'mac_address' => [$connectionType === 'dhcp' ? 'required' : 'nullable', 'mac_address'],
            'ip_management' => ['required', Rule::in(['router'])],
            'ip_address' => ['nullable', 'ip'],
        ];
    }
}
