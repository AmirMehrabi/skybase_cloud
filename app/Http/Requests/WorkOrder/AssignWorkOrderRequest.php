<?php

namespace App\Http\Requests\WorkOrder;

use App\Models\TicketTeam;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AssignWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assign', $this->route('work_order')) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $tenantId = tenant_id() ?? $this->user()?->tenant_id;

        return [
            'assigned_team_id' => ['required', Rule::exists(TicketTeam::class, 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)->where('status', 'active'))],
            'assigned_user_id' => ['nullable', Rule::exists(User::class, 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)->where('status', 'active'))],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('assigned_user_id')) {
                return;
            }

            $member = TicketTeam::query()->whereKey($this->integer('assigned_team_id'))
                ->whereHas('users', fn ($query) => $query->whereKey($this->integer('assigned_user_id'))->where('ticket_team_user.is_active', true))
                ->exists();

            if (! $member) {
                $validator->errors()->add('assigned_user_id', 'The technician must be an active member of the selected team.');
            }
        });
    }
}
