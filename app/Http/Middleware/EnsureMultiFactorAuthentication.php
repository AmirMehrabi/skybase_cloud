<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMultiFactorAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web');
        if (! $user instanceof User) {
            abort(403);
        }
        $required = $user->mfa_confirmed_at !== null || (config('security.require_mfa') && ($user->isCentralAdmin() || $user->isAdmin()));
        if ($required && ($request->session()->get('mfa_verified_user') !== $user->id
            || (int) $request->session()->get('mfa_verified_at', 0) < now()->subMinutes(config('security.mfa_session_minutes'))->timestamp)) {
            if ($request->expectsJson()) {
                abort(403, 'Multi-factor authentication is required.');
            }

            return redirect()->route($user->mfa_confirmed_at ? 'mfa.challenge' : 'mfa.setup');
        }

        return $next($request);
    }
}
