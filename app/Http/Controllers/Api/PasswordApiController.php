<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailOtp;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Lupa password (OTP, publik) & ganti password (saat login).
 * Memakai ulang OtpService dengan purpose 'password_reset'.
 */
class PasswordApiController extends Controller
{
    public function __construct(private OtpService $otp) {}

    /**
     * [PUBLIK] Kirim OTP reset password. Demi keamanan, respons SELALU sukses
     * (tidak membocorkan apakah email terdaftar). Email hanya benar-benar
     * dikirim bila akunnya ada.
     */
    public function sendOtp(Request $request)
    {
        $payload = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower(trim($payload['email']));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user) {
            try {
                $this->otp->send($email, EmailOtp::PURPOSE_PASSWORD_RESET, $user->name);
            } catch (\Throwable $e) {
                Log::warning('Gagal mengirim OTP reset password: '.$e->getMessage());
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Jika email terdaftar, kode reset sudah dikirim. Cek kotak masuk kamu.',
        ]);
    }

    /**
     * [PUBLIK] Reset password dengan OTP.
     */
    public function reset(Request $request)
    {
        $payload = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $email = strtolower(trim($payload['email']));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kode tidak valid atau sudah kadaluarsa.',
            ], 422);
        }

        $error = $this->otp->verify($email, EmailOtp::PURPOSE_PASSWORD_RESET, trim($payload['code']));
        if ($error !== null) {
            return response()->json([
                'status' => 'error',
                'message' => $error,
            ], 422);
        }

        $user->forceFill([
            'password' => Hash::make($payload['password']),
            // Reset password lewat email = bukti kepemilikan email -> sekalian verified.
            'email_verified_at' => $user->email_verified_at ?? now(),
            'api_token' => null, // cabut sesi lama demi keamanan
        ])->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Password berhasil diubah. Silakan login dengan password baru.',
        ]);
    }

    /**
     * [LOGIN] Ganti password saat sudah masuk (butuh password lama).
     */
    public function change(Request $request)
    {
        $payload = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (! Hash::check($payload['current_password'], $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Password lama salah.',
            ], 422);
        }

        $user->forceFill([
            'password' => Hash::make($payload['password']),
        ])->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Password berhasil diubah.',
        ]);
    }
}
