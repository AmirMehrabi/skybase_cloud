<?php

namespace App\Http\Requests\Acs;

use Illuminate\Foundation\Http\FormRequest;

class SaveProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return tenant()?->acs_enabled && $this->user()?->isOwner();
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:100'], 'parameters' => ['required', 'array', 'min:1', 'max:8'], 'parameters.*.path' => ['nullable', 'string', 'max:255'], 'parameters.*.value' => ['nullable', 'string', 'max:1000']];
    }

    public function messages(): array
    {
        return ['parameters.required' => 'Choose at least one writable parameter for this profile.'];
    }
}
