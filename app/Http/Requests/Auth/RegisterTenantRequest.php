<?php

namespace App\Http\Requests\Auth;

use App\Rules\Turnstile;
use Closure;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;

class RegisterTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) config('app.cloud.enabled');
    }

    public function rules(): array
    {
        return [
            'cf-turnstile-response' => ['bail', 'required', 'string', 'max:2048', new Turnstile('signup')],
            'website' => ['prohibited'],
            'form_token' => ['bail', 'required', 'string', function (string $attribute, mixed $value, Closure $fail): void {
                try {
                    $payload = explode('|', Crypt::decryptString($value));
                    $age = now()->timestamp - (int) ($payload[1] ?? 0);
                    if (count($payload) !== 2 || ! hash_equals(hash('sha256', $this->session()->getId()), $payload[0]) || $age < 3 || $age > 7200) {
                        $fail('Please refresh this page, take a moment to complete the form, and try again.');
                    }
                } catch (DecryptException) {
                    $fail('Please refresh this page and try again.');
                }
            }],
            'company_name' => ['required', 'string', 'max:255'],
            'owner_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'timezone' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'cf-turnstile-response.required' => 'Please complete the CAPTCHA verification.',
            'website.prohibited' => 'Your submission could not be accepted.',
            'form_token.required' => 'Please refresh this page and try again.',
            'company_name.required' => 'The company name is required.',
            'owner_name.required' => 'The owner name is required.',
            'email.required' => 'The email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already registered.',
            'password.required' => 'The password is required.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
        ];
    }
}
