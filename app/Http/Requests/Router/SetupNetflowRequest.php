<?php

namespace App\Http\Requests\Router;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SetupNetflowRequest extends FormRequest
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
            'netflow_enabled' => ['required', 'boolean'],
            'netflow_collector_host' => [Rule::requiredIf(fn (): bool => $this->boolean('netflow_enabled')), 'nullable', 'string', 'max:255'],
            'netflow_collector_port' => [Rule::requiredIf(fn (): bool => $this->boolean('netflow_enabled')), 'integer', 'min:1', 'max:65535'],
            'netflow_version' => [Rule::requiredIf(fn (): bool => $this->boolean('netflow_enabled')), 'integer', Rule::in([5, 9])],
            'netflow_interfaces' => [Rule::requiredIf(fn (): bool => $this->boolean('netflow_enabled')), 'nullable', 'string', 'max:255'],
            'netflow_sampling_interval' => [Rule::requiredIf(fn (): bool => $this->boolean('netflow_enabled')), 'integer', 'min:1', 'max:1000000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'netflow_collector_host.required' => 'The NetFlow collector host is required when NetFlow is enabled.',
            'netflow_collector_port.required' => 'The NetFlow collector port is required when NetFlow is enabled.',
            'netflow_version.in' => 'NetFlow version must be 5 or 9.',
        ];
    }
}
