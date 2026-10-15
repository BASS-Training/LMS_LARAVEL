<?php

namespace App\Casts;

use App\Services\SensitiveDataEncryptionService;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class EncryptedPhone implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return app(SensitiveDataEncryptionService::class)->decrypt($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return app(SensitiveDataEncryptionService::class)->encrypt($value);
    }
}
