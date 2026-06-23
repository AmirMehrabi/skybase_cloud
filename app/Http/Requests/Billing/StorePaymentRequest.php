<?php

namespace App\Http\Requests\Billing;

use App\Models\Invoice;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'invoice_id' => [
                'required',
                Rule::exists('invoices', 'id')
                    ->where('tenant_id', tenant_id() ?? auth()->user()?->tenant_id),
            ],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'invoice_id.required' => 'The invoice is required.',
            'invoice_id.exists' => 'The selected invoice is invalid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $invoice = $this->route('invoice');

        if ($invoice instanceof Invoice) {
            $this->merge([
                'invoice_id' => $invoice->id,
            ]);
        }
    }
}
