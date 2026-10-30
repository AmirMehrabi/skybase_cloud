<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRequiredEmailIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->email_verification_required || $user->hasVerifiedEmail()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, 'Please verify your email address before continuing.');
        }

        return redirect()->guest(route('verification.notice'));

    }
}
