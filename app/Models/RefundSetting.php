<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefundSetting extends Model
{
    protected $fillable = [
        'request_window_days',
        'max_progress_percentage',
    ];

    protected $casts = [
        'request_window_days' => 'integer',
        'max_progress_percentage' => 'integer',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'request_window_days' => 7,
            'max_progress_percentage' => 30,
        ]);
    }
}
