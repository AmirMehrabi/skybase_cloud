<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IpdrReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return ['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after:from']];
    }

    public function messages(): array
    {
        return ['to.after' => 'The end must be after the start of the flow reporting window.'];
    }
}
