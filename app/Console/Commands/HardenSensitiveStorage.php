<?php

namespace App\Console\Commands;

use App\Models\ImportExportRun;
use App\Models\ImportExportRunRow;
use App\Models\TicketAttachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class HardenSensitiveStorage extends Command
{
    protected $signature = 'security:harden-storage {--prune : Delete expired import and export artifacts}';

    protected $description = 'Move ticket attachments to private storage and redact retained import credentials';

    public function handle(): int
    {
        $disk = config('security.attachment_disk') === 'public' ? 'local' : config('security.attachment_disk');
        foreach (TicketAttachment::withoutGlobalScopes()->lazyById(100) as $attachment) {
            $public = Storage::disk('public');
            $private = Storage::disk($disk);
            if (! $public->exists($attachment->path)) {
                if ($attachment->disk === 'public') {
                    throw new RuntimeException('An attachment is missing. Restore it before retrying.');
                }

                continue;
            }
            if (! $private->exists($attachment->path)) {
                $stream = $public->readStream($attachment->path);
                try {
                    if (! is_resource($stream) || ! $private->put($attachment->path, $stream)) {
                        throw new RuntimeException('Unable to copy an attachment to private storage.');
                    }
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }
            }
            if (! hash_equals($this->checksum('public', $attachment->path), $this->checksum($disk, $attachment->path))) {
                throw new RuntimeException('Private attachment verification failed.');
            }
            $attachment->forceFill(['disk' => $disk])->saveQuietly();
            if (! $public->delete($attachment->path)) {
                throw new RuntimeException('Unable to remove a public attachment. Retry the command.');
            }
        }
        foreach (ImportExportRunRow::withoutGlobalScopes()->lazyById(100) as $row) {
            $payload = collect($row->payload ?? [])->reject(fn (mixed $value, string $key): bool => preg_match('/password|secret|token|credential/i', $key) === 1)->all();
            $row->forceFill(['payload' => $payload])->saveQuietly();
        }
        if ($this->option('prune')) {
            foreach (ImportExportRun::withoutGlobalScopes()->whereNotNull('finished_at')->where('finished_at', '<', now()->subDays(config('security.import_retention_days')))->lazyById(100) as $run) {
                if ($run->file_path && ! Storage::disk($run->disk)->delete($run->file_path)) {
                    throw new RuntimeException('Unable to remove an expired artifact.');
                }
                $run->rows()->withoutGlobalScopes()->where('tenant_id', $run->tenant_id)->delete();
                $run->deleteQuietly();
            }
        }
        $this->info('Private attachment storage and retained import data hardened.');

        return self::SUCCESS;
    }

    private function checksum(string $disk, string $path): string
    {
        $stream = Storage::disk($disk)->readStream($path);
        if (! is_resource($stream)) {
            throw new RuntimeException('Unable to verify attachment integrity.');
        }
        try {
            $hash = hash_init('sha256');
            if (hash_update_stream($hash, $stream) === false) {
                throw new RuntimeException('Unable to verify attachment integrity.');
            }

            return hash_final($hash);
        } finally {
            fclose($stream);
        }
    }
}
