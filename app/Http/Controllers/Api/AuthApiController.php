<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthApiController extends Controller
{
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
            'data' => $this->transformUserResponse($user, $token),
        ]);
    }

    public function register(Request $request)
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['nullable', Rule::in(['participant', 'instructor'])],
        ]);

        $email = strtolower(trim($payload['email']));

        $user = User::create([
            'name' => $payload['name'],
            'email' => $email,
            'password' => $payload['password'],
            'role' => $payload['role'] ?? 'participant',
        ]);

        if (($payload['role'] ?? 'participant') === 'instructor') {
            try {
                $user->assignRole('instructor');
            } catch (\Throwable $e) {
                // Keep legacy role column if Spatie role is unavailable in this environment.
            }
        } elseif ($user->roles()->count() === 0) {
            try {
                $user->assignRole('participant');
            } catch (\Throwable $e) {
                // Ignore and rely on legacy role column.
            }
        }

        $token = $this->issueToken($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Registrasi berhasil.',
            'data' => $this->transformUserResponse($user, $token),
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
            'data' => $this->transformUserResponse($user),
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

    private function transformUserResponse(User $user, ?string $token = null): array
    {
        $roles = $this->extractRoles($user);

        return [
            'token' => $token,
            'user' => [
                'id' => (string) $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $this->resolvePrimaryRole($roles, $user->role ?? null),
                'primary_role' => $this->resolvePrimaryRole($roles, $user->role ?? null),
                'roles' => $roles,
            ],
        ];
    }

    private function extractRoles(User $user): array
    {
        $roles = $user->getRoleNames()->values()->all();

        if (empty($roles) && !empty($user->role)) {
            $roles = [(string) $user->role];
        }

        return array_values(array_unique(array_map('strval', $roles)));
    }

    private function resolvePrimaryRole(array $roles, ?string $fallback = null): string
    {
        $precedence = ['super-admin', 'admin', 'instructor', 'event-organizer', 'participant'];

        foreach ($precedence as $role) {
            if (in_array($role, $roles, true)) {
                return $role;
            }
        }

        return $fallback ?: 'participant';
    }

    private function issueToken(User $user): string
    {
        $token = Str::random(80);
        $user->forceFill(['api_token' => $token])->save();

        return $token;
    }
}
