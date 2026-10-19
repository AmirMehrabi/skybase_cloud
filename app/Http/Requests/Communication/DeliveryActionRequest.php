<?php

namespace App\Http\Requests\Communication;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission($this->routeIs('campaigns.*') ? 'campaigns.actions' : 'communications.actions') === true;
    }

    public function rules(): array
    {
        return ['action' => ['required', 'in:send,schedule,pause,resume,cancel,retry,duplicate,acknowledge,resolve'], 'scheduled_at' => ['nullable', 'required_if:action,schedule', 'date'], 'destination' => ['nullable', 'string', 'max:255'], 'channel' => ['nullable', 'in:email,sms']];
    }

    public function messages(): array
    {
        return ['scheduled_at.required_if' => 'Choose a future date and time in your tenant timezone.'];
    }
}
