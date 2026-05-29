<?php

namespace App\Http\Requests\Support;

use App\Models\TicketTeam;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketTeamRequest extends FormRequest
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
        $teamId = $this->route('team')?->id;
        $allowedAgentIds = User::query()
            ->where(function ($query) use ($tenantId): void {
                $query->where('tenant_id', $tenantId)
                    ->orWhereNull('tenant_id');
            })
            ->whereIn('role', ['owner', 'admin', 'support', 'noc'])
            ->where('status', 'active')
            ->pluck('id')
            ->map(fn (int $id): string => (string) $id)
            ->all();

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'alpha_dash',
                'max:255',
                Rule::unique(TicketTeam::class, 'slug')->where(fn ($query) => $query->where('tenant_id', $tenantId))->ignore($teamId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'assignment_strategy' => ['required', Rule::in([TicketTeam::STRATEGY_RANDOM, TicketTeam::STRATEGY_DEFAULT_AGENT, TicketTeam::STRATEGY_QUEUE])],
            'default_user_id' => [
                'nullable',
                Rule::in($allowedAgentIds),
            ],
            'first_response_minutes' => ['required', 'integer', 'min:0', 'max:10080'],
            'resolution_minutes' => ['required', 'integer', 'min:0', 'max:43200'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'users' => ['nullable', 'array'],
            'users.*' => [Rule::in($allowedAgentIds)],
            'auto_assign_users' => ['nullable', 'array'],
            'auto_assign_users.*' => [Rule::in($allowedAgentIds)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => $this->input('slug') ?: str($this->input('name', ''))->slug()->toString(),
            'sort_order' => $this->input('sort_order', 0),
        ]);
    }
}
