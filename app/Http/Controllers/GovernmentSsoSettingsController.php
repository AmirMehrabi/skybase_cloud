<?php

namespace App\Http\Controllers;

use App\Http\Requests\Setting\ManageGovernmentIdentityRequest;
use App\Http\Requests\Setting\UpdateGovernmentSsoRequest;
use App\Models\Setting;
use App\Models\Tenant;
use App\Services\GovernmentSso;
use App\Services\SecurityEventLogger;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GovernmentSsoSettingsController extends Controller
{
    public function show(Request $request): View
    {
        abort_unless($request->user()->isAdmin(), 403);
        $section = 'government-sso';
        $tenant = tenant();
        $settings = GovernmentSso::settings((string) $tenant->id);
        $hasSecret = filled($settings['client_secret']);
        unset($settings['client_secret']);

        return view('settings.section', compact('section', 'tenant', 'settings', 'hasSecret'));
    }

    public function update(UpdateGovernmentSsoRequest $request, SecurityEventLogger $events): RedirectResponse
    {
        $tenantId = (string) tenant()->id;
        (new Tenant)->getConnection()->transaction(function () use ($request, $tenantId): void {
            Tenant::query()->whereKey($tenantId)->lockForUpdate()->firstOrFail();
            $settings = $request->validated();
            $settings['client_secret'] ??= GovernmentSso::settings($tenantId)['client_secret'];
            foreach (['web_enabled', 'customer_enabled', 'pkce'] as $key) {
                $settings[$key] = $request->boolean($key);
            }
            if (($settings['web_enabled'] || $settings['customer_enabled'])
                && (blank($settings['client_id'] ?? null) || blank($settings['client_secret']))) {
                throw ValidationException::withMessages(['client_id' => 'Save the USW client ID and secret before enabling sign-in.']);
            }
            $settings['client_id'] ??= '';
            $settings['revision'] = (string) Str::uuid();
            Setting::set(GovernmentSso::KEY, $settings, 'json', $tenantId);
        });

        $events->record('government_sso.settings_updated', ['tenant_id' => $tenantId, 'user_id' => $request->user()->id]);

        return back()->with('success', 'Government SSO settings saved.');
    }

    public function identity(ManageGovernmentIdentityRequest $request, SecurityEventLogger $events): RedirectResponse
    {
        $tenantId = (string) tenant()->id;
        $guard = $request->validated('account_type');
        $model = GovernmentSso::model($guard);
        $accounts = $model::query()->where('tenant_id', $tenantId)
            ->where('email', $request->validated('account_email'))->limit(2)->get();
        if ($accounts->count() !== 1) {
            throw ValidationException::withMessages(['account_email' => 'Enter the email of exactly one account of this type in your tenant.']);
        }

        $enroll = $request->validated('operation') === 'enroll';
        $account = $accounts->first();
        $hash = $enroll ? GovernmentSso::identityHash($tenantId, $guard, $request->validated('government_national_id')) : null;
        try {
            $model::query()->where('tenant_id', $tenantId)->whereKey($account->getKey())
                ->update(['government_identity_hash' => $hash]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['government_national_id' => 'This identity is already enrolled for another account of this type.']);
        }

        $events->record($enroll ? 'government_sso.enrolled' : 'government_sso.revoked', [
            'tenant_id' => $tenantId,
            'user_id' => $request->user()->id,
            'resource_id' => $account->getKey(),
            'guard' => $guard,
        ]);

        return back()->with('success', $enroll ? 'Government identity enrolled.' : 'Government identity revoked.');
    }
}
