<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\PresentsMobileUser;
use App\Http\Controllers\Controller;
use App\Services\InstructorBioSanitizer;
use App\Services\UserAvatarService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileApiController extends Controller
{
    use PresentsMobileUser;

    /**
     * Update the authenticated user's profile (mobile). Accepts the same fields
     * collected at registration plus an optional avatar image (multipart).
     */
    public function update(
        Request $request,
        InstructorBioSanitizer $bioSanitizer,
        UserAvatarService $avatars
    ) {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'institution_name' => ['required', 'string', 'max:255'],
            'occupation' => ['required', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
            'remove_avatar' => ['nullable', 'boolean'],
            'instructor_bio' => [
                Rule::prohibitedIf(! $user->can('manage own courses')),
                'nullable',
                'string',
                'max:10000',
            ],
        ]);

        $user->name = $data['name'];
        $user->date_of_birth = $data['date_of_birth'];
        $user->gender = $data['gender'];
        $user->institution_name = $data['institution_name'];
        $user->occupation = $data['occupation'];

        if ($user->can('manage own courses') && array_key_exists('instructor_bio', $data)) {
            $user->instructor_bio = $bioSanitizer->sanitize($data['instructor_bio']);
        }

        $avatars->save($user, $request->file('avatar'), $request->boolean('remove_avatar'));

        return response()->json([
            'status' => 'success',
            'message' => 'Profil berhasil diperbarui.',
            'data' => ['user' => $this->mobileUserPayload($user)],
        ]);
    }
}
