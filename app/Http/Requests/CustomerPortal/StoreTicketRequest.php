<?php

namespace App\Http\Requests\CustomerPortal;

use App\Models\Subscription;
use App\Models\Ticket;
use App\Models\TicketTeam;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('customer')->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $customer = auth('customer')->user();

        return [
            'ticket_team_id' => [
                'required',
                Rule::exists(TicketTeam::class, 'id')->where(fn ($query) => $query->where('tenant_id', $customer?->tenant_id)->where('status', 'active')),
            ],
            'subscription_id' => [
                'nullable',
                Rule::exists(Subscription::class, 'id')->where(fn ($query) => $query->where('tenant_id', $customer?->tenant_id)->where('customer_id', $customer?->id)),
            ],
            'priority' => ['required', Rule::in([Ticket::PRIORITY_LOW, Ticket::PRIORITY_NORMAL, Ticket::PRIORITY_HIGH, Ticket::PRIORITY_URGENT])],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:10000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,pdf,txt,csv,doc,docx,xls,xlsx'],
        ];
    }
}
