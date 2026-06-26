<?php

namespace App\Http\Requests\Admin\Tenant;

use App\Models\Role;
use App\Support\Rbac\PermissionRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('roles.write') === true;
    }

    public function rules(): array
    {
        $tenantId = tenant_id() ?? $this->user()?->tenant_id;
        $role = $this->route('role');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')
                    ->where('tenant_id', $tenantId)
                    ->ignore($role instanceof Role ? $role->id : null),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(array_merge(['*'], array_keys(PermissionRegistry::allPermissions())))],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'نام نقش الزامی است.',
            'name.unique' => 'این نقش قبلا برای این سازمان ثبت شده است.',
            'permissions.*.in' => 'دسترسی انتخاب شده معتبر نیست.',
        ];
    }
}
