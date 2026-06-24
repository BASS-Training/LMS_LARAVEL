<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmailOtp;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Lupa password berbasis OTP untuk WEB — disamakan dengan mobile (kode 6 digit
 * + email branded yang sama via OtpService), menggantikan alur link bawaan.
 */
class PasswordResetOtpController extends Controller
{
    public function __construct(private OtpService $otp) {}

    /** Halaman input email. */
    public function request(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Kirim OTP reset. Demi keamanan tidak membocorkan apakah email terdaftar:
     * selalu lanjut ke halaman input kode dengan pesan generik.
     */
    public function sendOtp(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        $email = strtolower(trim($request->input('email')));

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
        if ($user) {
            try {
                $this->otp->send($email, EmailOtp::PURPOSE_PASSWORD_RESET, $user->name);
            } catch (\Throwable $e) {
                Log::warning('Gagal mengirim OTP reset password (web): '.$e->getMessage());
            }
        }

        return redirect()->route('password.otp')
            ->with('reset_email', $email)
            ->with('status', 'Jika email terdaftar, kode reset sudah dikirim. Cek inbox/Spam kamu.');
    }

    /** Halaman input kode + password baru. */
    public function showReset(Request $request): View|RedirectResponse
    {
        $email = session('reset_email', old('email'));
        if (! $email) {
            return redirect()->route('password.request');
        }

        return view('auth.reset-password-otp', ['email' => $email]);
    }

    /** Verifikasi OTP lalu set password baru. */
    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $email = strtolower(trim($request->input('email')));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            return back()->withInput($request->only('email'))
                ->withErrors(['code' => 'Kode tidak valid atau sudah kadaluarsa.']);
        }

        $error = $this->otp->verify($email, EmailOtp::PURPOSE_PASSWORD_RESET, trim($request->input('code')));
        if ($error !== null) {
            return back()->withInput($request->only('email'))
                ->withErrors(['code' => $error]);
        }

        $user->forceFill([
            'password' => Hash::make($request->input('password')),
            // Reset lewat email = bukti kepemilikan email -> sekalian verified.
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        return redirect()->route('login')
            ->with('status', 'Password berhasil diubah. Silakan login dengan password baru.');
    }
}
