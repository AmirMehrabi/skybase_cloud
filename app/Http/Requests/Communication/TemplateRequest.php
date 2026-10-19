<?php

namespace App\Http\Requests\Communication;

use App\Services\MessageTemplateService;
use App\Support\Notifications\NotificationEventRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('communications.write') === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'], 'event_key' => ['nullable', Rule::in(array_keys(NotificationEventRegistry::events()))],
            'recipient_type' => ['required', 'in:customer,admin,both'], 'enabled' => ['nullable', 'boolean'],
            'channels' => ['required', 'array', 'min:1'], 'channels.*' => ['in:email,sms', 'distinct'],
            'subject' => ['nullable', 'required_if:channels.*,email', 'string', 'max:255'],
            'email_body' => ['nullable', 'string', 'max:50000'], 'sms_body' => ['nullable', 'string', 'max:4000'],
            'sms_mode' => ['required', 'in:plain,lookup'], 'lookup_template' => ['nullable', 'required_if:sms_mode,lookup', 'regex:/^[a-zA-Z0-9_-]+$/', 'max:100'],
            'lookup_tokens' => ['nullable', 'array:token,token2,token3,token10,token20'], 'lookup_tokens.*' => ['nullable', Rule::in(MessageTemplateService::VARIABLES)],
            'recipients' => ['nullable', 'array', 'max:100'], 'recipients.*.channel' => ['required', 'in:email,sms'],
            'recipients.*.destination' => ['required', 'string', 'max:255'], 'recipients.*.stage' => ['required', 'integer', 'between:0,5'],
            'recipient_lines' => ['nullable', 'string', 'max:10000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $recipients = [];
        foreach (preg_split('/\r?\n/', $this->input('recipient_lines', ''), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $line) {
            $parts = array_map('trim', explode(',', $line));
            $recipients[] = ['channel' => $parts[0] ?? '', 'destination' => $parts[1] ?? '', 'stage' => $parts[2] ?? 0];
        }
        $this->merge(['enabled' => $this->boolean('enabled'), 'recipients' => $recipients, 'lookup_tokens' => array_filter($this->input('lookup_tokens', []))]);
    }

    public function messages(): array
    {
        return ['event_key.in' => 'Choose a supported application event.', 'lookup_template.required_if' => 'Enter the approved Kavenegar Lookup template name.'];
    }
}
