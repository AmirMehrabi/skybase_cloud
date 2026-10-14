<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Hash;

trait HashesPasswordCredentials
{
    /** @param array<string, mixed> $options */
    public function save(array $options = []): bool
    {
        $passwordColumn = $this->credentialPasswordColumn();
        $hashColumn = $passwordColumn.'_hash';
        $password = $this->getAttribute($passwordColumn);

        if ($this->isDirty($passwordColumn) && blank($password)) {
            $this->setAttribute($hashColumn, null);
        } elseif (filled($password) && (blank($this->getAttribute($hashColumn))
            || ($this->isDirty($passwordColumn) && $password !== $this->getOriginal($passwordColumn)))) {
            $this->setAttribute($hashColumn, Hash::driver('argon2id')->make($password));
        }

        return parent::save($options);
    }

    abstract protected function credentialPasswordColumn(): string;
}
