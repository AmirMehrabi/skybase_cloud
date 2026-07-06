<?php

namespace App\Http\Requests\WorkOrder;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkOrderTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $workOrder = $this->route('work_order');
        $task = $this->route('task');

        return $this->user()?->can('execute', $workOrder)
            && (int) $task?->work_order_id === (int) $workOrder?->id;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['status' => ['required', Rule::in(['pending', 'completed', 'skipped'])], 'result' => ['nullable', 'string', 'max:5000']];
    }
}
