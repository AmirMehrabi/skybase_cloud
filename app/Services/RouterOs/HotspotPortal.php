<?php

namespace App\Services\RouterOs;

use App\Models\Router;
use App\Models\Tenant;
use App\Services\OutboundConnectionPolicy;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class HotspotPortal
{
    public function __construct(private OutboundConnectionPolicy $connections) {}

    public function defaults(Tenant $tenant): array
    {
        return [
            'headline' => 'Connect to Wi-Fi',
            'message' => 'Sign in to get online.',
            'button' => 'Connect',
            'accent' => preg_match('/^#[0-9a-f]{6}$/iD', (string) $tenant->primary_color) ? $tenant->primary_color : '#0f766e',
            'layout' => 'light',
        ];
    }

    public function render(Tenant $tenant, array $design, ?string $preview = null): string
    {
        $logo = null;
        $disk = Storage::disk('public');
        foreach ([$tenant->company_logo, $tenant->login_logo, $tenant->company_logo_dark] as $path) {
            if ($path && $disk->exists($path) && $disk->size($path) <= 2 * 1024 * 1024) {
                $contents = $disk->get($path);
                $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents);
                if (in_array($mime, ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'], true)) {
                    $logo = 'data:'.$mime.';base64,'.base64_encode($contents);
                    break;
                }
            }
        }

        return view('modules.routers.hotspot.portal', [
            'brand' => $tenant->company_name ?: $tenant->name,
            'logo' => $logo,
            'design' => $design + $this->defaults($tenant),
            'preview' => $preview,
        ])->render();
    }

    public function prepare(Router $router, string $source, bool $flash, bool $chap, string $html, bool $upload): string
    {
        $source = trim($source, '/');
        if (! preg_match('~^[a-zA-Z0-9_-][a-zA-Z0-9_./ -]*$~D', $source) || preg_match('~(?:^|/)\.\.?(/|$)~', $source)) {
            throw new RuntimeException('The existing portal directory has a path SkyBase cannot safely copy.');
        }

        $parent = dirname($source);
        $release = 'skybase-'.strtolower((string) Str::ulid());
        $destination = ($parent !== '.' ? $parent.'/' : ($flash ? 'flash/' : '')).$release;
        $workspace = sys_get_temp_dir().'/'.$release;
        File::makeDirectory($workspace, 0700);
        try {
            $this->transfer($router, $workspace, 'get -R "'.$source.'" portal');
            if (! File::isFile($workspace.'/portal/login.html') || ($chap && ! File::isFile($workspace.'/portal/md5.js'))) {
                throw new RuntimeException('The current portal is missing login.html or the CHAP helper. Restore the HotSpot files in RouterOS and try again.');
            }

            if ($upload) {
                File::put($workspace.'/portal/login.html', $html);
                File::put($workspace.'/portal/flogin.html', $html);
                $this->transfer($router, $workspace, 'put -R portal "'.$destination."\"\n".'get "'.$destination.'/login.html" verified-login.html'."\n".'get "'.$destination.'/flogin.html" verified-error.html');
                if (File::get($workspace.'/verified-login.html') !== $html || File::get($workspace.'/verified-error.html') !== $html) {
                    throw new RuntimeException('The uploaded login pages could not be verified.');
                }
            }
        } finally {
            File::deleteDirectory($workspace);
        }

        return str_contains($destination, '/') ? '/'.$destination : $destination;
    }

    public function verifyRollback(Router $router, string $source): void
    {
        $this->prepare($router, $source, false, false, '', false);
    }

    private function transfer(Router $router, string $workspace, string $batch): void
    {
        $port = (int) ($router->ssh_port ?: 22);
        $host = $this->connections->resolve($router->ip_address, $port, $router->tenant_id, configuredDestination: true);
        $host = filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? '['.$host.']' : $host;
        $user = (string) $router->api_username;
        if (! preg_match('/^[a-zA-Z0-9_.@-]{1,128}$/D', $user)) {
            throw new RuntimeException('Set a valid RouterOS management username before transferring portal files.');
        }

        $process = new Process([
            'sftp', '-b', '-', '-P', (string) $port, '-o', 'BatchMode=yes',
            '-o', 'StrictHostKeyChecking=yes', '-o', 'HostKeyAlias='.$router->ip_address,
            '-o', 'ConnectTimeout=6', '-o', 'User='.$user, '--', $host,
        ], $workspace, input: $batch."\n", timeout: 14);
        try {
            $process->run();
        } catch (ProcessTimedOutException $exception) {
            throw new RuntimeException('The portal transfer timed out. Check the router connection and portal directory size.', previous: $exception);
        }
        if (! $process->isSuccessful()) {
            throw new RuntimeException('Portal file transfer failed. Check the router SSH service, trusted host key, management key, file permissions, and active directory.');
        }
    }
}
