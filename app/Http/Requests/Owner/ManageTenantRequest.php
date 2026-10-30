<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManageTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCentralAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->route('action')) {
            $this->merge(['action' => $this->route('action')]);
        }
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['contacts', 'package', 'trial', 'limit', 'note', 'suspend', 'activate', 'archive'])],
            'reason' => ['required', 'string', 'max:2000'],
            'name' => ['exclude_unless:action,contacts', 'required_if:action,contacts', 'string', 'max:255'],
            'company_name' => ['exclude_unless:action,contacts', 'required_if:action,contacts', 'string', 'max:255'],
            'email' => ['exclude_unless:action,contacts', 'required_if:action,contacts', 'email', 'max:255'],
            'phone' => ['exclude_unless:action,contacts', 'nullable', 'string', 'max:30'],
            'package_key' => ['exclude_unless:action,package', 'required_if:action,package', Rule::in(array_keys(config('skybase_billing.packages')))],
            'trial_ends_at' => ['exclude_unless:action,trial', 'required_if:action,trial', 'date', 'after:now'],
            'subscriber_limit' => ['exclude_unless:action,limit', 'required_if:action,limit', 'integer', 'min:0', 'max:10000000'],
        ];
    }

    public function messages(): array
    {
        return ['action.in' => 'Choose a supported account action.', 'reason.required' => 'A reason is required for the audit trail.', 'package_key.in' => 'Choose a published SkyBase package.', 'outcome.in' => 'Choose a supported payment outcome.'];
    }
}
