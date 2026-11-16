<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GolestanSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() && (string) $this->user()->tenant_id === (string) tenant()?->id;
    }

    public function rules(): array
    {
        if ($this->routeIs('golestan.sync.start')) {
            return ['preview_token' => ['required', 'uuid']];
        }

        return [
            'search_token' => ['required', 'uuid'],
            'rows' => ['required', 'array', 'min:1', 'max:500'],
            'rows.*' => ['required', 'integer', 'min:0', 'distinct'],
            'plan_id' => ['required', 'integer'],
            'router_id' => ['required', 'integer'],
            'ip_management' => ['required', 'in:router,system'],
            'ip_pool_id' => ['exclude_unless:ip_management,system', 'required', 'integer'],
            'subscriptions' => ['sometimes', 'array', 'max:500'],
            'subscriptions.*' => ['nullable', 'integer', 'min:0'],
            'addresses' => ['exclude_unless:ip_management,system', 'sometimes', 'array', 'max:500'],
            'addresses.*' => ['nullable', 'ipv4', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'rows.required' => 'Select at least one student.',
            'rows.max' => 'Select up to 500 students per sync.',
            'ip_pool_id.required' => 'Select an IP pool for system-managed addresses.',
            'addresses.*.distinct' => 'Enter a different IP address for each student.',
        ];
    }
}
