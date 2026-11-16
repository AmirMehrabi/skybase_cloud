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
            'plan_id' => ['required', 'integer'],
            'router_id' => ['required', 'integer'],
            'ip_pool_id' => ['required', 'integer'],
            'pppoe_username_source' => ['sometimes', 'required', 'in:mobile,national_id'],
            'pppoe_password_source' => ['sometimes', 'required', 'in:mobile,national_id'],
        ];
    }
}
