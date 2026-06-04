<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreIpPoolRequest extends FormRequest
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
        $tenantId = $this->user()?->tenant_id;

        return [
            // Basic Information
            'name' => ['required', 'string', 'max:255'],
            'router_id' => ['nullable', Rule::exists('routers', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
            'router_ids' => ['nullable', 'array'],
            'router_ids.*' => [Rule::exists('routers', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
            'site_id' => ['nullable', Rule::exists('sites', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
            'site' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:dynamic,static,mixed'],
            'status' => ['nullable', 'in:active,disabled,exhausted'],
            'all_devices' => ['boolean'],

            // Network Configuration
            'network_address' => ['required', 'ip', 'max:45'],
            'cidr' => ['required', 'integer', 'between:8,32'],
            'gateway' => ['nullable', 'ip', 'max:45'],
            'dns_primary' => ['nullable', 'ip', 'max:45'],
            'dns_secondary' => ['nullable', 'ip', 'max:45'],
            'vlan_id' => ['nullable', 'integer', 'between:1,4094'],

            // Advanced Settings
            'allow_static' => ['boolean'],
            'auto_assign' => ['boolean'],
            'block_reserved' => ['boolean'],
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
            'name.required' => 'The pool name is required.',
            'network_address.required' => 'The network address is required.',
            'network_address.ip' => 'Please enter a valid IP address.',
            'cidr.required' => 'The CIDR prefix is required.',
            'cidr.between' => 'The CIDR prefix must be between 8 and 32.',
            'gateway.ip' => 'Please enter a valid gateway IP address.',
            'dns_primary.ip' => 'Please enter a valid primary DNS IP address.',
            'dns_secondary.ip' => 'Please enter a valid secondary DNS IP address.',
            'vlan_id.between' => 'The VLAN ID must be between 1 and 4094.',
            'type.required' => 'The pool type is required.',
            'type.in' => 'The pool type must be dynamic, static, or mixed.',
            'router_ids.array' => 'The selected devices must be provided as a list.',
            'router_ids.*.exists' => 'One or more selected devices are invalid.',
            'site_id.exists' => 'The selected site is invalid.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $routerIds = collect($this->input('router_ids', []))
            ->filter(fn ($routerId): bool => filled($routerId))
            ->map(fn ($routerId): int => (int) $routerId)
            ->values()
            ->all();

        $this->merge([
            'allow_static' => $this->boolean('allow_static', false),
            'auto_assign' => $this->boolean('auto_assign', false),
            'block_reserved' => $this->boolean('block_reserved', false),
            'all_devices' => $this->boolean('all_devices', false),
            'router_ids' => $routerIds,
            'router_id' => $this->boolean('all_devices') ? null : ($this->input('router_id') ?: ($routerIds[0] ?? null)),
        ]);
    }

    /**
     * Add cross-field validation.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->boolean('all_devices')) {
                return;
            }

            $routerIds = collect($this->input('router_ids', []))
                ->filter(fn ($routerId): bool => filled($routerId))
                ->values();

            if (blank($this->input('router_id')) && $routerIds->isEmpty()) {
                $validator->errors()->add('router_ids', 'Please select at least one device or choose all devices.');
            }
        });
    }
}
