<?php

namespace App\Console\Commands;

use App\Models\Router;
use App\Models\Setting;
use App\Models\Subscription;
use App\Services\CompatibleSecretCast;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;

class UpgradeSecurityStorage extends Command
{
    protected $signature = 'security:encrypt-secrets {--decrypt : Restore plaintext for a coordinated rollback}';

    protected $description = 'Explicitly convert stored secrets after all application instances support the chosen format';

    public function handle(): int
    {
        if (! $this->option('decrypt') && ! config('security.encrypt_secrets')) {
            $this->error('Enable SECURITY_ENCRYPT_SECRETS on every application instance before encrypting existing data.');

            return self::FAILURE;
        }
        if ($this->option('decrypt') && config('security.encrypt_secrets')) {
            $this->error('Disable SECURITY_ENCRYPT_SECRETS on every application instance before restoring plaintext.');

            return self::FAILURE;
        }
        $fields = [Router::class => ['api_password', 'coa_secret'], Subscription::class => ['pppoe_password'], Setting::class => ['value']];
        foreach ($fields as $model => $columns) {
            foreach ($model::withoutGlobalScopes()->lazyById(100) as $record) {
                foreach ($columns as $column) {
                    $raw = $record->getRawOriginal($column);
                    if ($raw !== null && CompatibleSecretCast::isEncrypted($raw)) {
                        Crypt::decryptString($raw);
                    }
                }
            }
        }
        foreach ($fields as $model => $columns) {
            foreach ($model::withoutGlobalScopes()->lazyById(100) as $record) {
                $record->getConnection()->transaction(function () use ($model, $record, $columns): void {
                    $current = $model::withoutGlobalScopes()->whereKey($record->getKey())->lockForUpdate()->firstOrFail();
                    $updates = [];
                    foreach ($columns as $column) {
                        $raw = $current->getRawOriginal($column);
                        if ($raw === null) {
                            continue;
                        }
                        $encrypted = CompatibleSecretCast::isEncrypted($raw);
                        if ($encrypted) {
                            $plaintext = Crypt::decryptString($raw);
                            if ($this->option('decrypt')) {
                                $updates[$column] = $plaintext;
                            }
                        } elseif (! $this->option('decrypt')) {
                            $updates[$column] = Crypt::encryptString($raw);
                        }
                    }
                    if ($updates !== []) {
                        $model::withoutGlobalScopes()->whereKey($current->getKey())->update($updates);
                    }
                });
            }
        }
        $this->info('Secret storage converted. Existing encrypted values were verified before conversion.');

        return self::SUCCESS;
    }
}
