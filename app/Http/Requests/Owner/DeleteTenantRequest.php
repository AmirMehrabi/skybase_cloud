<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class DeleteTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('delete-owner-tenants') ?? false;
    }

    public function rules(): array
    {
        return ['confirmed' => ['required', 'accepted']];
    }

    public function messages(): array
    {
        return ['confirmed.required' => 'Please confirm tenant deletion.', 'confirmed.accepted' => 'Please confirm tenant deletion.'];
    }
}
