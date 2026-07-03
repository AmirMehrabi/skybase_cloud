<?php

namespace App\Http\Requests\Support;

use App\Models\Subscription;
use App\Models\Ticket;
use App\Models\TicketTeam;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = tenant_id() ?? $this->user()?->tenant_id;

        return [
            'ticket_team_id' => [
                'required',
                Rule::exists(TicketTeam::class, 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)->where('status', 'active')),
            ],
            'assigned_user_id' => [
                'nullable',
                'string',
                Rule::exists(User::class, 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)->where('status', 'active')),
            ],
            'subscription_id' => [
                'required',
                Rule::exists(Subscription::class, 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'priority' => ['required', Rule::in([Ticket::PRIORITY_LOW, Ticket::PRIORITY_NORMAL, Ticket::PRIORITY_HIGH, Ticket::PRIORITY_URGENT])],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:10000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,pdf,txt,csv,doc,docx,xls,xlsx'],
        ];
    }
}
