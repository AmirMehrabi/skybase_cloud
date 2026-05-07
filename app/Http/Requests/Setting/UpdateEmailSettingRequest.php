<?php

namespace App\Http\Requests\Setting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmailSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'incoming_active' => ['nullable', 'boolean'],
            'incoming_protocol' => ['required', 'in:imap,pop3'],
            'incoming_host' => ['nullable', 'string', 'max:255'],
            'incoming_port' => ['nullable', 'integer', 'between:1,65535'],
            'incoming_encryption' => ['required', 'in:none,ssl,tls'],
            'incoming_username' => ['nullable', 'string', 'max:255'],
            'incoming_password' => ['nullable', 'string', 'max:255'],
            'incoming_mailbox' => ['nullable', 'string', 'max:255'],

            'outgoing_active' => ['nullable', 'boolean'],
            'outgoing_host' => ['nullable', 'string', 'max:255'],
            'outgoing_port' => ['nullable', 'integer', 'between:1,65535'],
            'outgoing_encryption' => ['required', 'in:none,ssl,tls'],
            'outgoing_username' => ['nullable', 'string', 'max:255'],
            'outgoing_password' => ['nullable', 'string', 'max:255'],
            'outgoing_from_email' => ['nullable', 'email', 'max:255'],
            'outgoing_from_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'incoming_protocol' => 'incoming protocol',
            'incoming_host' => 'incoming host',
            'incoming_port' => 'incoming port',
            'incoming_encryption' => 'incoming encryption',
            'incoming_username' => 'incoming username',
            'incoming_password' => 'incoming password',
            'incoming_mailbox' => 'incoming mailbox',
            'outgoing_host' => 'outgoing host',
            'outgoing_port' => 'outgoing port',
            'outgoing_encryption' => 'outgoing encryption',
            'outgoing_username' => 'outgoing username',
            'outgoing_password' => 'outgoing password',
            'outgoing_from_email' => 'from email',
            'outgoing_from_name' => 'from name',
        ];
    }
}
