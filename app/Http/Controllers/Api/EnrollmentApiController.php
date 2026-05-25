<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnrollmentApiController extends Controller
{
    public function enroll(Request $request)
    {
        $payload = $request->validate([
            'token' => ['required', 'string', 'max:20'],
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $token = strtoupper(trim($payload['token']));

        return DB::transaction(function () use ($token, $user) {
            $course = Course::where('enrollment_token', $token)
                ->lockForUpdate()
                ->first();

            if ($course) {
                if (!$course->token_enabled) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Token tidak aktif atau sudah tidak berlaku.',
                    ], 422);
                }

                if (!$course->isTokenValid()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Token sudah kadaluarsa.',
                    ], 422);
                }

                if ($accessError = $this->getProgramAccessError($user, $course->program_type ?? 'regular')) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $accessError,
                    ], 403);
                }

                if ($course->enrolledUsers()->where('users.id', $user->id)->exists()) {
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Anda sudah terdaftar di course ini.',
                        'data' => [
                            'type' => 'course',
                            'courseId' => (string) $course->id,
                            'courseTitle' => $course->title,
                            'alreadyEnrolled' => true,
                        ],
                    ]);
                }

                $course->enrolledUsers()->attach($user->id);

                return response()->json([
                    'status' => 'success',
                    'message' => "Berhasil bergabung dengan course: {$course->title}",
                    'data' => [
                        'type' => 'course',
                        'courseId' => (string) $course->id,
                        'courseTitle' => $course->title,
                    ],
                ], 201);
            }

            $class = CourseClass::where('enrollment_token', $token)
                ->with('course')
                ->lockForUpdate()
                ->first();

            if ($class) {
                if (!$class->token_enabled) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Token kelas tidak aktif atau sudah tidak berlaku.',
                    ], 422);
                }

                if (!$class->isTokenValid()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Token kelas sudah kadaluarsa.',
                    ], 422);
                }

                $course = $class->course;
                if (!$course) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Course untuk kelas ini tidak ditemukan.',
                    ], 404);
                }

                if ($accessError = $this->getProgramAccessError($user, $class->program_type ?? ($course->program_type ?? 'regular'))) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $accessError,
                    ], 403);
                }

                if (!$class->hasAvailableSlots()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Kelas sudah penuh. Maksimal {$class->max_participants} peserta.",
                    ], 422);
                }

                if (!$course->enrolledUsers()->where('users.id', $user->id)->exists()) {
                    $course->enrolledUsers()->attach($user->id);
                }

                if (!$class->participants()->where('users.id', $user->id)->exists()) {
                    $class->participants()->attach($user->id);
                }

                return response()->json([
                    'status' => 'success',
                    'message' => "Berhasil bergabung dengan kelas: {$class->name} di course {$course->title}",
                    'data' => [
                        'type' => 'class',
                        'courseId' => (string) $course->id,
                        'courseTitle' => $course->title,
                        'classId' => (string) $class->id,
                        'className' => $class->name,
                    ],
                ], 201);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Token tidak valid atau sudah tidak aktif.',
            ], 422);
        });
    }

    private function getProgramAccessError($user, string $programType): ?string
    {
        if ($programType !== 'avpn_ai') {
            return null;
        }

        if ($user->avpn_verification_status === 'pending') {
            return 'Akses kelas Literasi AI (AVPN) masih pending. Tunggu validasi admin.';
        }

        if ($user->avpn_verification_status === 'rejected') {
            return 'Akses kelas Literasi AI (AVPN) ditolak. Hubungi admin untuk klarifikasi.';
        }

        if (!$user->canAccessProgram('avpn_ai')) {
            return 'Akses kelas Literasi AI (AVPN) belum aktif.';
        }

        return null;
    }
}