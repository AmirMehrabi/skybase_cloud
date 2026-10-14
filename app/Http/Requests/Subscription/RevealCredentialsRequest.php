<?php

namespace App\Http\Requests\Subscription;

use Illuminate\Foundation\Http\FormRequest;

class RevealCredentialsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ! config('security.password_encryption', false)
            && $this->user()?->hasPermission('subscriptions.credentials') === true;
    }

    public function rules(): array
    {
        return ['current_password' => ['required', 'string', 'current_password:web']];
    }

    public function messages(): array
    {
        return ['current_password.current_password' => 'The current password is incorrect.'];
    }
}
