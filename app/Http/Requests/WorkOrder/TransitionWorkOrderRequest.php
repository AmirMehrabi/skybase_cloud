<?php

namespace App\Http\Requests\WorkOrder;

use App\Enums\WorkOrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = match ($this->input('status')) {
            WorkOrderStatus::Completed->value => 'complete',
            WorkOrderStatus::Cancelled->value => 'cancel',
            default => 'execute',
        };

        return $this->user()?->can($ability, $this->route('work_order')) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(WorkOrderStatus::class)],
            'blocked_reason' => ['nullable', 'string', 'max:5000'],
            'follow_up_at' => ['nullable', 'date'],
            'completion_notes' => ['nullable', 'string', 'max:10000'],
            'cancellation_reason' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
