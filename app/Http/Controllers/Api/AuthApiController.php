<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\PresentsMobileUser;
use App\Http\Controllers\Controller;
use App\Models\EmailOtp;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthApiController extends Controller
{
    use PresentsMobileUser;

    public function __construct(private OtpService $otp) {}

    public function login(Request $request)
    {
        $payload = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = strtolower(trim($payload['email']));

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
        if (! $user || ! Hash::check($payload['password'], $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email atau password salah.',
            ], 422);
        }

        $token = $this->issueToken($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil.',
            'data' => $this->presentMobileUser($user, $token),
        ]);
    }

    public function register(Request $request)
    {
        $payload = $request->validate([
            'class_interest' => ['required', Rule::in(['regular', 'avpn_ai'])],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'date_of_birth' => ['required', 'date'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'institution_name' => ['required', 'string', 'max:255'],
            'occupation' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $email = strtolower(trim($payload['email']));

        $registrationProgram = $payload['class_interest'];

        $pendingIdentity = User::query()
            ->whereRaw('LOWER(name) = ?', [strtolower($payload['name'])])
            ->whereDate('date_of_birth', $payload['date_of_birth'])
            ->where('avpn_verification_status', 'pending')
            ->first();

        if ($pendingIdentity) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data Anda sedang menunggu validasi AVPN. Gunakan akun yang sudah terdaftar sebelumnya.',
            ], 422);
        }

        $user = User::create([
            'name' => $payload['name'],
            'email' => $email,
            'registration_program' => $registrationProgram,
            'avpn_verification_status' => $registrationProgram === 'avpn_ai' ? 'pending' : 'not_required',
            'avpn_google_form_submitted_at' => $registrationProgram === 'avpn_ai' ? now() : null,
            'date_of_birth' => $payload['date_of_birth'],
            'gender' => $payload['gender'],
            'institution_name' => $payload['institution_name'],
            'occupation' => $payload['occupation'],
            'password' => $payload['password'],
            'role' => 'participant',
            // Akun BARU: verifikasi email WAJIB (bukan opsional seperti akun lama).
            'email_verification_optional' => false,
        ]);

        try {
            $user->assignRole('participant');
        } catch (\Throwable $e) {
            // Keep legacy role column if Spatie role is unavailable in this environment.
        }

        // Kirim OTP verifikasi. Dibungkus try/catch supaya kegagalan kirim email
        // (mis. SMTP belum siap) TIDAK menggagalkan registrasi — user bisa minta
        // kirim ulang dari layar OTP.
        try {
            $this->otp->send($user->email, EmailOtp::PURPOSE_EMAIL_VERIFICATION, $user->name);
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim OTP verifikasi saat registrasi: '.$e->getMessage());
        }

        $token = $this->issueToken($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Registrasi berhasil. Silakan verifikasi email kamu.',
            'data' => $this->presentMobileUser($user, $token),
        ], 201);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->presentMobileUser($user),
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->forceFill(['api_token' => null])->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil.',
        ]);
    }

    /**
     * Hapus permanen akun peserta yang sedang login (butuh konfirmasi password).
     * Dipakai fitur "Hapus Akun" di aplikasi mobile — wajib App Store Guideline
     * 5.1.1(v) untuk aplikasi yang mengizinkan pembuatan akun.
     */
    public function deleteAccount(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $payload = $request->validate([
            'password' => ['required', 'string'],
        ]);

        // Konfirmasi identitas: password harus benar sebelum penghapusan permanen.
        if (! Hash::check($payload['password'], $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Password salah. Penghapusan akun dibatalkan.',
            ], 422);
        }

        // Batasi ke peserta. Akun pengelola (admin/instruktur/EO) diprovisi lewat
        // web dan sebagian datanya (mis. kuis yang dibuat) memakai FK cascade —
        // menghapusnya dari aplikasi bisa ikut menghapus materi milik bersama.
        if ($user->role !== 'participant') {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun pengelola tidak dapat dihapus lewat aplikasi. Silakan hubungi admin.',
            ], 403);
        }

        try {
            DB::transaction(function () use ($user) {
                // Cabut sesi & peran, lalu hard-delete. Seluruh data pribadi
                // (enrolmen, progres, submission, sertifikat, diskusi, dsb.) ikut
                // terhapus lewat foreign key onDelete('cascade'); referensi audit
                // di-set null. User tidak memakai SoftDeletes → benar-benar hilang.
                $user->forceFill(['api_token' => null])->save();

                try {
                    $user->syncRoles([]);
                } catch (\Throwable $e) {
                    // Spatie mungkin tidak tersedia di environment ini — abaikan.
                }

                $user->delete();
            });
        } catch (\Throwable $e) {
            Log::error('Gagal menghapus akun mobile: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus akun. Silakan coba lagi nanti.',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Akun kamu telah dihapus permanen.',
        ]);
    }

    private function issueToken(User $user): string
    {
        $token = Str::random(80);
        $user->forceFill(['api_token' => $token])->save();

        return $token;
    }
}
