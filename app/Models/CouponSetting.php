<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CouponSetting extends Model
{
    protected $fillable = [
        'checkout_enabled',
        'updated_by',
    ];

    protected $casts = [
        'checkout_enabled' => 'boolean',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], ['checkout_enabled' => false]);
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
