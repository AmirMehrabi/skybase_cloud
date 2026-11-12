<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAcsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(tenant()?->acs_enabled && $request->user()?->tenant_id === tenant()->id, 403);

        return $next($request);
    }
}
