<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RadiusAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $token = config('radius_allocation.tokens.'.$this->route('tenantId'));

        return is_string($token) && strlen($token) >= 32 && hash_equals($token, $this->bearerToken() ?? '');
    }

    public function rules(): array
    {
        return [
            'username' => [$this->routeIs('radius.reset') ? 'nullable' : 'required', 'string', 'max:255'],
            'router_id' => ['required', 'integer'],
            'reset_at' => [$this->routeIs('radius.reset') ? 'required' : 'nullable', 'date', 'before_or_equal:now'],
            'session_key' => [$this->routeIs('radius.allocate') ? 'required' : 'nullable', 'string', 'max:255'],
            'lease_token' => [$this->routeIs('radius.accounting') ? 'required' : 'nullable', 'uuid'],
            'event' => [$this->routeIs('radius.accounting') ? 'required' : 'nullable', 'in:start,interim,stop'],
        ];
    }

    public function messages(): array
    {
        return [
            'session_key.required' => 'A stable session identifier is required for IP allocation.',
            'lease_token.required' => 'Accounting must include the lease token returned during allocation.',
        ];
    }
}
