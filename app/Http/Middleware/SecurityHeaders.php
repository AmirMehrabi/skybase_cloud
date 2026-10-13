<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = base64_encode(random_bytes(24));
        Vite::useCspNonce($nonce);
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        if (config('security.enforce_csp')) {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self)');
        }
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set(config('security.enforce_csp') ? 'Content-Security-Policy' : 'Content-Security-Policy-Report-Only', "default-src 'self'; script-src 'self' 'nonce-{$nonce}' 'unsafe-eval'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data: https:; connect-src 'self'; frame-ancestors 'self'; object-src 'none'; base-uri 'self'; form-action 'self'");
        if ($request->isSecure() && config('security.hsts')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }
        if ($request->user() || $request->routeIs('auth.*', 'mfa.*', 'customer.login*')) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
