<?php

namespace App\Http\Requests\Admin\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCentralAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('tenants', 'email')->ignore($this->route('tenant'))],
            'phone' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'timezone' => ['required', 'timezone'],
            'status' => ['required', Rule::in(['pending', 'active', 'suspended'])],
            'plan_id' => ['nullable', Rule::exists('plans', 'id')->where('tenant_id', $this->route('tenant')->id)],
            'trial_ends_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return ['plan_id.exists' => 'The selected plan must belong to this tenant.'];
    }
}
