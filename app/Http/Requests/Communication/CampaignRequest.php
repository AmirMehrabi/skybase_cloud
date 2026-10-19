<?php

namespace App\Http\Requests\Communication;

use App\Models\CommunicationTemplate;
use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('campaigns.write') === true;
    }

    protected function prepareForValidation(): void
    {
        $filters = $this->input('filters', []);
        if (is_string($filters['customer_ids'] ?? null)) {
            $filters['customer_ids'] = array_values(array_filter(array_map('trim', explode(',', $filters['customer_ids']))));
        }
        $this->merge(['filters' => $filters]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'], 'purpose' => ['required', 'in:service,promotional'],
            'channels' => ['required', 'array', 'min:1'], 'channels.*' => ['in:email,sms', 'distinct'],
            'template_id' => ['nullable', Rule::exists(CommunicationTemplate::class, 'id')->where('tenant_id', tenant()->id)],
            'subject' => ['nullable', 'string', 'max:255'], 'email_body' => ['nullable', 'string', 'max:50000'], 'sms_body' => ['nullable', 'string', 'max:4000'],
            'audience_mode' => ['required', 'in:snapshot,dynamic'], 'save_audience_name' => ['nullable', 'string', 'max:255'],
            'filters' => ['required', 'array:search,status,organization,router_id,plan_id,site,subscription_status,connection_status,customer_ids'],
            'filters.search' => ['nullable', 'string', 'max:255'], 'filters.status' => ['nullable', 'in:active,inactive,suspended'],
            'filters.organization' => ['nullable', Rule::exists('organizations', 'id')->where('tenant_id', tenant()->id)],
            'filters.router_id' => ['nullable', Rule::exists('routers', 'id')->where('tenant_id', tenant()->id)],
            'filters.plan_id' => ['nullable', Rule::exists('plans', 'id')->where('tenant_id', tenant()->id)],
            'filters.site' => ['nullable', 'string', 'max:255'], 'filters.subscription_status' => ['nullable', 'in:pending,active,suspended,cancelled'],
            'filters.connection_status' => ['nullable', 'in:online,offline,unknown'], 'filters.customer_ids' => ['nullable', 'array', 'max:10000'],
            'filters.customer_ids.*' => ['integer', Rule::exists(Customer::class, 'id')->where('tenant_id', tenant()->id)],
        ];
    }

    public function messages(): array
    {
        return ['channels.required' => 'Choose at least one delivery channel.', 'filters.customer_ids.*.exists' => 'Every selected customer must belong to your tenant.'];
    }
}
