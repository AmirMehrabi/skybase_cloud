<?php

namespace App\Http\Requests\Site;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSiteRequest extends FormRequest
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
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sites', 'code')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'The site code is required.',
            'code.unique' => 'A site with this code already exists.',
            'name.required' => 'The site name is required.',
            'latitude.required' => 'The site latitude is required.',
            'latitude.between' => 'Latitude must be between -90 and 90.',
            'longitude.required' => 'The site longitude is required.',
            'longitude.between' => 'Longitude must be between -180 and 180.',
        ];
    }
}
