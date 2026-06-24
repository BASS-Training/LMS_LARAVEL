<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmailOtp;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Verifikasi email berbasis OTP untuk WEB (sejajar dengan versi mobile).
 * Memakai OtpService & penanda email_verification_optional yang sama,
 * jadi web & mobile berperilaku identik dan akun lama tetap aman.
 */
class OtpVerificationController extends Controller
{
    public function __construct(private OtpService $otp) {}

    /** Halaman input kode OTP (akun baru wajib, akun lama boleh sukarela). */
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        // Sudah verified -> tidak perlu halaman ini.
        if ($user->isEmailVerified()) {
            return redirect()->route('dashboard');
        }

        return view('auth.verify-otp', ['email' => $user->email]);
    }

    /**
     * Mulai verifikasi sukarela (dipakai akun lama dari halaman Profil):
     * kirim OTP lalu arahkan ke halaman input kode.
     */
    public function start(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isEmailVerified()) {
            return redirect()->route('profile.edit')
                ->with('success', 'Email kamu sudah terverifikasi.');
        }

        try {
            $wait = $this->otp->send($user->email, EmailOtp::PURPOSE_EMAIL_VERIFICATION, $user->name);
        } catch (\Throwable $e) {
            Log::warning('Gagal memulai verifikasi (web): '.$e->getMessage());

            return redirect()->route('verification.otp')
                ->withErrors(['code' => 'Gagal mengirim email saat ini. Coba lagi beberapa saat.']);
        }

        $message = $wait !== null
            ? "Kode sebelumnya masih berlaku. Cek email kamu (atau tunggu {$wait} detik untuk kirim ulang)."
            : 'Kode verifikasi sudah dikirim ke email kamu.';

        return redirect()->route('verification.otp')->with('success', $message);
    }

    /** Verifikasi kode OTP lalu tandai email terverifikasi. */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();

        if ($user->isEmailVerified()) {
            return redirect()->route('dashboard');
        }

        $error = $this->otp->verify(
            $user->email,
            EmailOtp::PURPOSE_EMAIL_VERIFICATION,
            trim($request->input('code'))
        );

        if ($error !== null) {
            return back()->withErrors(['code' => $error]);
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'email_verification_optional' => false,
        ])->save();

        return redirect()->route('dashboard')
            ->with('success', 'Email berhasil diverifikasi. Selamat datang!');
    }

    /** Kirim ulang kode OTP (dengan throttle dari OtpService). */
    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isEmailVerified()) {
            return redirect()->route('dashboard');
        }

        try {
            $wait = $this->otp->send($user->email, EmailOtp::PURPOSE_EMAIL_VERIFICATION, $user->name);
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim ulang OTP (web): '.$e->getMessage());

            return back()->withErrors(['code' => 'Gagal mengirim email saat ini. Coba lagi beberapa saat.']);
        }

        if ($wait !== null) {
            return back()->withErrors(['code' => "Tunggu {$wait} detik sebelum meminta kode baru."]);
        }

        return back()->with('success', 'Kode baru sudah dikirim ke email kamu.');
    }
}
