<?php

namespace App\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use RuntimeException;

class SensitiveDataEncryptionService
{
    private function encrypter(): Encrypter
    {
        $encrypter = new Encrypter($this->decodeKey(config('sensitive-data.key')), 'aes-256-gcm');
        $encrypter->previousKeys(array_map(
            fn ($key) => $this->decodeKey($key),
            config('sensitive-data.previous_keys', [])
        ));

        return $encrypter;
    }

    private function decodeKey(?string $key): string
    {
        $decoded = is_string($key) && str_starts_with($key, 'base64:')
            ? base64_decode(substr($key, 7), true)
            : false;

        if ($decoded === false || strlen($decoded) !== 32) {
            throw new RuntimeException('Kunci enkripsi data sensitif harus berupa base64: dengan 32 byte kunci.');
        }

        return $decoded;
    }

    public function encrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return 'v1:'.$this->encrypter()->encryptString($value);
    }

    public function decrypt(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! str_starts_with($value, 'v1:')) {
            throw new DecryptException('Format data sensitif tidak dikenali.');
        }

        return $this->encrypter()->decryptString(substr($value, 3));
    }
}
