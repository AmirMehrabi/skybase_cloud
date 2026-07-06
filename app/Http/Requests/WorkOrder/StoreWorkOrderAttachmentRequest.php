<?php

namespace App\Http\Requests\WorkOrder;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkOrderAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('work_order')) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'category' => ['required', Rule::in(['evidence', 'before', 'after', 'survey', 'acceptance', 'document'])],
            'attachment' => ['required', 'file', 'max:15360', 'mimes:jpg,jpeg,png,webp,pdf,txt,csv'],
        ];
    }
}
