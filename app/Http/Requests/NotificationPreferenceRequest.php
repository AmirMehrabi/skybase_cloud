<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class NotificationPreferenceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->tenant_id === tenant_id();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'notifications_enabled' => ['nullable', 'boolean'],
            'in_app_enabled' => ['nullable', 'boolean'],
            'email_enabled' => ['nullable', 'boolean'],
            'sms_enabled' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'notifications_enabled' => $this->boolean('notifications_enabled'),
            'in_app_enabled' => $this->boolean('in_app_enabled'),
            'email_enabled' => $this->boolean('email_enabled'),
            'sms_enabled' => $this->boolean('sms_enabled'),
        ]);
    }
}
