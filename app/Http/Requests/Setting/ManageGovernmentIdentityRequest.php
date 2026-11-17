<?php

namespace App\Http\Requests\Setting;

use Illuminate\Foundation\Http\FormRequest;

class ManageGovernmentIdentityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin() && $this->user()->canAccessTenant((string) tenant()->id);
    }

    public function rules(): array
    {
        return [
            'account_type' => ['required', 'in:web,customer'],
            'account_email' => ['required', 'email', 'max:255'],
            'operation' => ['required', 'in:enroll,revoke'],
            'government_national_id' => ['required_if:operation,enroll', 'nullable', 'string', 'regex:/\A[0-9۰-۹٠-٩]{10}\z/u'],
        ];
    }

    public function messages(): array
    {
        return ['government_national_id.regex' => 'Enter a 10-digit national ID, including any leading zero.'];
    }
}
