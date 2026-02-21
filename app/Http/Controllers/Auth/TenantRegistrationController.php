<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterTenantRequest;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantRegistrationController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register-tenant');
    }

    public function register(RegisterTenantRequest $request)
    {
        try {
            DB::beginTransaction();

            $slug = Str::slug($request->company_name).'-'.Str::random(6);

            $tenant = Tenant::create([
                'id' => Str::uuid(),
                'name' => $request->owner_name,
                'slug' => $slug,
                'company_name' => $request->company_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'country' => $request->country,
                'timezone' => $request->timezone ?? 'UTC',
                'status' => 'active',
                'trial_ends_at' => now()->addDays(14),
            ]);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $request->owner_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'owner',
                'status' => 'active',
            ]);

            $this->createDefaultRoles($tenant->id);
            $this->createDefaultSettings($tenant->id);

            DB::commit();

            auth()->login($user);

            return redirect()->route('dashboard')
                ->with('success', 'Welcome to SkyBase Cloud! Your account has been created with a 14-day trial.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::info($e->getMessage());
            return back()->withInput()
                ->with('error', 'Registration failed. Please try again.');
        }
    }

    protected function createDefaultRoles(string $tenantId): void
    {
        $defaultRoles = Role::getDefaultRoles();

        foreach ($defaultRoles as $roleData) {
            Role::create([
                'tenant_id' => $tenantId,
                'name' => $roleData['name'],
                'permissions' => $roleData['permissions'],
                'description' => $roleData['description'],
            ]);
        }
    }

    protected function createDefaultSettings(string $tenantId): void
    {
        $defaultSettings = Setting::getDefaultSettings();

        foreach ($defaultSettings as $settingData) {
            Setting::create([
                'tenant_id' => $tenantId,
                'key' => $settingData['key'],
                'value' => $settingData['value'],
                'type' => $settingData['type'],
                'group' => $settingData['group'],
            ]);
        }
    }
}
