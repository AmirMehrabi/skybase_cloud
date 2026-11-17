<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class GovernmentSso
{
    public const ORIGIN = 'https://usw.msrt.ir';

    public const KEY = 'government_sso';

    public static function settings(string $tenantId): array
    {
        return Setting::get(self::KEY, [], $tenantId) + [
            'web_enabled' => false,
            'customer_enabled' => false,
            'client_id' => '',
            'client_secret' => '',
            'label' => 'ورود از طریق دولت من',
            'authorize_path' => '/oauth2/authorize',
            'token_path' => '/oauth2/token',
            'userinfo_path' => '/api/v1/User/userinfo',
            'logout_path' => '/oauth2/logout',
            'pkce' => false,
            'token_format' => 'form',
        ];
    }

    public static function tenant(?string $slug): ?Tenant
    {
        return filled($slug) ? Tenant::query()->where('slug', $slug)->first() : null;
    }

    public static function enabled(?Tenant $tenant, array $settings, string $guard): bool
    {
        return $tenant?->isActive() && ! $tenant->application_access_suspended && ! $tenant->archived_at
            && $settings[$guard.'_enabled'] && filled($settings['client_id']) && filled($settings['client_secret']);
    }

    public static function model(string $guard): string
    {
        return $guard === 'web' ? User::class : Customer::class;
    }

    public static function identityHash(string $tenantId, string $guard, mixed $nationalId): ?string
    {
        if (! is_string($nationalId)) {
            return null;
        }

        $digits = strtr(trim($nationalId), array_combine(
            mb_str_split('۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩'),
            str_split('01234567890123456789'),
        ));

        return preg_match('/\A[0-9]{10}\z/', $digits)
            ? hash_hmac('sha256', $tenantId.':'.$guard.':'.$digits, config('app.key'))
            : null;
    }

    public function nationalId(array $settings, string $code, string $callback, ?string $verifier): ?string
    {
        $client = Http::acceptJson()->timeout(10)->withoutRedirecting();
        $parameters = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $settings['client_id'],
            'client_secret' => $settings['client_secret'],
            'redirect_uri' => $callback,
            'scope' => 'openid profile',
        ];
        if ($verifier !== null) {
            $parameters['code_verifier'] = $verifier;
        }

        $client = $settings['token_format'] === 'multipart' ? $client->asMultipart() : $client->asForm();
        $token = $client->post(self::ORIGIN.$settings['token_path'], $parameters);
        $accessToken = $token->successful() ? $token->json('access_token') : null;
        if (! is_string($accessToken) || ! preg_match('/\A[A-Za-z0-9._~+\/=-]+\z/', $accessToken)) {
            return null;
        }

        $profile = $client->withToken($accessToken)->get(self::ORIGIN.$settings['userinfo_path']);
        $nationalId = $profile->successful() ? $profile->json('nationalId') : null;

        return is_string($nationalId) ? $nationalId : null;
    }
}
