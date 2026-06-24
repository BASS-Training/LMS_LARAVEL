<?php

namespace App\Services;

use App\Mail\OtpMail;
use App\Models\EmailOtp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Pusat logika OTP: membuat, mengirim (email), dan memverifikasi kode.
 *
 * Aturan keamanan:
 *  - Kode disimpan ter-HASH, tidak pernah plaintext.
 *  - Hanya 1 OTP aktif per (email + purpose): generate baru hapus yang lama.
 *  - Ada throttle kirim ulang (RESEND_COOLDOWN) & batas percobaan tebak.
 */
class OtpService
{
    public const CODE_LENGTH = 6;

    public const EXPIRES_MINUTES = 10;

    public const RESEND_COOLDOWN_SECONDS = 60;

    /**
     * Buat & kirim OTP. Mengembalikan jumlah detik tunggu jika kena cooldown,
     * atau null kalau berhasil terkirim.
     */
    public function send(string $email, string $purpose, ?string $name = null): ?int
    {
        $email = strtolower(trim($email));

        // Throttle kirim ulang.
        $recent = EmailOtp::where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest()
            ->first();

        if ($recent && $recent->created_at->diffInSeconds(now()) < self::RESEND_COOLDOWN_SECONDS) {
            return self::RESEND_COOLDOWN_SECONDS - (int) $recent->created_at->diffInSeconds(now());
        }

        // Hanya satu OTP aktif per (email + purpose).
        EmailOtp::where('email', $email)->where('purpose', $purpose)->delete();

        $code = $this->generateCode();

        EmailOtp::create([
            'email' => $email,
            'purpose' => $purpose,
            'code' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(self::EXPIRES_MINUTES),
        ]);

        Mail::to($email)->send(new OtpMail($code, $purpose, $name, self::EXPIRES_MINUTES));

        return null;
    }

    /**
     * Verifikasi kode. Mengembalikan null jika sukses (OTP dikonsumsi),
     * atau pesan error (string) jika gagal.
     */
    public function verify(string $email, string $purpose, string $code): ?string
    {
        $email = strtolower(trim($email));

        $otp = EmailOtp::where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest()
            ->first();

        if (! $otp) {
            return 'Kode tidak ditemukan. Silakan minta kode baru.';
        }

        if ($otp->isExpired()) {
            return 'Kode sudah kadaluarsa. Silakan minta kode baru.';
        }

        if (! $otp->hasAttemptsLeft()) {
            return 'Terlalu banyak percobaan. Silakan minta kode baru.';
        }

        if (! Hash::check($code, $otp->code)) {
            $otp->increment('attempts');

            return 'Kode salah. Silakan periksa kembali.';
        }

        $otp->forceFill(['consumed_at' => now()])->save();

        return null;
    }

    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }
}
