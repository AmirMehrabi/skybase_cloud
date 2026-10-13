<?php

namespace App\Http\Requests\Subscription;

use App\Services\SafeRouterUsername;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('subscriptions.write') === true;
    }

    public function rules(): array
    {
        $tenantId = tenant_id() ?? $this->user()?->tenant_id;
        $effectiveRouterId = $this->input('router_id', $this->route('subscription')->router_id);

        return [
            'plan_id' => ['nullable', Rule::exists('plans', 'id')->where('tenant_id', $tenantId)],
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'service_type' => 'nullable|in:hotspot,pppoe,vpn',
            'router_id' => ['nullable', Rule::exists('routers', 'id')->where('tenant_id', $tenantId)],
            'access_point_id' => ['nullable', Rule::exists('access_points', 'id')->where('tenant_id', $tenantId)],
            'site' => 'nullable|string|max:255',
            'ip_pool_id' => [
                'nullable',
                'integer',
                Rule::exists('ip_pools', 'id')->where('tenant_id', $tenantId)->where(fn ($query) => $query->where(function ($query) use ($effectiveRouterId): void {
                    $query->where('router_id', $effectiveRouterId)
                        ->orWhere('all_devices', true);
                })),
            ],
            'ip_address' => 'nullable|ip|max:255',
            'pppoe_username' => ['nullable', 'string', 'max:255', SafeRouterUsername::validationRule()],
            'pppoe_password' => 'nullable|string|max:255',
            'billing_cycle' => 'nullable|in:daily,weekly,monthly,quarterly,yearly',
            'billing_enabled' => 'nullable|boolean',
            'auto_suspension_enabled' => 'nullable|boolean',
            'grace_period_days' => 'nullable|integer|min:0|max:365',
            'next_billing_date' => 'nullable|date',
            'status' => 'nullable|in:pending,active,suspended,cancelled',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'notes' => 'nullable|string',
            'sync_ip_routes' => 'nullable|boolean',
            'ip_routes' => 'nullable|array',
            'ip_routes.*' => 'array',
            'ip_routes.*.ip_pool_id' => ['nullable', 'integer', Rule::exists('ip_pools', 'id')->where('tenant_id', $tenantId)],
            'ip_routes.*.ip_address' => 'nullable|ip|max:255',
            'ip_routes.*.cidr' => 'nullable|integer|min:1|max:32',
        ];
    }

    public function messages(): array
    {
        return ['plan_id.exists' => 'The selected plan is unavailable.', 'router_id.exists' => 'The selected router is unavailable.', 'ip_pool_id.exists' => 'The selected IP pool is unavailable.'];
    }
}
