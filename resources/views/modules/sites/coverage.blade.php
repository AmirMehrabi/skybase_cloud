@extends('layouts.admin')
@section('title', 'Coverage · '.$site->name)
@section('content')
@php
$config = ['areas'=>$areas, 'latitude'=>(float)$site->latitude, 'longitude'=>(float)$site->longitude, 'name'=>$site->name, 'saveUrl'=>route('sites.coverage.save',$site), 'csrf'=>csrf_token(), 'tiles'=>config('coverage.tiles'), 'attribution'=>config('coverage.attribution')];
@endphp
<div x-data="siteCoverage(@js($config))" class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div><a href="{{ route('sites.show',$site) }}" class="text-sm text-blue-600">← {{ $site->name }}</a><h1 class="text-2xl font-bold mt-2">Coverage</h1><p class="text-sm text-gray-500">Draw a service area, review it, then publish it for your team.</p></div>
        <button type="button" @click="create()" :disabled="busy" class="rounded-lg border px-4 py-2 bg-white">New area</button>
    </div>
    <p role="alert" x-show="error" x-text="error" class="rounded-lg bg-red-50 text-red-800 p-3" style="display:none"></p>
    <p role="status" x-show="notice" x-text="notice" class="rounded-lg bg-blue-50 text-blue-800 p-3" style="display:none"></p>
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <div class="xl:col-span-2 space-y-3">
            <div class="flex flex-wrap gap-2" aria-label="Drawing tools">
                <button type="button" @click="draw('polygon')" :disabled="busy" class="border rounded-lg px-3 py-2 bg-white">Draw polygon</button>
                <button type="button" @click="draw('hole')" :disabled="busy || form.geometry?.type !== 'Polygon'" class="border rounded-lg px-3 py-2 bg-white disabled:opacity-40">Exclude area</button>
                <button type="button" @click="draw('part')" :disabled="busy || !form.geometry" class="border rounded-lg px-3 py-2 bg-white disabled:opacity-40">Add separate area</button>
                <button type="button" @click="undo()" :disabled="busy" class="border rounded-lg px-3 py-2 bg-white">Undo</button>
                <template x-if="mode"><span class="flex gap-2"><button type="button" @click="finish()" class="rounded-lg px-3 py-2 bg-blue-600 text-white">Finish</button><button type="button" @click="mode=''; points=[]; render()" class="border rounded-lg px-3 py-2 bg-white">Cancel drawing</button></span></template>
            </div>
            <div x-ref="map" class="rounded-xl border z-0" style="height:560px" aria-label="Coverage drawing map"></div>
            <p class="text-sm font-medium" x-text="measurements()"></p>
            <p class="text-xs text-gray-500">Blue: selected area. Gray: other published areas. Drag a corner to adjust it; right-click a corner to remove it. Drawn and estimated coverage may require a site survey.</p>
            <div class="bg-white rounded-xl border p-4 space-y-2">
                <h2 class="font-semibold">Saved areas</h2>
                <p x-show="!areas.length" class="text-sm text-gray-500">Your first area starts here. Draw its boundary above and save a draft.</p>
                <template x-for="area in areas" :key="area.id"><button type="button" @click="open(area)" :disabled="busy" class="w-full flex justify-between gap-2 text-left border rounded-lg p-3" :class="selected?.id === area.id ? 'border-blue-500 bg-blue-50' : 'border-gray-200'"><span x-text="area.revisions[0].snapshot.name"></span><span class="text-xs text-gray-500" x-text="area.revisions[0].snapshot.status + (area.published_revision_id ? ' · live' : '')"></span></button></template>
            </div>
        </div>
        <form @submit.prevent="save('draft')" @input="dirty=true" @change="dirty=true" class="bg-white border rounded-xl p-5 space-y-4 self-start">
            <h2 class="font-semibold" x-text="selected ? 'Edit coverage' : 'New coverage area'"></h2>
            <p x-show="dirty" class="text-xs text-amber-700">Unsaved changes</p>
            <fieldset :disabled="busy" class="space-y-4">
            <x-ui.input.text name="coverage_name" label="Area name" x-model="form.name" required placeholder="e.g. North Tower residential" />
            <x-ui.input.select name="technology" label="Technology" x-model="form.technology" :options="['wireless'=>'Fixed wireless','fiber'=>'Fiber','other'=>'Other']" />
            <x-ui.input.select name="access_point_id" label="Access point (optional)" x-model="form.access_point_id" :options="$accessPoints->pluck('name','id')->all()" />
            <x-ui.input.select name="evidence" label="Coverage evidence" x-model="form.evidence" :options="['declared'=>'Manually declared','estimated'=>'Estimated','verified'=>'Field verified']" />
            <div x-show="form.evidence === 'verified'"><x-ui.input.text name="verified_on" type="date" label="Verified on" x-model="form.verified_on" /></div>
            <x-ui.input.select name="availability" label="Service availability" x-model="form.availability" :options="['survey_required'=>'Survey required','available'=>'Available','constrained'=>'Capacity constrained','unavailable'=>'Unavailable']" />
            <details class="border rounded-lg p-3"><summary class="cursor-pointer text-sm font-medium">Generate radius or sector</summary><div class="space-y-3 mt-3">
                <x-ui.input.select name="shape_type" label="Shape" x-model="form.shape.type" :options="['polygon'=>'Polygon','circle'=>'Radius','sector'=>'Directional sector']" />
                <x-ui.input.text name="radius" type="number" label="Radius (meters)" x-model.number="form.shape.radius" min="1" max="100000" />
                <x-ui.input.text name="azimuth" type="number" label="Bearing (degrees clockwise from north)" x-model.number="form.shape.azimuth" min="0" max="360" />
                <x-ui.input.text name="angle" type="number" label="Beam angle (degrees)" x-model.number="form.shape.angle" min="1" max="360" />
                <button type="button" @click="generate()" :disabled="form.shape.type === 'polygon'" class="border rounded-lg px-3 py-2 disabled:opacity-40">Preview shape</button>
            </div></details>
            <x-ui.input.textarea name="notes" label="Notes / reason for change" x-model="form.notes" />
            <div class="flex flex-wrap gap-2"><button type="submit" class="border rounded-lg px-4 py-2" x-text="busy ? 'Saving…' : 'Save draft'"></button><button type="button" @click="save('published')" class="bg-blue-600 text-white rounded-lg px-4 py-2">Publish</button><button type="button" x-show="selected" @click="save('archived')" class="text-gray-600 px-2 py-2">Archive</button></div>
            <details class="border rounded-lg p-3"><summary class="cursor-pointer text-sm">Import / export GeoJSON</summary><div class="mt-3 space-y-2"><x-ui.input.textarea name="geojson" label="Paste Polygon or MultiPolygon GeoJSON (up to 200 KB)" x-model="importText" /><button type="button" @click="importGeometry()" class="border rounded-lg px-3 py-2">Preview import</button><button type="button" @click="exportGeometry()" :disabled="!form.geometry" class="border rounded-lg px-3 py-2">Export selected</button></div></details>
            <details x-show="selected" class="border rounded-lg p-3"><summary class="cursor-pointer text-sm">Revision history</summary><template x-for="revision in selected?.revisions || []" :key="revision.id"><div class="py-3 border-b text-sm"><p x-text="new Date(revision.created_at).toLocaleString()"></p><p class="text-xs text-gray-500" x-text="revision.snapshot.status + ' · User ' + (revision.author_id || 'removed')"></p><p x-text="revision.snapshot.notes"></p><button type="button" @click="restore(revision)" class="text-blue-600 mt-1">Restore as draft</button></div></template></details>
            </fieldset>
        </form>
    </div>
</div>
@endsection
