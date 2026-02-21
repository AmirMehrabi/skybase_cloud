<?php

namespace App\Http\Controllers\Admin\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tenant\CreateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::where('tenant_id', auth()->user()->tenant_id)
            ->with('roles')
            ->latest()
            ->paginate(20);

        return view('admin.tenant.users.index', compact('users'));
    }

    public function create(): View
    {
        $roles = Role::where('tenant_id', auth()->user()->tenant_id)
            ->get()
            ->pluck('name', 'id')
            ->toArray();

        return view('admin.tenant.users.create', compact('roles'));
    }

    public function store(CreateUserRequest $request): RedirectResponse
    {
        $tenant = auth()->user()->tenant;

        $planLimit = $tenant->plan->max_users ?? config('tenancy.max_users', 10);
        $currentUserCount = User::where('tenant_id', $tenant->id)->count();

        if ($currentUserCount >= $planLimit) {
            return back()
                ->withInput()
                ->with('error', 'You have reached the maximum number of users allowed in your plan.');
        }

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status' => 'active',
        ]);

        return redirect()
            ->route('admin.tenant.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        $this->authorizeUserAccess($user);

        $roles = Role::where('tenant_id', auth()->user()->tenant_id)
            ->get()
            ->pluck('name', 'id')
            ->toArray();

        return view('admin.tenant.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeUserAccess($user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
            'role' => ['required', 'string', 'in:owner,admin,billing,support,noc'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($request->password);
        }

        $user->update($validated);

        return redirect()
            ->route('admin.tenant.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorizeUserAccess($user);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()
            ->route('admin.tenant.users.index')
            ->with('success', 'User deleted successfully.');
    }

    protected function authorizeUserAccess(User $user): void
    {
        if ($user->tenant_id !== auth()->user()->tenant_id) {
            abort(403, 'Unauthorized access.');
        }
    }
}
