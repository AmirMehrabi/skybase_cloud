<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;
use Throwable;

class UploadScanner
{
    public function scan(UploadedFile $file): void
    {
        if (! $file->isValid() || (config('security.enforce_resource_limits') && $file->getSize() > config('security.max_upload_bytes'))) {
            throw ValidationException::withMessages(['file' => 'The uploaded file is invalid or too large.']);
        }
        if (! config('security.upload_scan_required')) {
            return;
        }
        try {
            $process = new Process([config('security.upload_scanner'), '--no-summary', '--', $file->getRealPath()], timeout: 30);
            $process->run();
            if ($process->isSuccessful()) {
                return;
            }
        } catch (Throwable) {
        }
        throw ValidationException::withMessages(['file' => 'The file could not pass the security scan.']);
    }
}
