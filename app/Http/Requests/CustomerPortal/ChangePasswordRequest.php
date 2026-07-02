<?php

namespace App\Http\Requests\CustomerPortal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('customer') !== null;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password:customer'],
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'The current password is required.',
            'current_password.current_password' => 'The current password is incorrect.',
            'password.required' => 'The new password is required.',
            'password.confirmed' => 'The password confirmation does not match.',
        ];
    }
}
