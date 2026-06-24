<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\User;

/**
 * Shared shaping of the mobile "user" payload, used by both auth (login /
 * register / me) and profile update so the client always receives the same
 * fields in the same shape.
 */
trait PresentsMobileUser
{
    /** Full auth envelope: token (nullable) + user. */
    protected function presentMobileUser(User $user, ?string $token = null): array
    {
        return [
            'token' => $token,
            'user' => $this->mobileUserPayload($user),
        ];
    }

    /** Just the user object, for endpoints that don't issue a token. */
    protected function mobileUserPayload(User $user): array
    {
        $roles = $this->extractRoles($user);

        return [
            'id' => (string) $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $this->resolvePrimaryRole($roles, $user->role ?? null),
            'primary_role' => $this->resolvePrimaryRole($roles, $user->role ?? null),
            'roles' => $roles,
            'registration_program' => $user->registration_program,
            'avpn_verification_status' => $user->avpn_verification_status,
            'date_of_birth' => optional($user->date_of_birth)?->format('Y-m-d'),
            'gender' => $user->gender,
            'institution_name' => $user->institution_name,
            'occupation' => $user->occupation,
            'avatar_url' => $user->avatar ? asset('storage/'.$user->avatar) : null,
            'created_at' => optional($user->created_at)?->toISOString(),
            // Status verifikasi email (soft enforcement):
            'email_verified' => $user->isEmailVerified(),
            'must_verify_email' => $user->mustVerifyEmail(),          // akun BARU wajib verifikasi
            'should_nudge_verify_email' => $user->shouldNudgeEmailVerification(), // saran (akun lama)
        ];
    }

    protected function extractRoles(User $user): array
    {
        $roles = $user->getRoleNames()->values()->all();

        if (empty($roles) && ! empty($user->role)) {
            $roles = [(string) $user->role];
        }

        return array_values(array_unique(array_map('strval', $roles)));
    }

    protected function resolvePrimaryRole(array $roles, ?string $fallback = null): string
    {
        $precedence = ['super-admin', 'admin', 'instructor', 'event-organizer', 'participant'];

        foreach ($precedence as $role) {
            if (in_array($role, $roles, true)) {
                return $role;
            }
        }

        return $fallback ?: 'participant';
    }
}
