<?php

namespace App\Http\Requests\Setting;

use App\Services\PayfastGateway;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentGatewayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tenant_id === tenant()?->id && $this->user()?->hasPermission('settings.write');
    }

    public function rules(): array
    {
        $rules = ['enabled' => ['required', 'boolean'], 'environment' => ['required', Rule::in(['sandbox', 'live'])], 'label' => ['required', 'string', 'max:80'], 'methods' => ['nullable', 'array'], 'methods.*' => [Rule::in(array_keys(PayfastGateway::METHODS))]];
        foreach (['sandbox', 'live'] as $environment) {
            $rules[$environment.'.merchant_id'] = ['nullable', 'digits:8'];
            $rules[$environment.'.merchant_key'] = ['nullable', 'string', 'max:100'];
            $rules[$environment.'.passphrase'] = ['nullable', 'string', 'max:100'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return ['environment.in' => 'Choose sandbox or live mode.', 'methods.*.in' => 'Choose a supported PayFast payment method.'];
    }
}
