<?php

namespace App\Http\Requests\Communication;

use Illuminate\Foundation\Http\FormRequest;

class IncidentPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('communications.write') === true;
    }

    public function rules(): array
    {
        return ['enabled' => ['nullable', 'boolean'], 'failure_threshold' => ['required', 'integer', 'between:1,20'], 'recovery_threshold' => ['required', 'integer', 'between:1,20'], 'escalation_minutes' => ['nullable', 'string', 'regex:/^\s*(\d+\s*(,\s*\d+\s*)*)?$/'], 'quiet_start' => ['nullable', 'date_format:H:i', 'required_with:quiet_end'], 'quiet_end' => ['nullable', 'date_format:H:i', 'required_with:quiet_start', 'different:quiet_start'], 'maintenance_until' => ['nullable', 'date']];
    }

    public function messages(): array
    {
        return ['escalation_minutes.regex' => 'Enter escalation delays in minutes, separated by commas.'];
    }
}
