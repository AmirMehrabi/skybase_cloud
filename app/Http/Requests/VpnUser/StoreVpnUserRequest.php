<?php

namespace App\Http\Requests\VpnUser;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVpnUserRequest extends FormRequest
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
        $tenantId = (string) (tenant()?->id ?? $this->user()?->tenant_id);

        return [
            'username' => [
                'required',
                'string',
                'max:64',
                'alpha_dash:ascii',
                Rule::unique('vpn_users', 'username')->where('tenant_id', $tenantId),
            ],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
            'active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.required' => 'The VPN username is required.',
            'username.alpha_dash' => 'The VPN username may only contain letters, numbers, dashes, and underscores.',
            'username.unique' => 'This VPN username already exists for this tenant.',
            'password.required' => 'The VPN password is required.',
            'password.confirmed' => 'The VPN password confirmation does not match.',
        ];
    }
}
