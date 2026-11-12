<?php

namespace App\Http\Requests\Billing;

use App\Services\PayfastGateway;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('customer') !== null;
    }

    public function rules(): array
    {
        return ['method' => ['nullable', 'string', Rule::in(array_keys(PayfastGateway::METHODS))]];
    }

    public function messages(): array
    {
        return ['method.in' => 'Choose a supported payment method.'];
    }
}
