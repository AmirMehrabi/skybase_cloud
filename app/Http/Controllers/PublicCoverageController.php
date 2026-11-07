<?php

namespace App\Http\Controllers;

use App\Models\CoverageArea;
use App\Models\CoverageRevision;
use App\Models\Setting;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicCoverageController extends Controller
{
    public function sharing(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isOwner() && $request->user()->tenant_id === tenant()->id, 403);
        if ($request->isMethod('put')) {
            Setting::set('coverage.public', true, 'boolean', tenant()->id);
        } else {
            Setting::query()->where('tenant_id', tenant()->id)->where('key', 'coverage.public')->delete();
        }

        return to_route('sites.index');
    }

    public function map(Tenant $tenant): View
    {
        $this->authorizePublication($tenant);

        return view('modules.sites.public-map', ['tenant' => $tenant]);
    }

    public function feed(Tenant $tenant): JsonResponse
    {
        $this->authorizePublication($tenant);
        $published = CoverageArea::query()->where('tenant_id', $tenant->id)->select('published_revision_id')->whereNotNull('published_revision_id');
        $features = CoverageRevision::query()->where('tenant_id', $tenant->id)->whereIn('id', $published)->orderBy('id')->get(['coverage_area_id', 'snapshot'])->map(function (CoverageRevision $revision): array {
            $snapshot = $revision->snapshot;

            return ['type' => 'Feature', 'id' => $revision->coverage_area_id, 'geometry' => ['type' => $snapshot['geometry']['type'], 'coordinates' => $snapshot['geometry']['coordinates']], 'properties' => collect($snapshot)->only(['name', 'technology', 'availability', 'evidence', 'verified_on'])->all()];
        });

        return response()->json(['type' => 'FeatureCollection', 'features' => $features], 200, ['Access-Control-Allow-Origin' => '*', 'Cache-Control' => 'no-store', 'Content-Type' => 'application/geo+json']);
    }

    private function authorizePublication(Tenant $tenant): void
    {
        abort_unless($tenant->isActive() && ! $tenant->application_access_suspended && ! $tenant->archived_at && Setting::get('coverage.public', false, $tenant->id), 404);
    }
}
