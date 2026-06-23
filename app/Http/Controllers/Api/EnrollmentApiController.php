<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseClass;
use App\Models\EnrollmentCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnrollmentApiController extends Controller
{
    public function enroll(Request $request)
    {
        $payload = $request->validate([
            'token' => ['required', 'string', 'max:32'],
        ]);

        $user = $request->user();
        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $token = strtoupper(trim($payload['token']));

        return DB::transaction(function () use ($token, $user) {
            // === JALUR BARU: kode pendaftaran sekali-pakai (aditif) ===
            // Dahulukan pencarian di tabel enrollment_codes. Jika tidak ada
            // yang cocok, jatuh ke jalur token bersama lama di bawah, sehingga
            // perilaku course/kelas lama TIDAK berubah sama sekali.
            $code = EnrollmentCode::where('code', $token)
                ->lockForUpdate()
                ->first();

            if ($code) {
                return $this->redeemEnrollmentCode($code, $user);
            }

            // === JALUR LAMA: token bersama (tidak diubah) ===
            $course = Course::where('enrollment_token', $token)
                ->lockForUpdate()
                ->first();

            if ($course) {
                if (! $course->token_enabled) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Token tidak aktif atau sudah tidak berlaku.',
                    ], 422);
                }

                if (! $course->isTokenValid()) {
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
                if (! $class->token_enabled) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Token kelas tidak aktif atau sudah tidak berlaku.',
                    ], 422);
                }

                if (! $class->isTokenValid()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Token kelas sudah kadaluarsa.',
                    ], 422);
                }

                $course = $class->course;
                if (! $course) {
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

                if (! $class->hasAvailableSlots()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Kelas sudah penuh. Maksimal {$class->max_participants} peserta.",
                    ], 422);
                }

                if (! $course->enrolledUsers()->where('users.id', $user->id)->exists()) {
                    $course->enrolledUsers()->attach($user->id);
                }

                if (! $class->participants()->where('users.id', $user->id)->exists()) {
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

    /**
     * Redeem kode pendaftaran sekali-pakai (jalur baru).
     *
     * Dipanggil dari dalam transaksi enroll() dengan $code yang sudah di-lock
     * (lockForUpdate) sehingga aman dari race condition.
     *
     * Catatan keamanan data: method ini HANYA melakukan attach (mendaftarkan),
     * tidak pernah detach — kepemilikan course yang sudah ada tidak tersentuh.
     */
    private function redeemEnrollmentCode(EnrollmentCode $code, User $user)
    {
        // 1. Validasi kode: sekali-pakai, kadaluarsa, dibatalkan, bind email.
        if ($error = $code->redeemErrorFor($user)) {
            return response()->json([
                'status' => 'error',
                'message' => $error,
            ], 422);
        }

        // 2. Tentukan target: kelas (prioritas) atau course.
        $class = null;
        $course = null;

        if ($code->course_class_id) {
            $class = CourseClass::with('course')->find($code->course_class_id);
            if (! $class) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Kelas untuk kode ini tidak ditemukan.',
                ], 404);
            }
            $course = $class->course;
            if (! $course) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Course untuk kelas ini tidak ditemukan.',
                ], 404);
            }
        } elseif ($code->course_id) {
            $course = Course::find($code->course_id);
            if (! $course) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Course untuk kode ini tidak ditemukan.',
                ], 404);
            }
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Kode tidak terhubung ke course mana pun.',
            ], 422);
        }

        // 3. Gating program (mis. AVPN) — sama seperti jalur token lama.
        $programType = $class
            ? ($class->program_type ?? ($course->program_type ?? 'regular'))
            : ($course->program_type ?? 'regular');

        if ($accessError = $this->getProgramAccessError($user, $programType)) {
            return response()->json([
                'status' => 'error',
                'message' => $accessError,
            ], 403);
        }

        // 4. Jika sudah terdaftar: jangan dobel, dan JANGAN konsumsi kode
        //    (biar kode tidak hangus karena salah pencet).
        if ($class) {
            if ($class->participants()->where('users.id', $user->id)->exists()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Anda sudah terdaftar di kelas ini.',
                    'data' => [
                        'type' => 'class',
                        'courseId' => (string) $course->id,
                        'courseTitle' => $course->title,
                        'classId' => (string) $class->id,
                        'className' => $class->name,
                        'alreadyEnrolled' => true,
                    ],
                ]);
            }

            if (! $class->hasAvailableSlots()) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Kelas sudah penuh. Maksimal {$class->max_participants} peserta.",
                ], 422);
            }

            if (! $course->enrolledUsers()->where('users.id', $user->id)->exists()) {
                $course->enrolledUsers()->attach($user->id);
            }
            $class->participants()->attach($user->id);
        } else {
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
        }

        // 5. Sukses → konsumsi kode (sekali-pakai).
        $code->markRedeemedBy($user);

        return response()->json([
            'status' => 'success',
            'message' => $class
                ? "Berhasil bergabung dengan kelas: {$class->name} di course {$course->title}"
                : "Berhasil bergabung dengan course: {$course->title}",
            'data' => $class
                ? [
                    'type' => 'class',
                    'courseId' => (string) $course->id,
                    'courseTitle' => $course->title,
                    'classId' => (string) $class->id,
                    'className' => $class->name,
                ]
                : [
                    'type' => 'course',
                    'courseId' => (string) $course->id,
                    'courseTitle' => $course->title,
                ],
        ], 201);
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

        if (! $user->canAccessProgram('avpn_ai')) {
            return 'Akses kelas Literasi AI (AVPN) belum aktif.';
        }

        return null;
    }
}
