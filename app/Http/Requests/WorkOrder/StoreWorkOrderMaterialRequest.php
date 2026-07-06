<?php

namespace App\Http\Requests\WorkOrder;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkOrderMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('execute', $this->route('work_order')) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'direction' => ['required', Rule::in(['issued', 'installed', 'removed', 'returned'])],
            'sku' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'mac_address' => ['nullable', 'mac_address'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:10000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
