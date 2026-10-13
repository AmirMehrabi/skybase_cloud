<?php

namespace App\Http\Requests\Subscription;

use App\Models\Subscription;
use Illuminate\Foundation\Http\FormRequest;

class RenewSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $subscription = $this->route('subscription');

        return $subscription instanceof Subscription
            && $this->user()?->tenant_id === $subscription->tenant_id
            && $this->user()->hasPermission('subscriptions.actions');
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'renewal_token' => ['required', 'string', 'max:8192'],
            'confirm_renewal' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'renewal_token.required' => 'Review a renewal preview before applying the renewal.',
            'renewal_token.max' => 'The renewal preview is invalid. Please review a fresh preview.',
            'confirm_renewal.accepted' => 'Confirm the renewal charge and replacement of the current cycle.',
        ];
    }
}
