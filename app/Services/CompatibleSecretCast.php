<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class CompatibleSecretCast implements CastsAttributes
{
    public function __construct(private string $format = 'string') {}

    public static function isEncrypted(string $value): bool
    {
        $decoded = base64_decode($value, true);
        $payload = $decoded === false ? null : json_decode($decoded, true);

        return is_array($payload) && isset($payload['iv'], $payload['value'], $payload['mac']);
    }

    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }
        $raw = self::isEncrypted((string) $value) ? Crypt::decryptString($value) : $value;

        return $this->format === 'array' ? json_decode($raw, true) : $raw;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }
        $raw = $this->format === 'array' ? json_encode($value, JSON_THROW_ON_ERROR) : (string) $value;
        $alreadyEncrypted = isset($attributes[$key]) && self::isEncrypted((string) $attributes[$key]);

        return config('security.encrypt_secrets') || $alreadyEncrypted || ($model instanceof Setting && (str_starts_with($attributes['key'] ?? '', 'email.') || str_starts_with($attributes['key'] ?? '', 'sms.'))) ? Crypt::encryptString($raw) : $raw;
    }
}
