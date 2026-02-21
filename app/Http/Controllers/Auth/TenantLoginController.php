<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TenantLoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantLoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(TenantLoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return back()->withInput()
                ->withErrors([
                    'email' => 'These credentials do not match our records.',
                ]);
        }

        if ($user->tenant_id) {
            $tenant = $user->tenant;

            if (! $tenant) {
                return back()->withInput()
                    ->withErrors([
                        'email' => 'Tenant not found. Please contact support.',
                    ]);
            }

            if ($tenant->isSuspended()) {
                return back()->withInput()
                    ->withErrors([
                        'email' => 'Your account has been suspended. Please contact support.',
                    ]);
            }
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withInput()
                ->withErrors([
                    'email' => 'These credentials do not match our records.',
                ]);
        }

        $request->session()->regenerate();

        $user->update(['last_login_at' => now()]);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
