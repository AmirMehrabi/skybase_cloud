<?php

namespace App\Http\Requests\Admin\Tenant;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        $tenantId = tenant_id() ?? $this->user()?->tenant_id;
        if ($tenantId) {
            Role::ensureDefaultsForTenant((string) $tenantId);
        }
        $roleNames = Role::query()
            ->where('tenant_id', $tenantId)
            ->whereRaw('LOWER(name) != ?', ['owner'])
            ->pluck('name')
            ->all();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in($roleNames)],
            'status' => ['required', 'in:active,inactive'],
            'send_invite' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The user name is required.',
            'email.required' => 'The email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already registered.',
            'password.required' => 'The password is required.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
            'role.required' => 'Please select a role for this user.',
            'role.in' => 'The selected role is invalid.',
            'status.required' => 'Please select a status for this user.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $tenantId = tenant_id() ?? $this->user()?->tenant_id;

        if (! $tenantId || ! $this->has('role')) {
            return;
        }

        Role::ensureDefaultsForTenant((string) $tenantId);
        $role = Role::findForTenantRole((string) $tenantId, (string) $this->input('role'));

        if ($role) {
            $this->merge(['role' => $role->name]);
        }
    }
}
