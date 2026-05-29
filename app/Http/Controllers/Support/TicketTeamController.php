<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Http\Requests\Support\StoreTicketTeamRequest;
use App\Models\TicketTeam;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TicketTeamController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', TicketTeam::class);

        return view('support.teams.index', [
            'teams' => TicketTeam::query()->withCount('tickets')->with('users', 'defaultUser')->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', TicketTeam::class);

        return view('support.teams.create', [
            'team' => new TicketTeam([
                'status' => 'active',
                'assignment_strategy' => TicketTeam::STRATEGY_QUEUE,
                'first_response_minutes' => 240,
                'resolution_minutes' => 2880,
            ]),
            'agents' => $this->agents(),
        ]);
    }

    public function store(StoreTicketTeamRequest $request): RedirectResponse
    {
        Gate::authorize('create', TicketTeam::class);

        $team = TicketTeam::create($request->safe()->except(['users', 'auto_assign_users']));
        $this->syncUsers($team, $request->validated('users', []), $request->validated('auto_assign_users', []));

        return redirect()
            ->route('support.teams.index')
            ->with('success', 'Support team created.');
    }

    public function edit(TicketTeam $team): View
    {
        Gate::authorize('update', $team);

        $team->load('users');

        return view('support.teams.edit', [
            'team' => $team,
            'agents' => $this->agents(),
        ]);
    }

    public function update(StoreTicketTeamRequest $request, TicketTeam $team): RedirectResponse
    {
        Gate::authorize('update', $team);

        $team->update($request->safe()->except(['users', 'auto_assign_users']));
        $this->syncUsers($team, $request->validated('users', []), $request->validated('auto_assign_users', []));

        return redirect()
            ->route('support.teams.index')
            ->with('success', 'Support team updated.');
    }

    public function destroy(TicketTeam $team): RedirectResponse
    {
        Gate::authorize('delete', $team);

        if ($team->tickets()->exists()) {
            return back()->with('error', 'Teams with tickets cannot be deleted. Mark the team inactive instead.');
        }

        $team->delete();

        return redirect()
            ->route('support.teams.index')
            ->with('success', 'Support team deleted.');
    }

    private function agents()
    {
        $tenantId = tenant_id() ?? auth()->user()?->tenant_id;

        return User::query()
            ->where(function ($query) use ($tenantId): void {
                $query->where('tenant_id', $tenantId)
                    ->orWhereNull('tenant_id');
            })
            ->whereIn('role', ['owner', 'admin', 'support', 'noc'])
            ->active()
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<int, int|string>  $userIds
     * @param  array<int, int|string>  $autoAssignUserIds
     */
    private function syncUsers(TicketTeam $team, array $userIds, array $autoAssignUserIds): void
    {
        if ($team->default_user_id && ! in_array((string) $team->default_user_id, array_map('strval', $userIds), true)) {
            $userIds[] = $team->default_user_id;
        }

        $autoAssignLookup = collect($autoAssignUserIds)->map(fn ($id): string => (string) $id)->flip();
        $sync = collect($userIds)
            ->unique()
            ->mapWithKeys(fn ($userId): array => [
                (int) $userId => [
                    'tenant_id' => $team->tenant_id,
                    'is_active' => true,
                    'accepts_auto_assignment' => $autoAssignLookup->has((string) $userId),
                ],
            ])
            ->all();

        $team->users()->sync($sync);
    }
}
