<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\MultiFactorRequest;
use App\Models\User;
use App\Services\SecurityEventLogger;
use App\Services\TotpService;
use App\Support\Rbac\PermissionRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MultiFactorController extends Controller
{
    public function setup(Request $request, TotpService $totp): View|RedirectResponse
    {
        if ($request->user('web')->mfa_confirmed_at) {
            return redirect()->route('mfa.challenge');
        }
        if (! $request->session()->has('mfa_pending_secret')) {
            $request->session()->put('mfa_pending_secret', Crypt::encryptString($totp->secret()));
        }
        $secret = Crypt::decryptString($request->session()->get('mfa_pending_secret'));
        $uri = 'otpauth://totp/'.rawurlencode(config('app.name').':'.$request->user('web')->email).'?secret='.$secret.'&issuer='.rawurlencode(config('app.name'));

        return view('modules/security/setup', compact('secret', 'uri'));
    }

    public function confirm(MultiFactorRequest $request, TotpService $totp): View
    {
        abort_if($request->user('web')->mfa_confirmed_at !== null, 403);
        abort_unless($request->session()->has('mfa_pending_secret'), 403);
        $secret = Crypt::decryptString($request->session()->get('mfa_pending_secret'));
        $counter = $totp->matchingCounter($secret, (string) $request->validated('code'));
        if ($counter === null) {
            throw ValidationException::withMessages(['code' => 'The authenticator code is invalid.']);
        }
        $codes = array_map(fn (): string => bin2hex(random_bytes(16)), range(1, 8));
        $user = $request->user('web');
        $user->forceFill([
            'mfa_secret' => $secret, 'mfa_confirmed_at' => now(), 'mfa_last_counter' => $counter,
            'mfa_recovery_codes' => array_map(fn (string $code): string => hash('sha256', $code), $codes),
        ])->saveQuietly();
        $request->session()->forget('mfa_pending_secret');
        $this->verified($request);
        app(SecurityEventLogger::class)->record('mfa.enrolled', ['user_id' => $user->id, 'tenant_id' => $user->tenant_id]);

        return view('modules/security/recovery', compact('codes'));
    }

    public function challenge(): View
    {
        return view('modules/security/challenge');
    }

    public function verify(MultiFactorRequest $request, TotpService $totp): RedirectResponse
    {
        $identity = $request->user('web');
        $verified = $identity->getConnection()->transaction(function () use ($identity, $request, $totp): bool {
            $user = User::withoutGlobalScopes()->whereKey($identity->id)->where('tenant_id', $identity->tenant_id)->lockForUpdate()->firstOrFail();
            if (! $user->mfa_confirmed_at) {
                return false;
            }
            if ($request->filled('recovery_code')) {
                $codes = $user->mfa_recovery_codes ?? [];
                $index = array_search(hash('sha256', $request->validated('recovery_code')), $codes, true);
                if ($index === false) {
                    return false;
                }
                unset($codes[$index]);
                $user->forceFill(['mfa_recovery_codes' => array_values($codes)])->saveQuietly();
            } else {
                $counter = $totp->matchingCounter($user->mfa_secret, (string) $request->validated('code'), $user->mfa_last_counter);
                if ($counter === null) {
                    return false;
                }
                $user->forceFill(['mfa_last_counter' => $counter])->saveQuietly();
            }

            return true;
        });
        if (! $verified) {
            app(SecurityEventLogger::class)->record('mfa.failed', ['user_id' => $identity->id, 'ip' => $request->ip()]);
            throw ValidationException::withMessages(['code' => 'The authentication code is invalid or has already been used.']);
        }
        $this->verified($request);

        return redirect()->route($identity->isCentralAdmin() ? 'admin.super-admin.tenants.index' : (PermissionRegistry::firstAccessibleRoute($identity) ?? 'profile.index'));
    }

    private function verified(Request $request): void
    {
        $request->session()->regenerate();
        $request->session()->put(['mfa_verified_user' => $request->user('web')->id, 'mfa_verified_at' => now()->timestamp]);
    }
}
