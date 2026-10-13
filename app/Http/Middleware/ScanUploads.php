<?php

namespace App\Http\Middleware;

use App\Services\UploadScanner;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

class ScanUploads
{
    public function handle(Request $request, Closure $next): Response
    {
        $files = Arr::flatten($request->allFiles());
        abort_if(config('security.enforce_resource_limits') && count($files) > 10, 413);
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                app(UploadScanner::class)->scan($file);
            }
        }

        return $next($request);
    }
}
