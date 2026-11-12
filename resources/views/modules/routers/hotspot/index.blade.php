@extends('layouts.admin')

@section('title', 'HotSpot login page')

@section('content')
<div class="mx-auto max-w-7xl space-y-6" x-data="hotspotEditor({{ Js::from([
    'syncUrl' => route('routers.hotspot.sync', $router),
    'actionUrl' => route('routers.hotspot.mutate', $router),
    'csrf' => csrf_token(),
    'defaults' => $defaults,
    'preview' => $preview,
]) }})" x-init="sync()" x-cloak>
    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <a class="text-sm font-semibold text-emerald-700 hover:underline" href="{{ route('routers.show', $router) }}">&larr; {{ $router->name }}</a>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">HotSpot login page</h1>
            <p class="mt-1 text-sm text-slate-600">Manage HotSpot profiles and the page customers see before connecting.</p>
        </div>
        <button type="button" @click="sync()" :disabled="busy" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold disabled:opacity-50" x-text="busy === 'sync' ? 'Syncing…' : 'Sync from RouterOS'"></button>
    </header>

    <p x-show="message" role="status" class="rounded-xl border px-4 py-3 text-sm" :class="failed ? 'border-red-200 bg-red-50 text-red-900' : 'border-emerald-200 bg-emerald-50 text-emerald-900'" x-text="message"></p>

    <div class="grid items-start gap-6 lg:grid-cols-[280px_minmax(0,1fr)]">
        <aside class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="flex items-center justify-between gap-2">
                <h2 class="font-semibold text-slate-950">HotSpot profiles</h2>
                @if ($canWrite)
                    <button type="button" :disabled="busy || !online" @click="openSettings(true)" class="text-sm font-semibold text-emerald-700 disabled:opacity-50">+ New</button>
                @endif
            </div>
            <p class="mt-1 text-xs text-slate-500" x-text="online ? 'Synced with RouterOS' : 'RouterOS connection unavailable'"></p>
            <nav class="mt-4 grid gap-2" aria-label="HotSpot profiles">
                <template x-for="profile in profiles" :key="profile['.id']">
                    <button type="button" :disabled="busy" @click="select(profile['.id'])" :aria-current="selected === profile['.id'] ? 'true' : null" class="rounded-xl border p-3 text-left" :class="selected === profile['.id'] ? 'border-emerald-600 bg-emerald-50' : 'border-slate-200 hover:bg-slate-50'">
                        <span class="block break-all text-sm font-semibold" x-text="profile.name"></span>
                        <span class="mt-1 block text-xs text-slate-600" x-text="profile.networks.length + (profile.networks.length === 1 ? ' HotSpot server' : ' HotSpot servers')"></span>
                    </button>
                </template>
            </nav>
            <p x-show="!profiles.length" class="mt-2 text-sm text-slate-600" x-text="online ? 'No HotSpot profiles found.' : 'Sync to load the profiles configured on this router.'"></p>
        </aside>

        <div class="min-w-0 space-y-5">
            <section x-show="active && !settingsOpen" class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold" x-text="active?.name"></h2>
                        <p class="mt-1 text-sm text-slate-600" x-text="active?.live ? (active.live.stage ? 'Test design live' : 'SkyBase design live') + (active.live.verified ? ' · Device tested' : ' · Device test pending') : 'Router-managed login page'"></p>
                        <p class="mt-2 break-all text-xs text-slate-500" x-text="active?.directory"></p>
                    </div>
                    <button type="button" :disabled="busy || !online" @click="openSettings(false)" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold disabled:opacity-50">Profile settings</button>
                </div>
                <p class="mt-3 text-sm text-slate-600" x-text="affected(active)"></p>
                @if ($canWrite)
                    <button type="button" x-show="assignableServers.length" :disabled="busy || !online" @click="serverId = assignableServers[0]?.network['.id']; review = 'assign'" class="mt-3 text-sm font-semibold text-emerald-700 disabled:opacity-50">Assign a HotSpot server</button>
                @endif
            </section>

            <section x-show="settingsOpen" class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold" x-text="creating ? 'New HotSpot profile' : 'Profile settings'"></h2>
                    <button type="button" @click="settingsOpen = false" class="text-sm font-semibold text-slate-600">Close</button>
                </div>
                <form @submit.prevent="saveProfile()" class="space-y-4">
                    <fieldset :disabled="busy || {{ Js::from(!$canWrite) }}">
                        <div class="grid gap-x-4 sm:grid-cols-2">
                            <x-input.text name="profile_name" label="Profile name" x-model="settings.name" required />
                            <x-input.text name="profile_dns" label="Portal DNS name" x-model="settings['dns-name']" />
                            <x-input.text name="profile_address" label="HotSpot address" x-model="settings['hotspot-address']" />
                            <x-input.text name="profile_certificate" label="TLS certificate name" x-model="settings['ssl-certificate']" />
                            <x-input.select name="profile_radius" label="Use RADIUS" :options="['no' => 'No', 'yes' => 'Yes']" x-model="settings['use-radius']" />
                            <x-input.select name="profile_redirect" label="HTTPS redirect" :options="['yes' => 'Yes', 'no' => 'No']" x-model="settings['https-redirect']" />
                        </div>
                        <fieldset class="grid gap-2 rounded-xl border border-slate-200 p-4 sm:grid-cols-3">
                            <legend class="px-2 text-sm font-semibold">Login methods</legend>
                            @foreach (\App\Services\RouterOs\RouterHotspot::METHODS as $method)
                                <x-input.checkbox :id="'method_'.$method" :name="'method_'.$method" :value="$method" :label="$method" x-model="settings['login-by']" />
                            @endforeach
                        </fieldset>
                        <details class="mt-4 rounded-xl border border-slate-200 p-4">
                            <summary class="cursor-pointer text-sm font-semibold">Advanced RouterOS settings</summary>
                            <div class="mt-4 grid gap-x-4 sm:grid-cols-2">
                                @foreach (['html-directory', 'html-directory-override', 'split-user-domain', 'radius-accounting', 'radius-interim-update', 'radius-location-id', 'radius-location-name', 'radius-default-domain', 'nas-port-type', 'http-cookie-lifetime', 'http-proxy', 'trial-uptime-limit', 'trial-uptime-reset', 'trial-user-profile', 'mac-auth-mode', 'smtp-server'] as $setting)
                                    <x-input.text :name="'profile_'.$setting" :label="ucfirst(str_replace('-', ' ', $setting))" :x-model="'settings['.json_encode($setting).']'" />
                                @endforeach
                            </div>
                        </details>
                    </fieldset>
                    @if ($canWrite)
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <button type="submit" :disabled="busy || !online" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50" x-text="creating ? 'Create on RouterOS' : 'Save to RouterOS'"></button>
                            <button type="button" x-show="!creating && active?.name !== 'default' && !active?.networks.length" :disabled="busy || !online" @click="review = 'delete'" class="rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 disabled:opacity-50">Delete profile</button>
                        </div>
                    @endif
                </form>
            </section>

            <section x-show="active && !settingsOpen" class="grid gap-5 xl:grid-cols-[minmax(240px,.8fr)_minmax(0,1.2fr)]">
                <div class="space-y-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5">
                        <h2 class="text-lg font-semibold">Design</h2>
                        <p class="mb-4 mt-1 text-sm text-slate-600">Your workspace name and logo are included.</p>
                        <fieldset :disabled="busy || {{ Js::from(!$canWrite && !$canDeploy) }}">
                            <x-input.text name="portal_headline" label="Headline" x-model="design.headline" />
                            <x-input.textarea name="portal_message" label="Welcome message" x-model="design.message" rows="2" />
                            <x-input.text name="portal_button" label="Button label" x-model="design.button" />
                            <x-input.select name="portal_layout" label="Appearance" :options="['light' => 'Light', 'dark' => 'Dark']" x-model="design.layout" />
                            <x-input.color name="portal_accent" label="Accent color" x-model="design.accent" />
                        </fieldset>
                        <div class="flex flex-wrap items-center gap-3">
                            @if ($canWrite)
                                <button type="button" :disabled="busy" @click="run('draft', {design}, {keepDesign: true})" class="rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white disabled:opacity-50">Save draft</button>
                            @endif
                            <button type="button" :disabled="busy" @click="design = {...config.defaults}" class="text-sm font-semibold text-slate-600 disabled:opacity-50">Reset to default</button>
                        </div>
                    </div>
                    @if ($canDeploy)
                        <div class="space-y-3 rounded-2xl border border-slate-200 bg-white p-5">
                            <h2 class="text-lg font-semibold">Test and publish</h2>
                            <p class="text-sm text-slate-600">Check the files, then use an isolated HotSpot to test the actual sign-in.</p>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" :disabled="busy || !online" @click="run('check', {design}, {keepDesign: true})" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold disabled:opacity-50">Check readiness</button>
                                <button type="button" :disabled="busy || !online || !testTargets.length" @click="stageId = testTargets[0]?.['.id']; review = 'stage'" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold disabled:opacity-50">Stage on test profile</button>
                                <button type="button" :disabled="busy || !online" @click="review = 'publish'" class="rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white disabled:opacity-50">Publish</button>
                            </div>
                            <p x-show="active?.live" class="text-sm text-slate-600">On a device, join the affected network, check the captive popup, try an invalid login, then sign in and open a website.</p>
                            <div x-show="active?.live" class="flex flex-wrap gap-3">
                                <button type="button" x-show="!active?.live?.verified" :disabled="busy || !online" @click="run('verify')" class="text-sm font-semibold text-emerald-700 disabled:opacity-50">Mark device tested</button>
                                <button type="button" :disabled="busy || !online" @click="review = 'restore'" class="text-sm font-semibold text-slate-700 disabled:opacity-50">Restore previous portal</button>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-4">
                        <h2 class="font-semibold">Preview</h2>
                        <div class="flex flex-wrap gap-2" aria-label="Preview size">
                            <template x-for="size in ['Phone', 'Desktop']" :key="size">
                                <button type="button" @click="device = size" :aria-pressed="device === size" class="rounded-lg px-3 py-1.5 text-xs font-semibold" :class="device === size ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700'" x-text="size"></button>
                            </template>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 border-b border-slate-200 px-4 py-3" aria-label="Preview state">
                        <template x-for="state in ['Sign in', 'Invalid login', 'Connected']" :key="state">
                            <button type="button" @click="previewState = state" :aria-pressed="previewState === state" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold" :class="previewState === state ? 'bg-emerald-100 text-emerald-900' : 'text-slate-600 hover:bg-slate-100'" x-text="state"></button>
                        </template>
                    </div>
                    <div class="flex justify-center bg-slate-100 p-3 sm:p-5">
                        <iframe title="Captive portal preview" sandbox :srcdoc="previewHtml" class="h-[530px] w-full rounded-xl border border-slate-300 bg-white" :class="device === 'Phone' ? 'max-w-[375px]' : ''"></iframe>
                    </div>
                </div>
            </section>
            <section x-show="!active && !settingsOpen" class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center">
                <h2 class="font-semibold" x-text="busy ? 'Reading profiles from RouterOS…' : 'Choose a HotSpot profile'"></h2>
                <p class="mt-2 text-sm text-slate-600">Each profile has its own login page and may serve multiple networks.</p>
            </section>
        </div>
    </div>

    <dialog x-ref="reviewDialog" @close="review = ''" class="m-auto w-[calc(100%-2rem)] max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-xl backdrop:bg-slate-900/50">
        <h2 class="text-xl font-semibold" x-text="({stage: 'Stage on a test network', publish: 'Publish this design?', restore: 'Restore the previous portal?', delete: 'Delete this profile?', assign: 'Assign a HotSpot server'})[review]"></h2>
        <p x-show="review === 'stage'" class="mt-2 text-sm text-slate-600">Choose an isolated test network. This replaces the login page on that network.</p>
        <div x-show="review === 'stage'" class="mt-3 grid gap-2">
            <template x-for="target in testTargets" :key="target['.id']">
                <button type="button" @click="stageId = target['.id']" :aria-pressed="stageId === target['.id']" class="rounded-lg border p-3 text-left text-sm" :class="stageId === target['.id'] ? 'border-emerald-600 bg-emerald-50' : 'border-slate-200'" x-text="target.name + ' · ' + target.networks[0].interface"></button>
            </template>
        </div>
        <div x-show="review === 'assign'" class="mt-3 grid gap-2">
            <p class="text-sm text-slate-600">Move a server to <span class="font-semibold" x-text="active?.name"></span>. Its customers will use this profile’s portal and sign-in settings.</p>
            <template x-for="{network, profile} in assignableServers" :key="network['.id']">
                <button type="button" @click="serverId = network['.id']" :aria-pressed="serverId === network['.id']" class="rounded-lg border p-3 text-left text-sm" :class="serverId === network['.id'] ? 'border-emerald-600 bg-emerald-50' : 'border-slate-200'" x-text="network.name + ' · ' + network.interface + ' · ' + profile.name"></button>
            </template>
        </div>
        <p x-show="!['stage', 'assign'].includes(review)" class="mt-2 text-sm text-slate-600" x-text="review === 'delete' ? 'RouterOS will remove ' + active?.name + '.' : affected(active)"></p>
        <div class="mt-5 flex justify-end gap-3">
            <button type="button" @click="review = ''" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold">Cancel</button>
            <button type="button" @click="confirm()" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white" x-text="({stage: 'Stage design', publish: 'Publish design', restore: 'Restore portal', delete: 'Delete profile', assign: 'Assign server'})[review]"></button>
        </div>
    </dialog>
</div>

<script src="{{ asset('js/routers/hotspot.js') }}"></script>
@endsection
