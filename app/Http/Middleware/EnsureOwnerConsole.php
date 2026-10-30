<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOwnerConsole
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isCentralAdmin(), 403);
        abort_unless(config('skybase_billing.owner_console_enabled'), 404);
        $user = $request->user();
        if (! $user->mfa_confirmed_at || $request->session()->get('mfa_verified_user') !== $user->id || (int) $request->session()->get('mfa_verified_at', 0) < now()->subMinutes(config('security.mfa_session_minutes'))->timestamp) {
            return redirect()->route($user->mfa_confirmed_at ? 'mfa.challenge' : 'mfa.setup');
        }

        return $next($request);
    }
}
