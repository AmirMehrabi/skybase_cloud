<?php

namespace App\Http\Requests\Acs;

use Illuminate\Foundation\Http\FormRequest;

class AssignDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return tenant()?->acs_enabled && $this->user()?->hasPermission('acs.write');
    }

    public function rules(): array
    {
        return ['customer_id' => ['nullable', 'integer'], 'subscription_id' => ['nullable', 'integer'], 'ownership_verified' => ['accepted']];
    }

    public function messages(): array
    {
        return ['ownership_verified.accepted' => 'Confirm that this equipment belongs to your ISP before assigning it.'];
    }
}
