<?php

namespace App\Http\Requests\Acs;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeviceActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return tenant()?->acs_enabled && $this->user()?->hasPermission('acs.actions');
    }

    public function rules(): array
    {
        return ['operation' => ['required', Rule::in(['refresh', 'reboot', 'wifi'])], 'ssid_path' => ['required_if:operation,wifi', 'nullable', 'string', 'max:255'], 'ssid' => ['required_if:operation,wifi', 'nullable', 'string', 'max:32'], 'confirmed' => ['exclude_unless:operation,reboot', 'required', 'accepted']];
    }

    public function messages(): array
    {
        return ['confirmed.accepted' => 'Confirm that the customer can tolerate a brief disconnection.', 'ssid.max' => 'The Wi-Fi name must be at most 32 characters.'];
    }
}
