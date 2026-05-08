<?php

namespace App\Http\Controllers;

use App\Http\Requests\VpnUser\StoreVpnUserRequest;
use App\Http\Requests\VpnUser\UpdateVpnUserRequest;
use App\Models\VpnUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class VpnUserController extends Controller
{
    public function index(Request $request): View
    {
        return view('vpn-users.index');
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'active', 'online']);

        $vpnUsers = VpnUser::query()
            ->filter($filters)
            ->latest('created_at')
            ->paginate($request->input('per_page', 15))
            ->through(fn (VpnUser $vpnUser): array => [
                'id' => $vpnUser->id,
                'username' => $vpnUser->username,
                'active' => $vpnUser->active,
                'online' => $vpnUser->online,
                'vpn_ip' => $vpnUser->vpn_ip,
                'real_ip' => $vpnUser->real_ip,
                'bytes_received' => $vpnUser->bytes_received,
                'bytes_sent' => $vpnUser->bytes_sent,
                'last_login_at' => $vpnUser->last_login_at?->format('M d, Y H:i'),
                'last_seen_at' => $this->lastSeenAt($vpnUser),
                'connected_at' => $vpnUser->connected_at?->format('M d, Y H:i'),
                'disconnected_at' => $vpnUser->disconnected_at?->format('M d, Y H:i'),
                'created_at' => $vpnUser->created_at?->format('M d, Y'),
                'show_url' => route('vpn-users.show', $vpnUser),
                'edit_url' => route('vpn-users.edit', $vpnUser),
            ]);

        return response()->json([
            'vpnUsers' => $vpnUsers->items(),
            'pagination' => [
                'current_page' => $vpnUsers->currentPage(),
                'last_page' => $vpnUsers->lastPage(),
                'per_page' => $vpnUsers->perPage(),
                'total' => $vpnUsers->total(),
                'from' => $vpnUsers->firstItem(),
                'to' => $vpnUsers->lastItem(),
            ],
        ]);
    }

    public function stats(): JsonResponse
    {
        return response()->json(VpnUser::getStats());
    }

    public function create(): View
    {
        return view('vpn-users.create');
    }

    public function store(StoreVpnUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        VpnUser::create([
            'tenant_id' => (string) (tenant()?->id ?? $request->user()->tenant_id),
            'username' => $validated['username'],
            'password_hash' => Hash::make($validated['password']),
            'active' => $request->boolean('active'),
        ]);

        return redirect()
            ->route('vpn-users.index')
            ->with('success', 'VPN user created successfully.');
    }

    public function show(VpnUser $vpnUser): View
    {
        $this->authorizeTenantAccess($vpnUser);

        return view('vpn-users.show', compact('vpnUser'));
    }

    public function edit(VpnUser $vpnUser): View
    {
        $this->authorizeTenantAccess($vpnUser);

        return view('vpn-users.edit', compact('vpnUser'));
    }

    public function update(UpdateVpnUserRequest $request, VpnUser $vpnUser): RedirectResponse
    {
        $this->authorizeTenantAccess($vpnUser);

        $validated = $request->validated();
        $updates = [
            'username' => $validated['username'],
            'active' => $request->boolean('active'),
        ];

        if (! empty($validated['password'])) {
            $updates['password_hash'] = Hash::make($validated['password']);
        }

        $vpnUser->update($updates);

        return redirect()
            ->route('vpn-users.show', $vpnUser)
            ->with('success', 'VPN user updated successfully.');
    }

    public function destroy(VpnUser $vpnUser): RedirectResponse
    {
        $this->authorizeTenantAccess($vpnUser);

        $vpnUser->delete();

        return redirect()
            ->route('vpn-users.index')
            ->with('success', 'VPN user deleted successfully.');
    }

    protected function authorizeTenantAccess(VpnUser $vpnUser): void
    {
        if (auth()->check() && auth()->user()->tenant_id && $vpnUser->tenant_id !== auth()->user()->tenant_id) {
            abort(403, 'You do not have access to this VPN user.');
        }
    }

    private function lastSeenAt(VpnUser $vpnUser): ?string
    {
        return ($vpnUser->online ? $vpnUser->connected_at : $vpnUser->disconnected_at)
            ?->format('M d, Y H:i')
            ?? $vpnUser->last_login_at?->format('M d, Y H:i');
    }
}
