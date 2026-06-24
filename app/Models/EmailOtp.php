<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Kode OTP untuk verifikasi email / reset password.
 * Lihat catatan desain di migration create_email_otps_table.
 */
class EmailOtp extends Model
{
    public const PURPOSE_EMAIL_VERIFICATION = 'email_verification';

    public const PURPOSE_PASSWORD_RESET = 'password_reset';

    public const MAX_ATTEMPTS = 5;

    protected $fillable = [
        'email',
        'purpose',
        'code',
        'attempts',
        'expires_at',
        'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isConsumed(): bool
    {
        return ! is_null($this->consumed_at);
    }

    public function hasAttemptsLeft(): bool
    {
        return $this->attempts < self::MAX_ATTEMPTS;
    }
}
