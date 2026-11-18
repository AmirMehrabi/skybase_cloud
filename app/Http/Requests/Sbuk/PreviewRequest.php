<?php

namespace App\Http\Requests\Sbuk;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PreviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'dataset_token' => ['required', 'uuid'],
            'filters' => ['sometimes', 'array:employment_type,unit_title,employment_status,search'],
            'filters.employment_type' => ['nullable', 'string', 'max:255'],
            'filters.unit_title' => ['nullable', 'string', 'max:255'],
            'filters.employment_status' => ['nullable', 'string', 'max:255'],
            'filters.search' => ['nullable', 'string', 'max:255'],
            'plan_id' => ['required', 'integer'],
            'router_id' => ['required', 'integer'],
            'ip_pool_id' => ['required', 'integer'],
            'pppoe_username_source' => ['sometimes', 'required', 'in:mobile,national_id'],
            'pppoe_password_source' => ['sometimes', 'required', 'in:mobile,national_id'],
            'existing_customers' => ['sometimes', 'required', 'in:skip,update'],
            'existing_subscriptions' => ['sometimes', 'required', 'in:skip,update'],
        ];
    }
}
