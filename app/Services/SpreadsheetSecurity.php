<?php

namespace App\Services;

use RuntimeException;
use ZipArchive;

class SpreadsheetSecurity
{
    public function inspect(string $path): void
    {
        if (! config('security.enforce_resource_limits')) {
            return;
        }
        if (filesize($path) > config('security.max_upload_bytes')) {
            throw new RuntimeException('Spreadsheet exceeds the permitted file size.');
        }
        $archive = new ZipArchive;
        if ($archive->open($path) === true) {
            $total = 0;
            try {
                for ($index = 0; $index < $archive->numFiles; $index++) {
                    $entry = $archive->statIndex($index);
                    $total += $entry['size'];
                    if ($total > config('security.spreadsheet_max_unpacked_bytes')) {
                        throw new RuntimeException('Spreadsheet exceeds the permitted unpacked size.');
                    }
                }
            } finally {
                $archive->close();
            }
        }
    }

    public static function csvValue(mixed $value): mixed
    {
        return is_string($value) && preg_match('/^[\s\x00-\x1f]*[=+@-]/', $value) ? "'".$value : $value;
    }
}
