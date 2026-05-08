<?php

namespace App\Http\Controllers;

use App\Http\Requests\VpnUser\StoreVpnUserRequest;
use App\Http\Requests\VpnUser\UpdateVpnUserRequest;
use App\Models\VpnUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class VpnUserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'active']);

        $vpnUsers = VpnUser::query()
            ->filter($filters)
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('vpn-users.index', [
            'vpnUsers' => $vpnUsers,
            'stats' => VpnUser::getStats(),
            'filters' => $filters,
        ]);
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
}
