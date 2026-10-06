<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureSetting extends Model
{
    protected $fillable = [
        'bundles_enabled',
        'learning_paths_enabled',
        'refund_requests_enabled',
    ];

    protected $casts = [
        'bundles_enabled' => 'boolean',
        'learning_paths_enabled' => 'boolean',
        'refund_requests_enabled' => 'boolean',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1], [
            'bundles_enabled' => true,
            'learning_paths_enabled' => true,
            'refund_requests_enabled' => true,
        ]);
    }
}
