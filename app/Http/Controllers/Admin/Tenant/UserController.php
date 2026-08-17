<?php

namespace App\Http\Controllers\Admin\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tenant\StoreUserRequest;
use App\Http\Requests\Admin\Tenant\UpdateUserRequest;
use App\Http\Requests\NotificationPreferenceRequest;
use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use App\Models\UserGroup;
use App\Services\ActivityLogFormatter;
use App\Services\NotificationPreferenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $tenantId = $this->currentTenantId($request);
        Role::ensureDefaultsForTenant($tenantId);

        $users = User::where('tenant_id', $tenantId)
            ->with('tenant')
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->input('role'), function ($query, $role) {
                $query->whereRaw('LOWER(role) = ?', [strtolower((string) $role)]);
            })
            ->when($request->input('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $roles = Role::where('tenant_id', $tenantId)->orderBy('name')->pluck('name', 'name');

        return view('admin.tenant.users.index', compact('users', 'roles'));
    }

    public function create(Request $request): View
    {
        $tenantId = $this->currentTenantId($request);
        Role::ensureDefaultsForTenant($tenantId);

        $roles = Role::where('tenant_id', $tenantId)
            ->whereRaw('LOWER(name) != ?', ['owner'])
            ->orderBy('name')
            ->get()
            ->map(function ($role) {
                return [
                    'id' => $role->name,
                    'name' => $role->name,
                    'label' => ucfirst(str_replace('_', ' ', $role->name)),
                    'description' => $role->description,
                ];
            });

        $userGroups = UserGroup::query()->orderBy('name')->pluck('name', 'id');

        return view('admin.tenant.users.create', compact('roles', 'userGroups'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $tenantId = $this->currentTenantId($request);

        $user = User::create([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'status' => $validated['status'],
            'user_group_id' => $request->user()->isOwner()
                ? ($validated['user_group_id'] ?? null)
                : $request->user()->user_group_id,
        ]);

        // Log activity
        ActivityLog::create([
            'tenant_id' => $tenantId,
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

        $recentActivity = app(ActivityLogFormatter::class)->forSubject($user, $user->tenant_id);
        $notificationPreference = app(NotificationPreferenceService::class)->settingsFor($user);
        $unreadNotificationsCount = $user->notifications()
            ->where('tenant_id', $user->tenant_id)
            ->whereNull('read_at')
            ->whereNull('archived_at')
            ->count();

        return view('admin.tenant.users.show', compact('user', 'recentActivity', 'notificationPreference', 'unreadNotificationsCount'));
    }

    public function edit(User $user): View
    {
        $this->authorizeUserAccess($user);

        Role::ensureDefaultsForTenant((string) $user->tenant_id);
        $roles = Role::where('tenant_id', $user->tenant_id)
            ->orderBy('name')
            ->pluck('description', 'name');

        $userGroups = UserGroup::query()->orderBy('name')->pluck('name', 'id');

        return view('admin.tenant.users.edit', compact('user', 'roles', 'userGroups'));
    }

    public function updateNotifications(NotificationPreferenceRequest $request, User $user, NotificationPreferenceService $preferences): RedirectResponse
    {
        $this->authorizeUserAccess($user);
        $preferences->updateFor($user, $request->validated());

        return back()->with('success', "Notification preferences updated for {$user->name}.");
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorizeUserAccess($user);

        $validated = $request->validated();

        $oldValues = $user->only('name', 'email', 'role', 'status');

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'status' => $validated['status'],
            'user_group_id' => $request->user()->isOwner()
                ? ($validated['user_group_id'] ?? null)
                : $user->user_group_id,
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        $newValues = $user->only('name', 'email', 'role', 'status');

        // Log activity
        ActivityLog::create([
            'tenant_id' => $this->currentTenantId($request),
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
            'tenant_id' => $this->currentTenantId($request),
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

        $tenantId = $this->currentTenantId($request);

        $users = User::where('tenant_id', $tenantId)
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
            'tenant_id' => $tenantId,
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
        if ($user->tenant_id !== $this->currentTenantId(request())) {
            abort(403, 'Unauthorized access.');
        }
    }

    private function currentTenantId(Request $request): string
    {
        $tenantId = tenant_id() ?? $request->user()?->tenant_id;

        if (! $tenantId) {
            abort(403, 'Tenant context is required.');
        }

        return (string) $tenantId;
    }
}
