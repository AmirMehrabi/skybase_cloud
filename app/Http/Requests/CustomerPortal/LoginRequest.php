<?php

namespace App\Http\Requests\CustomerPortal;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'tenant' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tenant.required' => 'Please enter your ISP tenant code.',
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
            'password.required' => 'Please enter your password.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tenant' => str($this->input('tenant'))->trim()->lower()->toString(),
            'email' => str($this->input('email'))->trim()->lower()->toString(),
            'remember' => $this->boolean('remember'),
        ]);
    }
}
