<?php

namespace App\Http\Requests\Communication;

use Illuminate\Foundation\Http\FormRequest;

class SmsGatewayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('settings.write') === true;
    }

    public function rules(): array
    {
        $rules = ['provider' => ['required', 'in:none,twilio,smsportal,kavenegar'], 'rate_per_minute' => ['required', 'integer', 'between:1,1000'], 'smsportal.test_mode' => ['nullable', 'boolean']];
        foreach (['twilio.sid', 'twilio.token', 'twilio.sender', 'smsportal.client_id', 'smsportal.secret', 'kavenegar.api_key', 'kavenegar.sender', 'kavenegar.credentials_template', 'kavenegar.reset_template'] as $field) {
            $rules[$field] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return ['provider.in' => 'Choose Twilio, SMSPortal, Kavenegar or Disabled.'];
    }
}
