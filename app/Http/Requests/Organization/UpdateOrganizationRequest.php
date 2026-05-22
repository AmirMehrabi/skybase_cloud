<?php

namespace App\Http\Requests\Organization;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationRequest extends FormRequest
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
        $tenantId = tenant_id() ?? auth()->user()?->tenant_id;
        $organization = $this->route('organization');

        return [
            'code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('organizations', 'code')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($organization?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'billing_enabled' => ['boolean'],
            'default_plan_id' => [Rule::requiredIf($this->boolean('billing_enabled')), 'nullable', 'exists:plans,id'],
            'default_billing_cycle' => [Rule::requiredIf($this->boolean('billing_enabled')), 'nullable', Rule::in(['monthly', 'quarterly', 'yearly'])],
            'default_grace_period_days' => [Rule::requiredIf($this->boolean('billing_enabled')), 'nullable', 'integer', 'min:0', 'max:365'],
            'default_discount_type' => ['required', Rule::in(['none', 'fixed', 'percentage'])],
            'default_discount_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'default_tax_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'default_plan_id.required_if' => 'Select a default service when organization billing is enabled.',
            'default_billing_cycle.required_if' => 'Select a default billing cycle when organization billing is enabled.',
            'default_grace_period_days.required_if' => 'Set a grace period when organization billing is enabled.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $billingEnabled = $this->boolean('billing_enabled');

        $this->merge([
            'billing_enabled' => $billingEnabled,
            'default_plan_id' => $billingEnabled ? $this->input('default_plan_id') : null,
            'default_billing_cycle' => $billingEnabled ? $this->input('default_billing_cycle') : null,
            'default_grace_period_days' => $billingEnabled ? $this->input('default_grace_period_days') : null,
            'default_discount_type' => $this->input('default_discount_type', 'none'),
            'default_discount_amount' => $this->input('default_discount_type') === 'none' ? 0 : $this->input('default_discount_amount', 0),
            'default_tax_percentage' => $this->input('default_tax_percentage', 0),
        ]);
    }
}
