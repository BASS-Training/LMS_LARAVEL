<?php

namespace App\Support;

use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Filter peserta per kelas (course_classes) untuk halaman admin/instruktur:
 * Diskusi, Penilaian Essay, Nilai Quiz, Progress, Attendance.
 */
class ClassFilter
{
    /**
     * Daftar kelas yang boleh dipilih user pada course ini.
     * Instruktur murni hanya melihat kelas yang ia ampu; peran lain melihat semua kelas.
     */
    public static function options(User $user, Course $course): Collection
    {
        $query = $course->classes()->reorder()->orderBy('start_date')->orderBy('name');

        if ($user->isInstructorFor($course) && ! $user->can('manage all courses') && ! $user->isEventOrganizerFor($course)) {
            $ids = $user->instructorPeriods()->where('course_id', $course->id)->pluck('course_classes.id');
            if ($ids->isNotEmpty()) {
                $query->whereIn('id', $ids);
            }
        }

        return $query->get();
    }

    /** class_id dari query string, hanya jika termasuk opsi yang diizinkan. */
    public static function selected(Request $request, Collection $options): ?int
    {
        $id = (int) $request->query('class_id');

        return $id && $options->contains('id', $id) ? $id : null;
    }

    /** Batasi query User ke peserta kelas terpilih. */
    public static function apply($query, ?int $classId)
    {
        if ($classId) {
            $query->whereHas('participantClasses', fn ($q) => $q->where('course_classes.id', $classId));
        }

        return $query;
    }

    /** ID user peserta kelas terpilih (null jika tidak ada filter). */
    public static function participantIds(?int $classId): ?Collection
    {
        if (! $classId) {
            return null;
        }

        return User::whereHas('participantClasses', fn ($q) => $q->where('course_classes.id', $classId))->pluck('id');
    }
}
