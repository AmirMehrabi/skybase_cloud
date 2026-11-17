<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\GovernmentSso;
use App\Services\TenantContext;
use App\Support\Rbac\PermissionRegistry;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GovernmentSsoController extends Controller
{
    public function start(Request $request, string $guard): RedirectResponse
    {
        if ($guard === 'web' && config('skybase_billing.console_host_enabled')
            && $request->getHost() === config('skybase_billing.console_host')) {
            abort(404);
        }

        $tenant = GovernmentSso::tenant($request->string('sso_tenant')->trim()->limit(100, '')->toString());
        $settings = $tenant ? GovernmentSso::settings((string) $tenant->id) : [];
        if (! GovernmentSso::enabled($tenant, $settings, $guard)) {
            return $this->failed($guard, $tenant);
        }

        $state = Str::random(64);
        $verifier = $settings['pkce'] ? Str::random(64) : null;
        $callback = route('government-sso.callback');
        $request->session()->put('government_sso.pending', [
            'state' => $state,
            'tenant_id' => (string) $tenant->id,
            'guard' => $guard,
            'expires' => now()->addMinutes(10)->timestamp,
            'revision' => $settings['revision'] ?? null,
            'verifier' => $verifier,
            'callback' => $callback,
        ]);

        $parameters = [
            'response_type' => 'code',
            'scope' => 'openid profile',
            'client_id' => $settings['client_id'],
            'redirect_uri' => $callback,
            'state' => $state,
        ];
        if ($verifier !== null) {
            $parameters['code_challenge'] = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
            $parameters['code_challenge_method'] = 'S256';
        }

        return redirect()->away(GovernmentSso::ORIGIN.$settings['authorize_path'].'?'.http_build_query($parameters, '', '&', PHP_QUERY_RFC3986));
    }

    public function callback(Request $request, GovernmentSso $sso, TenantContext $context): RedirectResponse
    {
        $pending = $request->session()->get('government_sso.pending');
        $state = $request->query('state');
        if (! $pending || ! is_string($state) || ! hash_equals($pending['state'], $state)
            || $pending['callback'] !== route('government-sso.callback')) {
            return $this->failed($pending['guard'] ?? 'web');
        }

        $request->session()->forget('government_sso.pending');
        $guard = $pending['guard'];
        $tenant = Tenant::query()->find($pending['tenant_id']);
        $settings = $tenant ? GovernmentSso::settings((string) $tenant->id) : [];
        $code = $request->query('code');
        if ($pending['expires'] <= now()->timestamp || $request->query->has('error')
            || ! is_string($code) || blank($code) || strlen($code) > 4096
            || ! GovernmentSso::enabled($tenant, $settings, $guard)
            || ($settings['revision'] ?? null) !== $pending['revision']) {
            return $this->failed($guard, $tenant);
        }

        try {
            $nationalId = $sso->nationalId($settings, $code, $pending['callback'], $pending['verifier']);
        } catch (ConnectionException) {
            return $this->failed($guard, $tenant);
        }

        return $context->run($pending['tenant_id'], function () use ($request, $pending, $guard, $tenant, $nationalId): RedirectResponse {
            $tenant->refresh();
            $settings = GovernmentSso::settings($pending['tenant_id']);
            $hash = GovernmentSso::identityHash($pending['tenant_id'], $guard, $nationalId);
            if ($hash === null || ! GovernmentSso::enabled($tenant, $settings, $guard)
                || ($settings['revision'] ?? null) !== $pending['revision']) {
                return $this->failed($guard, $tenant);
            }

            $model = GovernmentSso::model($guard);
            $account = $model::query()->where('tenant_id', $pending['tenant_id'])
                ->where('government_identity_hash', $hash)->where('status', 'active')->first();
            if (! $account) {
                return $this->failed($guard, $tenant);
            }

            $account->forceFill(['last_login_at' => now()])->save();
            Auth::guard($guard)->login($account);
            $request->session()->regenerate();
            $request->session()->forget(['auth.password_confirmed_at', 'mfa_verified_user', 'mfa_verified_at', 'mfa_pending_secret']);
            $request->session()->put('government_sso.'.$guard, GovernmentSso::ORIGIN.$settings['logout_path']);
            $landing = $guard === 'customer' ? 'customer.dashboard' : (PermissionRegistry::firstAccessibleRoute($account) ?? 'dashboard');

            return redirect()->intended(route($landing));
        });
    }

    private function failed(string $guard, ?Tenant $tenant = null): RedirectResponse
    {
        return redirect()->route($guard === 'customer' ? 'customer.login' : 'auth.login', ['sso_tenant' => $tenant?->slug])
            ->withErrors(['government_sso' => 'Government sign-in could not be completed. Try again, use your password, or ask your tenant administrator to check your enrollment.']);
    }
}
