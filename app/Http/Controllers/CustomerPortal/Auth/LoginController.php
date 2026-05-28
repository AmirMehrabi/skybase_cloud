<?php

namespace App\Http\Controllers\CustomerPortal\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerPortal\LoginRequest;
use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        if (! config('app.cloud.enabled') && config('app.cloud.guest_entry') !== 'customer') {
            return redirect()->route('auth.login');
        }

        return view('customer.auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $tenant = Tenant::query()
            ->where('slug', $request->string('tenant')->toString())
            ->first();

        if (! $tenant || $tenant->isSuspended()) {
            return back()
                ->withInput($request->safe()->except('password'))
                ->withErrors(['tenant' => 'These credentials do not match our records.']);
        }

        $customer = Customer::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('email', $request->string('email')->toString())
            ->first();

        if (! $customer || ! $customer->password || ! Hash::check($request->string('password')->toString(), $customer->password)) {
            return back()
                ->withInput($request->safe()->except('password'))
                ->withErrors(['email' => 'These credentials do not match our records.']);
        }

        if ($customer->status !== 'active') {
            return back()
                ->withInput($request->safe()->except('password'))
                ->withErrors(['email' => 'Your customer account is not active. Please contact support.']);
        }

        Auth::guard('customer')->login($customer, $request->boolean('remember'));

        $request->session()->regenerate();

        $customer->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('customer.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('customer.login');
    }
}
