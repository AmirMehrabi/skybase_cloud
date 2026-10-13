<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class MultiFactorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web') !== null;
    }

    public function rules(): array
    {
        return [
            'code' => ['nullable', 'required_without:recovery_code', 'string', 'regex:/^[0-9]{6}$/D'],
            'recovery_code' => ['nullable', 'required_without:code', 'string', 'max:64'],
            'current_password' => [$this->routeIs('mfa.confirm') ? 'required' : 'nullable', 'string', 'current_password:web'],
        ];
    }

    public function messages(): array
    {
        return ['code.regex' => 'Enter the six-digit authenticator code.', 'current_password.current_password' => 'The current password is incorrect.'];
    }
}
