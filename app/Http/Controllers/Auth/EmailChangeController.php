<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmailOtp;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Ubah email berbasis OTP untuk WEB, pola "verifikasi dulu, baru ganti".
 *
 * OTP dikirim ke EMAIL BARU; email akun baru berubah setelah OTP-nya benar.
 * Ini mencegah pengguna terkunci karena salah ketik / email asing —
 * sejalan dengan versi mobile dan kebijakan soft-enforcement yang sama.
 */
class EmailChangeController extends Controller
{
    public function __construct(private OtpService $otp) {}

    /** Kirim OTP ke email baru lalu arahkan ke halaman input kode. */
    public function sendOtp(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'new_email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique(User::class, 'email')->ignore($user->id),
            ],
        ]);

        $newEmail = strtolower(trim($data['new_email']));

        if ($newEmail === strtolower($user->email)) {
            return back()->withErrors(['new_email' => 'Email baru sama dengan email kamu saat ini.']);
        }

        try {
            $wait = $this->otp->send($newEmail, EmailOtp::PURPOSE_EMAIL_CHANGE, $user->name);
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim OTP ubah email (web): '.$e->getMessage());

            return back()->withErrors(['new_email' => 'Gagal mengirim email saat ini. Coba lagi beberapa saat.']);
        }

        $message = $wait !== null
            ? "Kode sebelumnya masih berlaku. Cek email baru kamu (atau tunggu {$wait} detik untuk kirim ulang)."
            : 'Kode konfirmasi sudah dikirim ke email baru kamu.';

        return redirect()->route('email.change.otp')
            ->with('pending_new_email', $newEmail)
            ->with('status', $message);
    }

    /** Halaman input kode OTP untuk konfirmasi email baru. */
    public function showVerify(Request $request): View|RedirectResponse
    {
        $newEmail = $request->session()->get('pending_new_email');

        if (! $newEmail) {
            return redirect()->route('profile.edit');
        }

        // Pertahankan email baru di sesi selama user masih di halaman ini.
        $request->session()->keep(['pending_new_email']);

        return view('auth.change-email-otp', ['newEmail' => $newEmail]);
    }

    /** Verifikasi OTP email baru lalu pindahkan email akun ke alamat itu. */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'new_email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique(User::class, 'email')->ignore($user->id),
            ],
            'code' => ['required', 'string'],
        ]);

        $newEmail = strtolower(trim($data['new_email']));

        $error = $this->otp->verify($newEmail, EmailOtp::PURPOSE_EMAIL_CHANGE, trim($data['code']));

        if ($error !== null) {
            return back()
                ->with('pending_new_email', $newEmail)
                ->withErrors(['code' => $error]);
        }

        $user->forceFill([
            'email' => $newEmail,
            'email_verified_at' => now(),
            'email_verification_optional' => false,
        ])->save();

        $request->session()->forget('pending_new_email');

        return redirect()->route('profile.edit')
            ->with('status', 'Email berhasil diubah dan diverifikasi.');
    }
}
