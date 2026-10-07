<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefundSetting extends Model
{
    protected $fillable = [
        'policy_mode',
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
            'policy_mode' => 'company_issue',
            'request_window_days' => 7,
            'max_progress_percentage' => 30,
        ]);
    }

    public function consentText(): string
    {
        return 'Saya memahami bahwa pembayaran yang telah berhasil tidak dapat dibatalkan atau dikembalikan (non-refundable), kecuali terjadi kendala dari pihak kami.';
    }
}
