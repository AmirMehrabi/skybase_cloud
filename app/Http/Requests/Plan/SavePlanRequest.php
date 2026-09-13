<?php

namespace App\Http\Requests\Plan;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePlanRequest extends FormRequest
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
        $tenantId = (string) (tenant_id() ?? $this->user()?->tenant_id);
        $plan = $this->route('plan');

        return [
            'user_group_id' => ['nullable', Rule::exists('user_groups', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
            'name' => ['required', 'string', 'max:255'],
            'internal_name' => ['required', 'string', 'max:255', Rule::unique('plans', 'internal_name')->where('tenant_id', $tenantId)->ignore($plan?->id)],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive', 'archived'])],
            'visibility' => ['required', Rule::in(['public', 'private', 'hidden'])],
            'type' => ['required', Rule::in(['pppoe', 'hotspot', 'static', 'dhcp', 'fiber', 'wireless'])],
            'category' => ['nullable', 'string', 'max:255'],
            'download_speed' => ['required', 'integer', 'min:0'],
            'upload_speed' => ['required', 'integer', 'min:0'],
            'burst_download' => ['nullable', 'integer', 'min:0'],
            'burst_upload' => ['nullable', 'integer', 'min:0'],
            'bandwidth_unit' => ['required', Rule::in(['Kbps', 'Mbps', 'Gbps'])],
            'shaping_mode' => ['nullable', Rule::in(['basic', 'advanced', 'disabled'])],
            'burst_threshold_download' => ['nullable', 'integer', 'min:0'],
            'burst_threshold_upload' => ['nullable', 'integer', 'min:0'],
            'burst_time_download' => ['nullable', 'integer', 'min:1', 'max:86400'],
            'burst_time_upload' => ['nullable', 'integer', 'min:1', 'max:86400'],
            'min_download_speed' => ['nullable', 'integer', 'min:0', 'lte:download_speed'],
            'min_upload_speed' => ['nullable', 'integer', 'min:0', 'lte:upload_speed'],
            'shaping_priority' => ['nullable', 'integer', 'min:1', 'max:8'],
            'queue_type' => ['nullable', 'string', 'max:255'],
            'data_limit' => ['nullable', 'integer', 'min:0'],
            'data_unit' => ['required', Rule::in(['MB', 'GB', 'TB'])],
            'data_cap_action' => ['nullable', Rule::in(['none', 'notify', 'throttle', 'suspend'])],
            'throttle_download_speed' => ['nullable', 'integer', 'min:0'],
            'throttle_upload_speed' => ['nullable', 'integer', 'min:0'],
            'unlimited' => ['nullable', 'boolean'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'billing_cycle' => ['required', Rule::in(['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])],
            'grace_period_days' => ['required', 'integer', 'min:0', 'max:365'],
            'setup_fee' => ['nullable', 'numeric', 'min:0'],
            'tax_profile' => ['nullable', 'string', 'max:255'],
            'router_profile' => ['nullable', 'string', 'max:255'],
            'ip_pool' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:10'],
            'contract_required' => ['nullable', 'boolean'],
            'contract_duration' => ['nullable', 'integer', 'min:1'],
            'available_from' => ['nullable', 'date'],
            'available_to' => ['nullable', 'date', 'after_or_equal:available_from'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
