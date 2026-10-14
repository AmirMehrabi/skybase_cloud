<?php

namespace App\Services;

class CredentialVisibility
{
    public static function passwordsRedacted(): bool
    {
        return config('security.password_encryption', false) || config('security.redact_credentials', false);
    }
}
