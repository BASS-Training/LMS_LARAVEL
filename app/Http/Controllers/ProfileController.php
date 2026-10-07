<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\InstructorBioSanitizer;
use App\Services\UserAvatarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(
        ProfileUpdateRequest $request,
        InstructorBioSanitizer $bioSanitizer,
        UserAvatarService $avatars
    ): RedirectResponse {
        $user = $request->user();
        $user->fill($request->safe()->except(['avatar', 'remove_avatar', 'instructor_bio']));

        if ($user->can('manage own courses') && $request->has('instructor_bio')) {
            $user->instructor_bio = $bioSanitizer->sanitize($request->input('instructor_bio'));
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $avatars->save($user, $request->file('avatar'), $request->boolean('remove_avatar'));

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Request AVPN verification for regular registrants.
     */
    public function requestAvpnVerification(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avpn_verification_status === 'approved') {
            return back()->with('info', 'Akses AVPN pada akun Anda sudah aktif.');
        }

        if ($user->avpn_verification_status === 'pending') {
            return back()->with('info', 'Pengajuan verifikasi AVPN Anda masih diproses admin.');
        }

        $user->update([
            'avpn_verification_status' => 'pending',
            'avpn_google_form_submitted_at' => now(),
            'avpn_verified_at' => null,
            'avpn_verified_by' => null,
            'avpn_rejection_reason' => null,
        ]);

        \App\Models\ActivityLog::log('avpn_verification_requested', [
            'description' => "Requested AVPN verification: {$user->name}",
            'metadata' => [
                'participant_id' => $user->id,
                'participant_name' => $user->name,
                'participant_email' => $user->email,
                'registration_program' => $user->registration_program,
            ],
        ]);

        return back()->with('success', 'Pengajuan verifikasi AVPN berhasil dikirim. Silakan tunggu validasi admin.');
    }

    public function updateEmailPreferences(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'learning_reminder_email_enabled' => ['required', 'boolean'],
        ]);

        $request->user()->update([
            'learning_reminder_email_enabled' => (bool) $validated['learning_reminder_email_enabled'],
        ]);

        return Redirect::route('profile.edit')->with('status', 'email-preferences-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
