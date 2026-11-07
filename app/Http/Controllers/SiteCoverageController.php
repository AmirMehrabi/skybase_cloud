<?php

namespace App\Http\Controllers;

use App\Http\Requests\Site\SaveCoverageRequest;
use App\Models\CoverageArea;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class SiteCoverageController extends Controller
{
    public function index(Site $site): View
    {
        return view('modules.sites.coverage', ['site' => $site, 'areas' => $this->areas($site), 'accessPoints' => $site->accessPoints()->orderBy('name')->get(['id', 'name', 'azimuth', 'coverage_angle'])]);
    }

    public function save(SaveCoverageRequest $request, Site $site, ?int $area = null): JsonResponse
    {
        $data = $request->validated();
        $result = CoverageArea::resolveConnection()->transaction(function () use ($site, $area, $data, $request): CoverageArea {
            $record = $area ? CoverageArea::query()->where('site_id', $site->id)->lockForUpdate()->findOrFail($area) : new CoverageArea(['tenant_id' => $site->tenant_id, 'site_id' => $site->id, 'version' => 0]);
            abort_if($record->version !== $data['version'], 409, 'This area was changed by someone else. Reload before saving.');
            $record->version++;
            $record->save();
            unset($data['version']);
            $revision = $record->revisions()->create(['tenant_id' => $site->tenant_id, 'author_id' => $request->user()->id, 'snapshot' => $data]);
            if ($data['status'] === 'published') {
                $record->published_revision_id = $revision->id;
            }
            if ($data['status'] === 'archived') {
                $record->published_revision_id = null;
            }
            $record->save();

            return $record;
        });

        return response()->json(['id' => $result->id, 'areas' => $this->areas($site)]);
    }

    private function areas(Site $site): array
    {
        return CoverageArea::query()->where('site_id', $site->id)->with(['revisions' => fn ($q) => $q->orderByDesc('id')])->get()->toArray();
    }
}
