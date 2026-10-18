<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class IpdrReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return ['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after:from'], 'analytics' => ['nullable', 'boolean'], 'source_ip' => ['nullable', 'ip'], 'destination_ip' => ['nullable', 'ip']];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isEmpty() && $this->filled('from') && $this->filled('to') && $this->date('from')->diffInSeconds($this->date('to')) > 366 * 86400) {
                $validator->errors()->add('to', 'Choose a time range of at most 366 days.');
            }
        }];
    }

    public function messages(): array
    {
        return ['to.after' => 'The end must be after the start of the flow reporting window.'];
    }
}
