<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TenantLoginRequest;
use App\Support\Rbac\PermissionRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantLoginController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('customer.dashboard');
        }

        if (! config('app.cloud.enabled') && config('app.cloud.guest_entry') === 'customer') {
            return redirect()->route('customer.login');
        }

        if (config('skybase_billing.console_host_enabled') && request()->getHost() === config('skybase_billing.console_host')) {
            return view('modules.owner.login');
        }

        return view('auth.login');
    }

    public function login(TenantLoginRequest $request): RedirectResponse
    {
        if (! Auth::guard('web')->attemptWhen($request->only('email', 'password'), fn ($identity): bool => ! config('skybase_billing.console_host_enabled') || $request->getHost() === config('skybase_billing.console_host') || filled($identity->tenant_id), $request->boolean('remember'))) {
            return back()->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'These credentials do not match our records.']);
        }
        $user = Auth::guard('web')->user();
        if (config('security.enforce_active_accounts') && (! $user->isActive() || (! $user->isCentralAdmin() && (! $user->tenant_id || $user->tenant?->status !== 'active')))) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'These credentials do not match our records.']);
        }

        $request->session()->regenerate();

        $user->update(['last_login_at' => now()]);

        $request->session()->forget('mfa_verified_user');
        $request->session()->put('auth.password_confirmed_at', time());
        if ($user->isCentralAdmin()) {
            return redirect()->route('admin.super-admin.tenants.index');
        }

        $landingRoute = PermissionRegistry::firstAccessibleRoute($user) ?? 'dashboard';

        return redirect()->intended(route($landingRoute));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('auth.login');
    }
}
