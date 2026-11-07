<?php

namespace App\Http\Requests\Site;

use App\Services\CoverageGeometry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCoverageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'version' => ['required', 'integer', 'min:0'], 'name' => ['required', 'string', 'max:120'],
            'technology' => ['required', Rule::in(['wireless', 'fiber', 'other'])],
            'evidence' => ['required', Rule::in(['declared', 'estimated', 'verified'])],
            'availability' => ['required', Rule::in(['available', 'survey_required', 'constrained', 'unavailable'])],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'access_point_id' => ['nullable', 'integer', Rule::exists('access_points', 'id')->where(fn ($q) => $q->where('tenant_id', tenant()->id)->where('site_id', $this->route('site')->id))],
            'notes' => ['nullable', 'string', 'max:2000'], 'verified_on' => ['nullable', 'date', 'before_or_equal:today', 'required_if:evidence,verified'],
            'geometry' => ['required', 'array', function ($attribute, $value, $fail): void {
                if (! app(CoverageGeometry::class)->valid($value)) {
                    $fail('Draw a valid, non-intersecting polygon. Check exclusions and coordinates.');
                }
            }],
            'shape' => ['nullable', 'array'], 'shape.type' => ['required_with:shape', Rule::in(['polygon', 'circle', 'sector'])],
            'shape.radius' => ['nullable', 'numeric', 'min:1', 'max:100000'], 'shape.azimuth' => ['nullable', 'numeric', 'between:0,360'], 'shape.angle' => ['nullable', 'numeric', 'between:1,360'],
        ];
    }

    public function messages(): array
    {
        return ['name.required' => 'Give this coverage area a name.', 'version.required' => 'Reload this area before saving.', 'verified_on.required_if' => 'Add the date this coverage was verified.'];
    }
}
