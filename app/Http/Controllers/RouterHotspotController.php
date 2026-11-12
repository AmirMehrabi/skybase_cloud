<?php

namespace App\Http\Controllers;

use App\Http\Requests\Router\HotspotRequest;
use App\Models\Router;
use App\Services\RouterOs\HotspotPortal;
use App\Services\RouterOs\RouterHotspot;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class RouterHotspotController extends Controller
{
    public function __construct(private RouterHotspot $hotspot, private HotspotPortal $portal) {}

    public function show(Router $router): View
    {
        $this->authorizeRouter($router);

        return view('modules.routers.hotspot.index', [
            'router' => $router,
            'defaults' => $this->portal->defaults(tenant()),
            'preview' => $this->portal->render(tenant(), [], 'preview'),
            'canWrite' => auth()->user()->hasPermission('routers.write') || auth()->user()->isCentralAdmin(),
            'canDeploy' => auth()->user()->hasPermission('routers.actions') || auth()->user()->isCentralAdmin(),
        ]);
    }

    public function sync(Router $router): JsonResponse
    {
        $this->authorizeRouter($router);
        $state = $this->hotspot->state($router);

        return $this->routerResponse(function () use ($router, $state): JsonResponse {
            $profiles = $this->hotspot->profiles($router);
            foreach ($profiles as &$profile) {
                $directory = trim($this->hotspot->directory($profile), '/');
                $record = $state['profiles'][$profile['.id']] ?? [];
                $profile['draft'] = $record['draft'] ?? null;
                $profile['live'] = $record['releases'][$directory] ?? null;
                $profile['directory'] = $directory;
            }

            return response()->json(['profiles' => $profiles]);
        });
    }

    public function mutate(HotspotRequest $request, Router $router): JsonResponse
    {
        $this->authorizeRouter($router);
        $data = $request->validated();
        $lock = Cache::lock('hotspot:'.$router->tenant_id.':'.$router->id, 120);

        if (! $lock->get()) {
            return response()->json(['message' => 'Another HotSpot change is in progress. Try again when it finishes.'], 409);
        }

        try {
            return $this->routerResponse(fn (): JsonResponse => response()->json(['message' => $this->apply($router, $data)]));
        } finally {
            $lock->release();
        }
    }

    private function apply(Router $router, array $data): string
    {
        $action = $data['action'];
        $state = $this->hotspot->state($router);

        if ($action === 'draft') {
            $state['profiles'][$data['profile']]['draft'] = $data['design'];
            $this->hotspot->save($router, $state);

            return 'Draft saved.';
        }

        $profile = $action === 'create' ? null : $this->hotspot->current($router, $data['profile'], $data['revision']);

        if (in_array($action, ['create', 'update'], true)) {
            $settings = $data['settings'];
            $settings['login-by'] = implode(',', $settings['login-by']);
            $settings = array_map(fn (?string $value): string => $value ?? '', $settings);
            if ($profile) {
                $settings = array_filter($settings, fn (string $value, string $key): bool => $value !== ($profile[$key] ?? ''), ARRAY_FILTER_USE_BOTH);
            }
            if ($settings !== []) {
                $this->hotspot->write($router, $profile['.id'] ?? null, $settings);
            }

            return $profile ? 'Profile updated on RouterOS.' : 'Profile created on RouterOS.';
        }

        if ($action === 'assign') {
            $source = $this->hotspot->current($router, $data['source'], $data['source_revision']);
            $this->hotspot->assign($router, $profile, $source, $data['server']);

            return 'HotSpot server assigned on RouterOS.';
        }

        $id = $profile['.id'];
        if ($action === 'delete') {
            $this->hotspot->delete($router, $profile);
            unset($state['profiles'][$id]);
            $this->hotspot->save($router, $state);

            return 'Profile deleted from RouterOS.';
        }

        $source = $this->hotspot->directory($profile);
        $directory = trim($source, '/');
        $live = $state['profiles'][$id]['releases'][$directory] ?? null;

        if ($action === 'restore' || $action === 'verify') {
            if (! $live) {
                throw ValidationException::withMessages(['profile' => 'Sync the profile and select a SkyBase release.']);
            }

            if ($action === 'verify') {
                $state['profiles'][$id]['releases'][$directory]['verified'] = now()->toIso8601String();
                $this->hotspot->save($router, $state);

                return 'Device test recorded for the live release.';
            }

            $this->portal->verifyRollback($router, $live['previous']['source']);
            $this->hotspot->activate($router, $profile, $live['previous']['paths']);

            return 'Previous portal restored on RouterOS.';
        }

        if (! array_intersect(explode(',', $profile['login-by'] ?? ''), ['http-chap', 'http-pap', 'https'])) {
            throw ValidationException::withMessages(['profile' => 'Enable a username and password login method on this profile first.']);
        }

        $publish = $action === 'publish';
        if ($publish && ($data['stage'] ?? false) && (count($profile['networks']) !== 1 || in_array($profile['networks'][0]['disabled'] ?? '', ['yes', 'true'], true))) {
            throw ValidationException::withMessages(['profile' => 'Select an isolated test profile assigned to exactly one HotSpot server.']);
        }

        $release = $this->portal->prepare(
            $router,
            $source,
            $publish && $this->hotspot->hasFlash($router),
            in_array('http-chap', explode(',', $profile['login-by'] ?? ''), true),
            $this->portal->render(tenant(), $data['design']),
            $publish,
        );

        if (! $publish) {
            return 'Readiness check passed. The router files and transfer access are available.';
        }

        $paths = array_replace(['html-directory' => 'hotspot', 'html-directory-override' => ''], array_intersect_key($profile, array_flip(['html-directory', 'html-directory-override'])));
        $property = $paths['html-directory-override'] ? 'html-directory-override' : 'html-directory';
        $state['profiles'][$id]['releases'][trim($release, '/')] = [
            'created' => now()->toIso8601String(),
            'stage' => (bool) ($data['stage'] ?? false),
            'design' => $data['design'],
            'previous' => ['source' => $source, 'paths' => $paths],
        ];
        $this->hotspot->save($router, $state);
        $this->hotspot->activate($router, $profile, [$property => $release]);

        return ($data['stage'] ?? false) ? 'Design staged. Join the test network to verify sign-in.' : 'Design published to RouterOS.';
    }

    private function authorizeRouter(Router $router): void
    {
        abort_unless(tenant()?->id && $router->tenant_id === tenant()->id && auth()->user()->tenant_id === $router->tenant_id, 403);
        abort_unless($router->isMikrotik(), 404);
    }

    private function routerResponse(callable $operation): JsonResponse
    {
        try {
            return $operation();
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (RuntimeException $exception) {
            report($exception);

            return response()->json(['message' => 'Router operation failed. '.$exception->getMessage()], 502);
        }
    }
}
