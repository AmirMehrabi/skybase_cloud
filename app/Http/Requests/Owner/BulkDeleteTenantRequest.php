<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkDeleteTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('delete-owner-tenants') ?? false;
    }

    public function rules(): array
    {
        return [
            'tenant_ids' => ['required', 'array', 'min:1', 'max:20'],
            'tenant_ids.*' => ['required', 'string', 'distinct', Rule::exists('tenants', 'id')->whereNull('deleted_at')],
            'confirmed' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_ids.required' => 'Select at least one tenant to delete.',
            'tenant_ids.min' => 'Select at least one tenant to delete.',
            'tenant_ids.max' => 'Select no more than 20 tenants at a time.',
            'tenant_ids.*.exists' => 'One or more selected tenants are no longer available.',
            'confirmed.required' => 'Please confirm tenant deletion.',
            'confirmed.accepted' => 'Please confirm tenant deletion.',
        ];
    }
}
