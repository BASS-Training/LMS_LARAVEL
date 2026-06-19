<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\PresentsMobileUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthApiController extends Controller
{
    use PresentsMobileUser;

    public function login(Request $request)
    {
        $payload = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = strtolower(trim($payload['email']));

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
        if (!$user || !Hash::check($payload['password'], $user->password)) {
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
        ]);

        try {
            $user->assignRole('participant');
        } catch (\Throwable $e) {
            // Keep legacy role column if Spatie role is unavailable in this environment.
        }

        $token = $this->issueToken($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Registrasi berhasil.',
            'data' => $this->presentMobileUser($user, $token),
        ], 201);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        if (!$user) {
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

    private function issueToken(User $user): string
    {
        $token = Str::random(80);
        $user->forceFill(['api_token' => $token])->save();

        return $token;
    }
}
