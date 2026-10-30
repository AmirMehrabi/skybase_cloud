<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterTenantRequest;
use App\Jobs\SendCloudSignupTelegram;
use App\Mail\CloudSignupReceived;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class TenantRegistrationController extends Controller
{
    public function showRegistrationForm(): View
    {
        abort_unless(config('app.cloud.enabled'), 404);

        return view('auth.register-tenant');
    }

    public function register(RegisterTenantRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $slug = Str::slug($request->company_name).'-'.Str::random(6);

            $tenant = Tenant::create([
                'id' => Str::uuid()->toString(),
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

            if (! $tenant->exists) {
                throw new \Exception('Failed to create tenant record.');
            }

            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $request->owner_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'owner',
                'status' => 'active',
                'email_verification_required' => true,
            ]);

            if (! $user->exists) {
                throw new \Exception('Failed to create user record.');
            }

            $this->createDefaultRoles($tenant->id);
            $this->createDefaultSettings($tenant->id);

            DB::commit();

        } catch (QueryException $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::error('Registration database error: '.$e->getMessage());

            return back()->withInput(request()->except(['password', 'password_confirmation', 'current_password', 'pppoe_password', 'api_password', 'coa_secret', 'incoming_password', 'outgoing_password', 'code', 'recovery_code']))
                ->with('error', 'Registration failed. Please try again.');
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::error('Registration error: '.$e->getMessage());

            return back()->withInput(request()->except(['password', 'password_confirmation', 'current_password', 'pppoe_password', 'api_password', 'coa_secret', 'incoming_password', 'outgoing_password', 'code', 'recovery_code']))
                ->with('error', 'Registration failed. Please try again.');
        }

        try {
            event(new Registered($user));
        } catch (Throwable $throwable) {
            Log::warning('Cloud signup verification email could not be queued.', ['exception_type' => $throwable::class]);
        }

        $signupDetails = [
            'business_name' => $tenant->company_name,
            'owner_name' => $user->name,
            'email' => $user->email,
            'phone' => $tenant->phone,
            'country' => $tenant->country,
        ];

        foreach (config('contact.recipients') as $recipient) {
            try {
                Mail::to($recipient)->queue(new CloudSignupReceived($signupDetails));
            } catch (Throwable $throwable) {
                Log::warning('Cloud signup email notification could not be queued.', ['exception_type' => $throwable::class]);
            }
        }

        try {
            SendCloudSignupTelegram::dispatch($signupDetails)->onQueue('notifications');
        } catch (Throwable $throwable) {
            Log::warning('Cloud signup Telegram notification could not be queued.', ['exception_type' => $throwable::class]);
        }

        auth()->login($user);

        return redirect()->route('verification.notice')
            ->with('success', 'Your 14-day trial is ready. Verify your email to get started.');
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
