<?php

namespace App\Console\Commands;

use App\Models\Router;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TicketAttachment;
use App\Services\CompatibleSecretCast;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SecurityPreflight extends Command
{
    protected $signature = 'security:preflight {--compatibility : Check schema and secret readability without requiring opt-in security controls}';

    protected $description = 'Check application hardening prerequisites without modifying production';

    public function handle(): int
    {
        if ($this->option('compatibility')) {
            return $this->checkCompatibility();
        }
        $checks = [
            'Production debug disabled' => app()->environment('production') && ! config('app.debug'),
            'Secure encrypted sessions' => config('session.secure') && config('session.http_only') && config('session.encrypt'),
            'Secret encryption enabled' => config('security.encrypt_secrets'),
            'Verified RouterOS TLS required' => config('security.require_router_tls'),
            'Encrypted LDAP required' => config('security.require_ldap_tls'),
            'Configured destination restrictions enabled' => config('security.restrict_configured_destinations'),
            'Resource and rate limits enabled' => config('security.enforce_resource_limits') && config('security.enforce_rate_limits'),
            'Credential redaction enabled' => config('security.redact_credentials'),
            'CSP enforced' => config('security.enforce_csp'),
            'Private attachment storage configured' => config('security.attachment_disk') !== 'public',
            'Strict API access enabled' => config('security.strict_api_access'),
            'Privileged MFA required' => config('security.require_mfa'),
            'Upload scanner installed' => config('security.upload_scan_required') && is_executable(config('security.upload_scanner')),
            'Runtime cannot modify source' => ! is_writable(app_path()) && ! is_writable(base_path('routes/web.php')),
        ];
        $insecureRouters = 0;
        $publicAttachments = 0;
        foreach (Tenant::query()->cursor() as $tenant) {
            $insecureRouters += Router::query()->where('tenant_id', $tenant->id)->where('api_port', 8728)->where('enable_provisioning', true)->count();
            foreach (TicketAttachment::query()->where('tenant_id', $tenant->id)->cursor() as $attachment) {
                if ($attachment->disk === 'public' || Storage::disk('public')->exists($attachment->path)) {
                    $publicAttachments++;
                }
            }
        }
        $checks['No plaintext RouterOS provisioning endpoints'] = $insecureRouters === 0;
        $checks['No public ticket attachments'] = $publicAttachments === 0;
        foreach ($checks as $name => $passed) {
            $this->line(($passed ? 'PASS ' : 'FAIL ').$name);
        }

        return in_array(false, $checks, true) ? self::FAILURE : self::SUCCESS;
    }

    private function checkCompatibility(): int
    {
        $ready = true;
        foreach (['users' => ['mfa_secret', 'mfa_recovery_codes', 'mfa_confirmed_at', 'mfa_last_counter'], 'invoice_items' => ['tenant_id'], 'user_settings' => ['tenant_id']] as $table => $columns) {
            $passed = Schema::hasColumns($table, $columns);
            $this->line(($passed ? 'PASS ' : 'FAIL ').$table.' compatibility columns');
            $ready = $ready && $passed;
        }
        foreach ([Router::class => ['api_password', 'coa_secret'], Subscription::class => ['pppoe_password'], Setting::class => ['value']] as $model => $columns) {
            $readable = true;
            foreach ($model::withoutGlobalScopes()->lazyById(100) as $record) {
                foreach ($columns as $column) {
                    $value = $record->getRawOriginal($column);
                    if ($value !== null && CompatibleSecretCast::isEncrypted($value)) {
                        try {
                            Crypt::decryptString($value);
                        } catch (DecryptException) {
                            $readable = false;
                        }
                    }
                }
            }
            $this->line(($readable ? 'PASS ' : 'FAIL ').(new $model)->getTable().' secret readability');
            $ready = $ready && $readable;
        }
        $this->line('External router/LDAP connectivity and custom integrations require separate verification.');

        return $ready ? self::SUCCESS : self::FAILURE;
    }
}
