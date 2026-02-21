<?php

namespace App\Http\Controllers\Admin\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
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
        $users = User::where('tenant_id', tenant_id())
            ->with('tenant')
            ->when(request('search'), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->when(request('role'), function ($query, $role) {
                $query->where('role', $role);
            })
            ->when(request('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $roles = Role::where('tenant_id', tenant_id())->pluck('name', 'name');

        return view('admin.tenant.users.index', compact('users', 'roles'));
    }

    public function create(): View
    {
        $roles = Role::where('tenant_id', tenant_id())
            ->get()
            ->map(function ($role) {
                return [
                    'id' => $role->name,
                    'name' => $role->name,
                    'label' => ucfirst(str_replace('_', ' ', $role->name)),
                    'description' => $role->description,
                ];
            });

        $availableRoles = [
            'admin' => 'Administrator - Full access to all features',
            'billing' => 'Billing Manager - Manage invoices and payments',
            'support' => 'Support Agent - Help customers and manage tickets',
            'noc' => 'NOC Engineer - Monitor network and routers',
        ];

        return view('admin.tenant.users.create', compact('roles', 'availableRoles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:admin,billing,support,noc'],
            'status' => ['required', 'in:active,inactive'],
            'send_invite' => ['nullable', 'boolean'],
        ]);

        $user = User::create([
            'tenant_id' => tenant_id(),
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'status' => $validated['status'],
        ]);

        // Log activity
        ActivityLog::create([
            'tenant_id' => tenant_id(),
            'user_id' => auth()->id(),
            'action' => 'user.created',
            'model_type' => User::class,
            'model_id' => $user->id,
            'new_values' => $user->only('name', 'email', 'role', 'status'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()
            ->route('admin.tenant.users.index')
            ->with('success', "User {$user->name} has been created successfully.");
    }

    public function show(User $user): View
    {
        $this->authorizeUserAccess($user);

        $user->load('tenant');

        $recentActivity = ActivityLog::where('user_id', $user->id)
            ->where('tenant_id', tenant_id())
            ->latest()
            ->take(20)
            ->get();

        return view('admin.tenant.users.show', compact('user', 'recentActivity'));
    }

    public function edit(User $user): View
    {
        $this->authorizeUserAccess($user);

        $roles = [
            'admin' => 'Administrator - Full access to all features',
            'billing' => 'Billing Manager - Manage invoices and payments',
            'support' => 'Support Agent - Help customers and manage tickets',
            'noc' => 'NOC Engineer - Monitor network and routers',
        ];

        return view('admin.tenant.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeUserAccess($user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'role' => ['required', 'in:admin,billing,support,noc'],
            'status' => ['required', 'in:active,inactive'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $oldValues = $user->only('name', 'email', 'role', 'status');

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'status' => $validated['status'],
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        $newValues = $user->only('name', 'email', 'role', 'status');

        // Log activity
        ActivityLog::create([
            'tenant_id' => tenant_id(),
            'user_id' => auth()->id(),
            'action' => 'user.updated',
            'model_type' => User::class,
            'model_id' => $user->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()
            ->route('admin.tenant.users.index')
            ->with('success', "User {$user->name} has been updated successfully.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorizeUserAccess($user);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $userName = $user->name;
        $userId = $user->id;

        $user->delete();

        // Log activity
        ActivityLog::create([
            'tenant_id' => tenant_id(),
            'user_id' => auth()->id(),
            'action' => 'user.deleted',
            'model_type' => User::class,
            'model_id' => $userId,
            'old_values' => ['name' => $userName, 'email' => $user->email],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()
            ->route('admin.tenant.users.index')
            ->with('success', "User {$userName} has been deleted successfully.");
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'users' => ['required', 'array'],
            'users.*' => ['exists:users,id'],
            'action' => ['required', 'in:activate,deactivate,delete'],
        ]);

        $users = User::where('tenant_id', tenant_id())
            ->whereIn('id', $validated['users'])
            ->where('id', '!=', auth()->id())
            ->get();

        $count = 0;

        foreach ($users as $user) {
            match ($validated['action']) {
                'activate' => $user->update(['status' => 'active']),
                'deactivate' => $user->update(['status' => 'inactive']),
                'delete' => $user->delete(),
            };
            $count++;
        }

        $message = match ($validated['action']) {
            'activate' => "{$count} user(s) activated successfully.",
            'deactivate' => "{$count} user(s) deactivated successfully.",
            'delete' => "{$count} user(s) deleted successfully.",
        };

        // Log activity
        ActivityLog::create([
            'tenant_id' => tenant_id(),
            'user_id' => auth()->id(),
            'action' => 'user.bulk_'.strtolower($validated['action']),
            'model_type' => User::class,
            'new_values' => ['count' => $count, 'user_ids' => $validated['users']],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()
            ->route('admin.tenant.users.index')
            ->with('success', $message);
    }

    protected function authorizeUserAccess(User $user): void
    {
        if ($user->tenant_id !== tenant_id()) {
            abort(403, 'Unauthorized access.');
        }
    }
}
