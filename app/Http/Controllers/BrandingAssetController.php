<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BrandingAssetController extends Controller
{
    public function __invoke(string $asset): StreamedResponse
    {
        abort_unless(in_array($asset, Tenant::BRANDING_ASSETS, true), 404);

        $tenant = $this->currentTenant();

        abort_unless($tenant, 403);

        $path = $tenant->getAttribute($asset);

        abort_unless($path && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path);
    }

    public function settings(string $path): StreamedResponse
    {
        $path = 'settings/'.ltrim($path, '/');

        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path);
    }

    private function currentTenant(): ?Tenant
    {
        if (tenant()) {
            return tenant();
        }

        $user = auth()->user() ?? auth('customer')->user();

        if (! $user?->tenant_id) {
            return null;
        }

        return Tenant::query()->find($user->tenant_id);
    }
}
