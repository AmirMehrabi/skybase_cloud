<?php

namespace App\Http\Requests\Setting;

use App\Support\Notifications\NotificationEventRegistry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNotificationSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('settings.write') === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'notifications_enabled' => ['nullable', 'boolean'],
            'in_app_enabled' => ['nullable', 'boolean'],
            'email_enabled' => ['nullable', 'boolean'],
            'sms_enabled' => ['nullable', 'boolean'],
            'events' => ['nullable', 'array'],
            'events.*' => ['nullable', 'boolean'],
            'event_keys' => ['nullable', 'array'],
            'event_keys.*' => ['string', Rule::in(array_keys(NotificationEventRegistry::events()))],
        ];
    }

    protected function prepareForValidation(): void
    {
        $eventKeys = array_keys(NotificationEventRegistry::events());
        $events = [];

        foreach ($eventKeys as $eventKey) {
            $events[$eventKey] = $this->boolean("events.{$eventKey}");
        }

        $this->merge([
            'notifications_enabled' => $this->boolean('notifications_enabled'),
            'in_app_enabled' => $this->boolean('in_app_enabled'),
            'email_enabled' => $this->boolean('email_enabled'),
            'sms_enabled' => $this->boolean('sms_enabled'),
            'events' => $events,
            'event_keys' => $eventKeys,
        ]);
    }
}
