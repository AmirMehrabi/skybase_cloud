<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSaasBilling
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(config('skybase_billing.enabled'), 404);
        abort_unless($request->user()?->tenant_id && $request->user()->isOwner(), 403);
        abort_unless($request->user()->status === 'active' && tenant()?->status === 'active' && ! tenant()?->archived_at && ! tenant()?->application_access_suspended, 403);

        return $next($request);
    }
}
