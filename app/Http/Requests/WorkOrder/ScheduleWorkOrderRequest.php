<?php

namespace App\Http\Requests\WorkOrder;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('schedule', $this->route('work_order')) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'reschedule_reason' => [$this->route('work_order')?->appointments()->exists() ? 'required' : 'nullable', 'string', 'max:2000'],
        ];
    }
}
