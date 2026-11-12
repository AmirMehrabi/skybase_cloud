<?php

namespace App\Http\Requests\Acs;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return tenant()?->acs_enabled && $this->user()?->isOwner();
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['base_url' => rtrim((string) $this->input('base_url'), '/')]);
    }

    public function rules(): array
    {
        return [
            'automation_enabled' => ['required', 'boolean'],
            'name' => ['required', 'string', 'max:100'], 'base_url' => ['required', 'url', Rule::in(config('acs.allowed_endpoints'))],
            'username' => ['nullable', 'string', 'max:255'], 'password' => ['nullable', 'string', 'max:1000'], 'cwmp_url' => ['nullable', 'url:https', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return ['base_url.in' => 'Ask your SkyBase administrator to approve this ACS endpoint first.', 'cwmp_url.url' => 'Use an HTTPS address for device enrollment.'];
    }
}
