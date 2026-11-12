<?php

namespace App\Http\Requests\Router;

use App\Services\RouterOs\RouterHotspot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HotspotRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = in_array($this->input('action'), ['check', 'publish', 'restore', 'verify'], true) ? 'routers.actions' : 'routers.write';

        return $this->user()->hasPermission($permission) || $this->user()->isCentralAdmin();
    }

    public function rules(): array
    {
        $action = $this->input('action');

        return [
            'action' => ['required', Rule::in(['create', 'update', 'delete', 'assign', 'draft', 'check', 'publish', 'restore', 'verify'])],
            'profile' => [Rule::requiredIf($action !== 'create'), 'nullable', 'regex:/^\*[a-f0-9]+$/iD'],
            'revision' => [Rule::requiredIf($action !== 'create'), 'nullable', 'regex:/^[a-f0-9]{64}$/D'],
            ...match ($action) {
                'create', 'update' => [
                    'settings' => ['required', 'array:name,dns-name,hotspot-address,ssl-certificate,login-by,use-radius,https-redirect,html-directory,html-directory-override,split-user-domain,radius-accounting,radius-interim-update,radius-location-id,radius-location-name,radius-default-domain,nas-port-type,http-cookie-lifetime,http-proxy,trial-uptime-limit,trial-uptime-reset,trial-user-profile,mac-auth-mode,smtp-server'],
                    'settings.name' => ['required', 'string', 'max:64'],
                    'settings.dns-name' => ['nullable', 'string', 'max:253', 'regex:/^[a-z0-9.-]+$/iD'],
                    'settings.hotspot-address' => ['nullable', 'ipv4'],
                    'settings.ssl-certificate' => ['nullable', 'string', 'max:128'],
                    'settings.login-by' => ['required', 'array', 'min:1'],
                    'settings.login-by.*' => ['distinct', Rule::in(RouterHotspot::METHODS)],
                    'settings.use-radius' => [Rule::in(['yes', 'no'])],
                    'settings.https-redirect' => [Rule::in(['yes', 'no'])],
                    ...collect(['html-directory', 'html-directory-override', 'split-user-domain', 'radius-accounting', 'radius-interim-update', 'radius-location-id', 'radius-location-name', 'radius-default-domain', 'nas-port-type', 'http-cookie-lifetime', 'http-proxy', 'trial-uptime-limit', 'trial-uptime-reset', 'trial-user-profile', 'mac-auth-mode', 'smtp-server'])->mapWithKeys(fn (string $key): array => ['settings.'.$key => ['nullable', 'string', 'max:253']])->all(),
                ],
                'assign' => [
                    'source' => ['required', 'regex:/^\*[a-f0-9]+$/iD', 'different:profile'],
                    'source_revision' => ['required', 'regex:/^[a-f0-9]{64}$/D'],
                    'server' => ['required', 'regex:/^\*[a-f0-9]+$/iD'],
                ],
                'draft', 'check', 'publish' => [
                    'design' => ['required', 'array:headline,message,button,accent,layout'],
                    'design.headline' => ['required', 'string', 'max:70'],
                    'design.message' => ['nullable', 'string', 'max:200'],
                    'design.button' => ['required', 'string', 'max:32'],
                    'design.accent' => ['required', 'regex:/^#[0-9a-f]{6}$/iD'],
                    'design.layout' => ['required', Rule::in(['light', 'dark'])],
                ],
                default => [],
            },
            'stage' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'profile.regex' => 'Select a HotSpot profile from the router.',
            'revision.regex' => 'Sync profiles before trying again.',
            'design.accent.regex' => 'Choose a valid six-digit color.',
            'settings.login-by.required' => 'Select at least one login method.',
        ];
    }
}
