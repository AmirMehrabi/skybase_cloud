<?php

namespace App\Http\Requests\Router;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRouterRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'vendor' => ['required', 'string', 'in:Mikrotik,Cisco,Juniper,Huawei'],
            'ip_address' => ['required', 'ip', 'unique:routers,ip_address,'.$this->route('router')],
            'api_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'api_username' => ['nullable', 'string', 'max:255'],
            'api_password' => ['nullable', 'string', 'max:255'],
            'ssh_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'location' => ['nullable', 'string', 'max:255'],
            'site' => ['nullable', 'string', 'max:255'],
            'timeout' => ['nullable', 'integer', 'min:1', 'max:300'],
            'enable_monitoring' => ['nullable', 'boolean'],
            'enable_provisioning' => ['nullable', 'boolean'],
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
            'name.required' => 'The router name is required.',
            'vendor.required' => 'The vendor field is required.',
            'vendor.in' => 'The vendor must be one of: Mikrotik, Cisco, Juniper, Huawei.',
            'ip_address.required' => 'The IP address is required.',
            'ip_address.ip' => 'Please enter a valid IP address.',
            'ip_address.unique' => 'A router with this IP address already exists.',
        ];
    }
}
