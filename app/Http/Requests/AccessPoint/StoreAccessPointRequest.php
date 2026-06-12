<?php

namespace App\Http\Requests\AccessPoint;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationRule;

class StoreAccessPointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenantId = (string) (tenant()?->id ?? $this->user()?->tenant_id);

        return [
            'name' => ['required', 'string', 'max:255'],
            'site_id' => ['required', Rule::exists('sites', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
            'router_id' => ['nullable', Rule::exists('routers', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
            'model' => ['nullable', 'string', 'max:255'],
            'vendor' => ['required', 'string', 'max:255'],
            'mac_address' => [
                'required',
                'string',
                'max:17',
                Rule::unique('access_points', 'mac_address')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'ip_address' => ['nullable', 'ip'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'firmware_version' => ['nullable', 'string', 'max:255'],
            'frequency_band' => ['nullable', 'string', 'max:50'],
            'channel' => ['nullable', 'string', 'max:50'],
            'ssid' => ['nullable', 'string', 'max:255'],
            'tx_power' => ['nullable', 'integer', 'min:-100', 'max:100'],
            'antenna_type' => ['nullable', 'string', 'max:100'],
            'antenna_gain' => ['nullable', 'integer', 'min:0', 'max:100'],
            'height_meters' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'azimuth' => ['nullable', 'integer', 'min:0', 'max:360'],
            'coverage_angle' => ['nullable', 'integer', 'min:0', 'max:360'],
            'max_clients' => ['nullable', 'integer', 'min:0'],
            'connected_clients' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'string', 'in:online,offline,maintenance,decommissioned'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The access point name is required.',
            'site_id.required' => 'Please select a site for this access point.',
            'vendor.required' => 'The vendor field is required.',
            'mac_address.required' => 'The MAC address is required.',
            'mac_address.unique' => 'An access point with this MAC address already exists.',
            'mac_address.max' => 'The MAC address must not exceed 17 characters.',
            'ip_address.ip' => 'Please enter a valid IP address.',
        ];
    }
}
