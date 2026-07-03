<?php

namespace App\Http\Requests\Support;

use App\Models\TicketTeam;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTicketAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('update', $this->route('ticket'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = tenant_id() ?? $this->user()?->tenant_id;

        return [
            'ticket_team_id' => [
                'required',
                Rule::exists(TicketTeam::class, 'id')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId)->where('status', 'active')
                ),
            ],
            'assigned_user_id' => [
                'nullable',
                Rule::exists(User::class, 'id')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId)->where('status', 'active')
                ),
            ],
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

    public function messages(): array
    {
        return [
            'ticket_team_id.required' => 'Select the team responsible for this ticket.',
            'assigned_user_id.exists' => 'The selected agent is not available.',
        ];
    }
}
