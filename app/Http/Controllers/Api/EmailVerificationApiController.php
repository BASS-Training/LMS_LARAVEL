<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\PresentsMobileUser;
use App\Http\Controllers\Controller;
use App\Models\EmailOtp;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Verifikasi email berbasis OTP (mobile). Semua endpoint butuh login
 * (token dari register/login) — verifikasi email TIDAK memblokir login,
 * jadi user yang belum verified tetap punya token untuk memanggil ini.
 */
class EmailVerificationApiController extends Controller
{
    use PresentsMobileUser;

    public function __construct(private OtpService $otp) {}

    /** Kirim / kirim ulang kode OTP ke email user saat ini. */
    public function sendOtp(Request $request)
    {
        $user = $request->user();

        if ($user->isEmailVerified()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Email kamu sudah terverifikasi.',
                'data' => ['already_verified' => true],
            ]);
        }

        try {
            $wait = $this->otp->send($user->email, EmailOtp::PURPOSE_EMAIL_VERIFICATION, $user->name);
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim OTP verifikasi (mobile): '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengirim email saat ini. Coba lagi beberapa saat.',
            ], 500);
        }

        if ($wait !== null) {
            return response()->json([
                'status' => 'error',
                'message' => "Tunggu {$wait} detik sebelum meminta kode baru.",
                'data' => ['retry_after' => $wait],
            ], 429);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Kode verifikasi sudah dikirim ke email kamu.',
        ]);
    }

    /** Verifikasi kode OTP lalu tandai email sebagai terverifikasi. */
    public function verifyOtp(Request $request)
    {
        $payload = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();

        if ($user->isEmailVerified()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Email kamu sudah terverifikasi.',
                'data' => $this->presentMobileUser($user),
            ]);
        }

        $error = $this->otp->verify(
            $user->email,
            EmailOtp::PURPOSE_EMAIL_VERIFICATION,
            trim($payload['code'])
        );

        if ($error !== null) {
            return response()->json([
                'status' => 'error',
                'message' => $error,
            ], 422);
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'email_verification_optional' => false,
        ])->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Email berhasil diverifikasi.',
            'data' => $this->presentMobileUser($user),
        ]);
    }

    /**
     * Pola "verifikasi dulu, baru ganti": kirim OTP ke EMAIL BARU. Email akun
     * belum berubah di sini — baru berubah setelah OTP-nya benar di changeEmail().
     * Ini mencegah pengguna terkunci karena salah ketik email.
     */
    public function sendChangeEmailOtp(Request $request)
    {
        $user = $request->user();

        $payload = $request->validate([
            'new_email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique(User::class, 'email')->ignore($user->id),
            ],
        ]);

        $newEmail = strtolower(trim($payload['new_email']));

        if ($newEmail === strtolower($user->email)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email baru sama dengan email kamu saat ini.',
            ], 422);
        }

        try {
            $wait = $this->otp->send($newEmail, EmailOtp::PURPOSE_EMAIL_CHANGE, $user->name);
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim OTP ubah email (mobile): '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengirim email saat ini. Coba lagi beberapa saat.',
            ], 500);
        }

        if ($wait !== null) {
            return response()->json([
                'status' => 'error',
                'message' => "Tunggu {$wait} detik sebelum meminta kode baru.",
                'data' => ['retry_after' => $wait],
            ], 429);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Kode konfirmasi sudah dikirim ke email baru kamu.',
        ]);
    }

    /** Verifikasi OTP email baru lalu pindahkan email akun ke alamat itu. */
    public function changeEmail(Request $request)
    {
        $user = $request->user();

        $payload = $request->validate([
            'new_email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique(User::class, 'email')->ignore($user->id),
            ],
            'code' => ['required', 'string'],
        ]);

        $newEmail = strtolower(trim($payload['new_email']));

        $error = $this->otp->verify(
            $newEmail,
            EmailOtp::PURPOSE_EMAIL_CHANGE,
            trim($payload['code'])
        );

        if ($error !== null) {
            return response()->json([
                'status' => 'error',
                'message' => $error,
            ], 422);
        }

        // OTP ke email baru terbukti dimiliki → baru sekarang email dipindah,
        // sekaligus ditandai terverifikasi.
        $user->forceFill([
            'email' => $newEmail,
            'email_verified_at' => now(),
            'email_verification_optional' => false,
        ])->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Email berhasil diubah dan diverifikasi.',
            'data' => $this->presentMobileUser($user),
        ]);
    }
}
