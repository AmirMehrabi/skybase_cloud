<?php

namespace App\Http\Requests\WorkOrder;

class UpdateWorkOrderRequest extends StoreWorkOrderRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('work_order')) ?? false;
    }
}
