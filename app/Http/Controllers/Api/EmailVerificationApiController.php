<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\PresentsMobileUser;
use App\Http\Controllers\Controller;
use App\Models\EmailOtp;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
}
