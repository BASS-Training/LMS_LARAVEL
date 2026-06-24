<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Penjaga verifikasi email versi OTP untuk WEB.
 *
 * BEDA PENTING dengan middleware `verified` bawaan Laravel: middleware ini
 * hanya menahan AKUN BARU yang belum verifikasi (User::mustVerifyEmail()).
 * Akun lama (`email_verification_optional = true`) SELALU lolos, sehingga
 * tidak mungkin terkunci — meskipun email_verified_at-nya kosong.
 *
 * Akun baru yang belum verifikasi diarahkan ke halaman OTP. Beberapa route
 * dikecualikan agar tidak terjadi redirect berputar (halaman OTP itu sendiri,
 * logout, dsb).
 */
class EnsureEmailVerifiedOtp
{
    /** Nama route yang boleh diakses meski belum verifikasi. */
    private array $allowedRoutes = [
        'verification.otp',
        'verification.otp.verify',
        'verification.otp.resend',
        'logout',
        // Sisakan jalur bawaan Laravel agar tetap berfungsi bila dipakai.
        'verification.notice',
        'verification.verify',
        'verification.send',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Tamu, akun lama (opsional), atau sudah verified -> lolos.
        if (! $user || ! $user->mustVerifyEmail()) {
            return $next($request);
        }

        // Jangan menghalangi halaman OTP itu sendiri & logout.
        if ($request->routeIs(...$this->allowedRoutes)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Email belum diverifikasi.',
            ], 409);
        }

        return redirect()->route('verification.otp');
    }
}
