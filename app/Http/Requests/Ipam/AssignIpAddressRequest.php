<?php

namespace App\Http\Requests\Ipam;

use App\Models\Customer;
use App\Models\Subscription;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignIpAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', Rule::exists(Customer::class, 'id')->where('tenant_id', $this->user()?->tenant_id)],
            'subscription_id' => ['required', 'integer', Rule::exists(Subscription::class, 'id')->where('tenant_id', $this->user()?->tenant_id)],
            'mac_address' => ['nullable', 'mac_address'],
        ];
    }
}
