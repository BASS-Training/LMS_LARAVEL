<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\EnrollmentCode;
use App\Services\EnrollmentCodeGenerator;
use Illuminate\Http\Request;

/**
 * Kelola kode pendaftaran pribadi sekali-pakai untuk sebuah course/kelas
 * (sistem baru, terpisah dari token bersama lama di CourseController).
 */
class EnrollmentCodeController extends Controller
{
    public function index(Course $course)
    {
        $classIds = $course->classes()->pluck('id');

        $codes = EnrollmentCode::with(['redeemer', 'courseClass'])
            ->where(function ($q) use ($course, $classIds) {
                $q->where('course_id', $course->id)
                    ->orWhereIn('course_class_id', $classIds);
            })
            ->latest()
            ->get();

        return view('courses.enrollment-codes', compact('course', 'codes'));
    }

    public function store(Request $request, Course $course, EnrollmentCodeGenerator $generator)
    {
        $data = $request->validate([
            'target' => ['required', 'string'],
            'count' => ['required', 'integer', 'min:1', 'max:500'],
            'issued_to_email' => ['nullable', 'email', 'max:255'],
            'expires_at' => ['nullable', 'date'],
            'prefix' => ['nullable', 'string', 'max:10', 'regex:/^[A-Za-z0-9\-]+$/'],
            'length' => ['required', 'integer', 'min:6', 'max:16'],
        ]);

        // Tentukan target: 'course' atau 'class:<id>'.
        $courseId = null;
        $courseClassId = null;

        if ($data['target'] === 'course') {
            $courseId = $course->id;
        } elseif (str_starts_with($data['target'], 'class:')) {
            $classId = (int) substr($data['target'], 6);
            $class = $course->classes()->find($classId);
            if (! $class) {
                return back()->withErrors(['target' => 'Kelas tidak ditemukan dalam course ini.']);
            }
            $courseClassId = $class->id;
        } else {
            return back()->withErrors(['target' => 'Target tidak valid.']);
        }

        try {
            $codes = $generator->generate([
                'course_id' => $courseId,
                'course_class_id' => $courseClassId,
                'issued_to_email' => $data['issued_to_email'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
                'prefix' => $data['prefix'] ?? null,
                'length' => $data['length'],
                'count' => $data['count'],
                'created_by' => $request->user()?->id,
            ]);
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Gagal membuat kode: '.$e->getMessage()]);
        }

        return back()->with('success', "Berhasil membuat {$codes->count()} kode pendaftaran.");
    }

    public function revoke(Course $course, EnrollmentCode $code)
    {
        if ($code->status === EnrollmentCode::STATUS_REDEEMED) {
            return back()->withErrors(['error' => 'Kode yang sudah dipakai tidak bisa dibatalkan.']);
        }

        $code->update(['status' => EnrollmentCode::STATUS_REVOKED]);

        return back()->with('success', 'Kode berhasil dibatalkan.');
    }

    public function destroy(Course $course, EnrollmentCode $code)
    {
        if ($code->status === EnrollmentCode::STATUS_REDEEMED) {
            return back()->withErrors(['error' => 'Kode yang sudah dipakai tidak bisa dihapus (untuk jejak audit).']);
        }

        $code->delete();

        return back()->with('success', 'Kode berhasil dihapus.');
    }
}
