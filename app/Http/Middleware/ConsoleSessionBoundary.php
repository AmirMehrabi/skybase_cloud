<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ConsoleSessionBoundary
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('skybase_billing.console_host_enabled') && $request->getHost() !== config('skybase_billing.console_host') && $request->user()?->isCentralAdmin()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->away('https://'.config('skybase_billing.console_host').'/auth/login');
        }

        return $next($request);
    }
}
