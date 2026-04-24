<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please tell us your name.',
            'email.required' => 'Please share your email address.',
            'email.email' => 'Please enter a valid email address.',
            'subject.required' => 'Please choose a short subject.',
            'message.required' => 'Please add a few details so we can help you well.',
            'message.min' => 'Please add a little more detail to your message.',
        ];
    }
}
