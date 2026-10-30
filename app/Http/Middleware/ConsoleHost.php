<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ConsoleHost
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('skybase_billing.console_host_enabled')) {
            return $next($request);
        }
        $isConsole = $request->getHost() === config('skybase_billing.console_host');
        if (! $isConsole && $request->is('admin/super-admin', 'admin/super-admin/*')) {
            return redirect()->away('https://'.config('skybase_billing.console_host').'/admin/super-admin/tenants');
        }
        if ($isConsole) {
            config(['session.cookie' => 'skybase_console_session', 'session.domain' => null, 'session.secure' => true, 'auth.guards.web.provider' => 'console_users']);
            Auth::forgetGuards();
            if ($request->is('/', 'dashboard')) {
                return redirect('/admin/super-admin/tenants');
            }
            abort_unless($request->is('auth/login', 'auth/logout', 'account/security/*', 'admin/super-admin/*', 'up'), 404);
        }

        return $next($request);
    }
}
