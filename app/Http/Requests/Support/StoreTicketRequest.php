<?php

namespace App\Http\Requests\Support;

use App\Models\Subscription;
use App\Models\Ticket;
use App\Models\TicketTeam;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->filled('assigned_user_id') || ! $this->filled('ticket_team_id')) {
                    return;
                }

                $tenantId = tenant_id() ?? $this->user()?->tenant_id;
                $isActiveTeamMember = TicketTeam::query()
                    ->where('tenant_id', $tenantId)
                    ->whereKey($this->integer('ticket_team_id'))
                    ->whereHas('users', function ($query) use ($tenantId): void {
                        $query->where('users.tenant_id', $tenantId)
                            ->where('users.id', $this->integer('assigned_user_id'))
                            ->where('users.status', 'active')
                            ->where('ticket_team_user.is_active', true);
                    })
                    ->exists();

                if (! $isActiveTeamMember) {
                    $validator->errors()->add('assigned_user_id', 'The selected agent is not an active member of this team.');
                }
            },
        ];
    }
}
