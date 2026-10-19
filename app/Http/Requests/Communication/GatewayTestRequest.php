<?php

namespace App\Http\Requests\Communication;

use Illuminate\Foundation\Http\FormRequest;

class GatewayTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('settings.actions') === true;
    }

    public function rules(): array
    {
        return ['action' => ['required', 'in:connection,send'], 'destination' => ['nullable', 'required_if:action,send', 'regex:/^\+?[0-9]{10,15}$/'], 'message' => ['nullable', 'required_if:action,send', 'string', 'max:1000']];
    }

    public function messages(): array
    {
        return ['destination.regex' => 'Enter a phone number with country code (10–15 digits).'];
    }
}
