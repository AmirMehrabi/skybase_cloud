<?php

namespace App\Http\Requests;

use App\Rules\Turnstile;
use Illuminate\Foundation\Http\FormRequest;

class StoreDemoRequestRequest extends FormRequest
{
    protected $errorBag = 'demoRequest';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cf-turnstile-response' => ['bail', 'required', 'string', 'max:2048', new Turnstile],
            'requested_plan' => ['required', 'string', 'max:255'],
            'business_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'country' => ['required', 'string', 'max:255'],
            'company_website' => ['nullable', 'url', 'max:255'],
            'customer_count' => ['required', 'integer', 'min:1', 'max:1000000'],
            'current_system' => ['nullable', 'string', 'max:255'],
            'deployment_timeline' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
            'source_page' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'cf-turnstile-response.required' => 'Please complete the CAPTCHA verification.',
            'requested_plan.required' => 'Please choose the plan you want a demo for.',
            'business_name.required' => 'Please enter your business name.',
            'contact_name.required' => 'Please enter the best contact person.',
            'email.required' => 'Please share your work email address.',
            'email.email' => 'Please enter a valid email address.',
            'phone.required' => 'Please share a phone number so we can arrange the setup.',
            'country.required' => 'Please tell us where the business operates.',
            'company_website.url' => 'Please enter a valid website URL.',
            'customer_count.required' => 'Please tell us roughly how many customers you manage.',
        ];
    }
}
