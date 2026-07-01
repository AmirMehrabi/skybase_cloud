<?php

namespace App\Http\Requests\Subscription;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowSubscriptionRequest extends FormRequest
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
            'tab' => ['nullable', Rule::in(['overview', 'usage', 'auth', 'billing', 'contract', 'invoices', 'activity'])],
            'usage_view' => ['nullable', Rule::in(['table', 'chart'])],
            'usage_page' => ['nullable', 'integer', 'min:1'],
            'usage_per_page' => ['nullable', Rule::in([10, 25, 50, 100])],
            'session_started_from' => ['nullable', 'date'],
            'session_stopped_to' => ['nullable', 'date', 'after_or_equal:session_started_from'],
            'session_status' => ['nullable', Rule::in(['online', 'offline'])],
            'session_nas_ip' => ['nullable', 'string', 'max:255'],
            'session_ip_address' => ['nullable', 'string', 'max:255'],
            'session_terminate_cause' => ['nullable', 'string', 'max:255'],
            'usage_chart_range' => ['nullable', Rule::in(['daily', 'weekly', 'monthly', 'custom'])],
            'usage_chart_from' => ['nullable', 'required_if:usage_chart_range,custom', 'date'],
            'usage_chart_to' => ['nullable', 'required_if:usage_chart_range,custom', 'date', 'after_or_equal:usage_chart_from'],
            'radpostauth_page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'session_stopped_to.after_or_equal' => 'The stop date must be on or after the start date.',
            'usage_chart_from.required_if' => 'Choose a start date for a custom chart range.',
            'usage_chart_to.required_if' => 'Choose an end date for a custom chart range.',
            'usage_chart_to.after_or_equal' => 'The chart end date must be on or after its start date.',
        ];
    }
}
