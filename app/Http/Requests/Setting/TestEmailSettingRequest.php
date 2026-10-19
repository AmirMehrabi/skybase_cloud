<?php

namespace App\Http\Requests\Setting;

use Illuminate\Foundation\Http\FormRequest;

class TestEmailSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('settings.actions') === true;
    }

    public function rules(): array
    {
        return [
            'direction' => ['required', 'in:incoming,outgoing'],
            'action' => ['nullable', 'in:connection,send'],
            'destination' => ['nullable', 'required_if:action,send', 'email', 'max:255'],
        ];
    }
}
