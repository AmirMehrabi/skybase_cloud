<?php

namespace App\Http\Requests\Report;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UsageReportRequest extends FormRequest
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
            'range' => ['nullable', Rule::in(['today', 'week', 'month', 'quarter', 'year', 'custom'])],
            'from' => ['nullable', 'required_if:range,custom', 'date'],
            'to' => ['nullable', 'required_if:range,custom', 'date', 'after_or_equal:from'],
            'customer_id' => ['nullable', 'integer'],
            'plan_id' => ['nullable', 'integer'],
            'router_id' => ['nullable', 'integer'],
            'group_by' => ['nullable', Rule::in(['day', 'week', 'month', 'quarter'])],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'from.required_if' => 'Choose a start date for a custom range.',
            'to.required_if' => 'Choose an end date for a custom range.',
            'to.after_or_equal' => 'The end date must be on or after the start date.',
        ];
    }
}
