<?php

namespace App\Http\Requests\Admin\Tenant;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
            ->pluck('name')
            ->all();
        $user = $this->route('user');

        if ($user instanceof User && ! in_array($user->role, $roleNames, true)) {
            $roleNames[] = $user->role;
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$this->route('user')->id],
            'role' => ['required', Rule::in($roleNames)],
            'status' => ['required', 'in:active,inactive'],
            'user_group_id' => [
                'nullable',
                Rule::exists('user_groups', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The user name is required.',
            'email.required' => 'The email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already registered.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
            'role.required' => 'Please select a role for this user.',
            'role.in' => 'The selected role is invalid.',
            'status.required' => 'Please select a status for this user.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $user = $this->route('user');

        if (! $user instanceof User) {
            return;
        }

        $role = $this->has('role') ? $this->input('role') : $user->role;
        $tenantId = tenant_id() ?? $this->user()?->tenant_id;

        if ($tenantId && $this->has('role')) {
            Role::ensureDefaultsForTenant((string) $tenantId);
            $roleModel = Role::findForTenantRole((string) $tenantId, (string) $role);
            $role = $roleModel?->name ?? $role;
        }

        $this->merge([
            'role' => $role,
            'status' => $this->has('status') ? $this->input('status') : $user->status,
        ]);
    }
}
