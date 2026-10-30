<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettleDummyPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isOwner() ?? false;
    }

    public function rules(): array
    {
        return ['outcome' => ['required', Rule::in(['success', 'cancelled', 'failed'])]];
    }

    public function messages(): array
    {
        return ['action.in' => 'Choose a supported account action.', 'reason.required' => 'A reason is required for the audit trail.', 'package_key.in' => 'Choose a published SkyBase package.', 'outcome.in' => 'Choose a supported payment outcome.'];
    }
}
