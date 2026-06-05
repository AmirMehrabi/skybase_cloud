<?php

namespace App\Http\Requests\Customer;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteCustomersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'selection_mode' => ['required', 'in:selected,all'],
            'ids' => ['required_if:selection_mode,selected', 'nullable', 'array'],
            'ids.*' => ['integer', 'exists:customers,id'],
            'excluded_ids' => ['nullable', 'array'],
            'excluded_ids.*' => ['integer', 'exists:customers,id'],
            'search' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'plan' => ['nullable', 'string'],
            'site' => ['nullable', 'string'],
            'router' => ['nullable', 'string'],
            'organization' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'selection_mode.required' => 'Please choose a selection mode.',
            'selection_mode.in' => 'The selected bulk delete mode is invalid.',
            'ids.required_if' => 'Please select at least one customer to delete.',
            'ids.*.exists' => 'One or more selected customers are no longer available.',
            'excluded_ids.*.exists' => 'One or more excluded customers are no longer available.',
        ];
    }
}
