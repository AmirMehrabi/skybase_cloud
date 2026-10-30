<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;

class StoreContactInquiryRequest extends FormRequest
{
    protected $errorBag = 'contactInquiry';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'website.prohibited' => 'Your submission could not be accepted.',
            'form_token.required' => 'Please refresh this page and try again.',
            'name.required' => 'Please tell us your name.',
            'email.required' => 'Please share your email address.',
            'email.email' => 'Please enter a valid email address.',
            'phone.required' => 'Please share a phone number so we can get back to you.',
            'subject.required' => 'Please choose a short subject.',
            'message.required' => 'Please add a few details so we can help you well.',
            'message.min' => 'Please add a little more detail to your message.',
        ];
    }
}
