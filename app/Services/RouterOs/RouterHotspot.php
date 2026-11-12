<?php

namespace App\Services\RouterOs;

use App\Models\Router;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

class RouterHotspot
{
    public const METHODS = ['http-chap', 'https', 'http-pap', 'cookie', 'mac', 'mac-cookie', 'trial'];

    public function __construct(private RouterOsClient $client) {}

    public function profiles(Router $router): array
    {
        return $this->client->execute($router, function ($connection): array {
            $profiles = $this->command($connection, ['/ip/hotspot/profile/print', '=.proplist=.id,name,dns-name,hotspot-address,login-by,use-radius,ssl-certificate,https-redirect,html-directory,html-directory-override,split-user-domain,radius-accounting,radius-interim-update,radius-location-id,radius-location-name,radius-default-domain,nas-port-type,http-cookie-lifetime,http-proxy,trial-uptime-limit,trial-uptime-reset,trial-user-profile,mac-auth-mode,smtp-server']);
            $servers = collect($this->command($connection, ['/ip/hotspot/print', '=.proplist=.id,name,profile,interface,disabled']))->groupBy('profile');

            return array_map(function (array $profile) use ($servers): array {
                $profile['networks'] = $servers->get($profile['name'], collect())->sortBy('name')->map(function (array $server): array {
                    ksort($server);

                    return $server;
                })->values()->all();
                ksort($profile);
                $profile['revision'] = hash('sha256', json_encode($profile, JSON_THROW_ON_ERROR));

                return $profile;
            }, $profiles);
        }, 8);
    }

    public function current(Router $router, string $id, string $revision): array
    {
        $profile = collect($this->profiles($router))->first(fn (array $entry): bool => $entry['.id'] === $id);

        if (! $profile || ! hash_equals($profile['revision'], $revision)) {
            throw ValidationException::withMessages(['profile' => 'The HotSpot profile changed on RouterOS. Sync profiles and review it before retrying.']);
        }

        return $profile;
    }

    public function write(Router $router, ?string $id, array $settings): void
    {
        $command = [$id ? '/ip/hotspot/profile/set' : '/ip/hotspot/profile/add'];
        if ($id) {
            $command[] = '=.id='.$id;
        }
        foreach ($settings as $key => $value) {
            $command[] = '='.$key.'='.$value;
        }
        $this->execute($router, $command);
    }

    public function assign(Router $router, array $profile, array $source, string $server): void
    {
        if (! collect($source['networks'])->contains(fn (array $entry): bool => $entry['.id'] === $server)) {
            throw ValidationException::withMessages(['profile' => 'The HotSpot server moved to another profile. Sync before assigning it.']);
        }

        $this->execute($router, ['/ip/hotspot/set', '=.id='.$server, '=profile='.$profile['name']]);
    }

    public function delete(Router $router, array $profile): void
    {
        if ($profile['name'] === 'default' || $profile['networks']) {
            throw ValidationException::withMessages(['profile' => 'Remove all server assignments before deleting a profile. The default profile cannot be deleted.']);
        }

        $this->execute($router, ['/ip/hotspot/profile/remove', '=.id='.$profile['.id']]);
    }

    public function state(Router $router): array
    {
        $path = $this->statePath($router);

        return File::exists($path) ? json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR) : [];
    }

    public function save(Router $router, array $state): void
    {
        $path = $this->statePath($router);
        File::ensureDirectoryExists(dirname($path), 0700);
        File::replace($path, json_encode($state, JSON_THROW_ON_ERROR), 0600);
    }

    public function directory(array $profile): string
    {
        return ($profile['html-directory-override'] ?? '') ?: (($profile['html-directory'] ?? '') ?: 'hotspot');
    }

    public function activate(Router $router, array $profile, array $paths): void
    {
        $this->current($router, $profile['.id'], $profile['revision']);
        $this->write($router, $profile['.id'], $paths);
        $actual = collect($this->profiles($router))->first(fn (array $entry): bool => $entry['.id'] === $profile['.id']);

        foreach ($paths as $key => $value) {
            if (! $actual || trim($actual[$key] ?? '', '/') !== trim($value, '/')) {
                throw ValidationException::withMessages(['profile' => 'RouterOS did not confirm the active directory. Sync profiles to check what is live.']);
            }
        }
    }

    public function hasFlash(Router $router): bool
    {
        return $this->execute($router, ['/file/print', '=.proplist=name', '?name=flash']) !== [];
    }

    private function statePath(Router $router): string
    {
        return storage_path('app/private/hotspot/'.hash('sha256', $router->tenant_id.':'.$router->getKey()).'.json');
    }

    private function execute(Router $router, array $words): array
    {
        return $this->client->execute($router, fn ($connection): array => $this->command($connection, $words), 8);
    }

    private function command(mixed $connection, array $words): array
    {
        $this->client->writeSentence($connection, $words);

        return $this->client->readResponse($connection);
    }
}
