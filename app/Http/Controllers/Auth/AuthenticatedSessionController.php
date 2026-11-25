<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\ActivityLog;
use App\Models\EmailOtp;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(private OtpService $otpService) {}

    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        ActivityLog::log('auth.login', [
            'description' => 'Login berhasil melalui web.',
            'metadata' => ['source' => 'web'],
        ], $request->user());

        $user = $request->user();

        if ($user->mustVerifyEmail()) {
            try {
                $this->otpService->send(
                    $user->email,
                    EmailOtp::PURPOSE_EMAIL_VERIFICATION,
                    $user->name
                );
            } catch (\Throwable $e) {
                Log::warning('Gagal mengirim OTP saat login : '.$e->getMessage());

                return redirect()->route('verification.otp')->withErrors([
                    'code' => 'Kode Verifikasi belum dapat dikirim. Silahkan tekan kirim ulang kodenya',
                ]);
            }
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
