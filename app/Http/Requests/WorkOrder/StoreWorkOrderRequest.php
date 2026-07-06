<?php

namespace App\Http\Requests\WorkOrder;

use App\Enums\WorkOrderPriority;
use App\Enums\WorkOrderType;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Ticket;
use App\Models\WorkOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', WorkOrder::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $tenantId = tenant_id() ?? $this->user()?->tenant_id;

        return [
            'customer_id' => ['required', Rule::exists(Customer::class, 'id')->where('tenant_id', $tenantId)],
            'subscription_id' => ['nullable', Rule::exists(Subscription::class, 'id')->where('tenant_id', $tenantId)],
            'source_ticket_id' => ['nullable', Rule::exists(Ticket::class, 'id')->where('tenant_id', $tenantId)],
            'plan_id' => ['nullable', Rule::exists(Plan::class, 'id')],
            'type' => ['required', Rule::enum(WorkOrderType::class)],
            'priority' => ['required', Rule::enum(WorkOrderPriority::class)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'service_address_line1' => ['required', 'string', 'max:255'],
            'service_address_line2' => ['nullable', 'string', 'max:255'],
            'service_city' => ['nullable', 'string', 'max:100'],
            'service_state' => ['nullable', 'string', 'max:100'],
            'service_postal_code' => ['nullable', 'string', 'max:30'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'connection_type' => ['nullable', Rule::in(['pppoe', 'dhcp', 'static'])],
            'requested_at' => ['nullable', 'date'],
            'promised_at' => ['nullable', 'date', 'after_or_equal:requested_at'],
            'tasks' => ['nullable', 'array', 'max:50'],
            'tasks.*.title' => ['required', 'string', 'max:255'],
            'tasks.*.instructions' => ['nullable', 'string', 'max:2000'],
            'tasks.*.is_required' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = WorkOrderType::tryFrom((string) $this->input('type'));
            if ($type?->requiresSubscription() && ! $this->filled('subscription_id')) {
                $validator->errors()->add('subscription_id', 'A subscription is required for this work type.');
            }

            if ($this->filled('subscription_id') && ! Subscription::query()
                ->whereKey($this->integer('subscription_id'))
                ->where('customer_id', $this->integer('customer_id'))
                ->exists()) {
                $validator->errors()->add('subscription_id', 'The selected subscription does not belong to this customer.');
            }

            if ($this->filled('source_ticket_id') && ! Ticket::query()
                ->whereKey($this->integer('source_ticket_id'))
                ->where('customer_id', $this->integer('customer_id'))
                ->exists()) {
                $validator->errors()->add('source_ticket_id', 'The selected ticket does not belong to this customer.');
            }
        });
    }
}
