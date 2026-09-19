<?php

namespace App\Http\Requests\Report;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinancialReportRequest extends FormRequest
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
            'period' => ['nullable', Rule::in(['this_month', 'last_month', 'quarter', 'year', 'custom'])],
            'from' => ['nullable', 'required_if:period,custom', 'date'],
            'to' => ['nullable', 'required_if:period,custom', 'date', 'after_or_equal:from'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'from.required_if' => 'Choose a start date for a custom period.',
            'to.required_if' => 'Choose an end date for a custom period.',
            'to.after_or_equal' => 'The end date must be on or after the start date.',
        ];
    }
}
