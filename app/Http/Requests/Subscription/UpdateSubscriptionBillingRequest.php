<?php

namespace App\Http\Requests\Subscription;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionBillingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'billing_enabled' => ['required', 'boolean'],
            'auto_suspension_enabled' => ['required', 'boolean'],
            'grace_period_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'next_billing_date' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'billing_enabled.required' => 'Choose whether billing is enabled.',
            'auto_suspension_enabled.required' => 'Choose whether automatic suspension is enabled.',
            'grace_period_days.max' => 'The grace period may not exceed 365 days.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $billingEnabled = $this->boolean('billing_enabled');

        $this->merge([
            'billing_enabled' => $billingEnabled,
            'auto_suspension_enabled' => $billingEnabled
                && $this->boolean('auto_suspension_enabled'),
        ]);
    }
}
