<?php

namespace App\Services;

use App\Models\Course;
use App\Models\LearningPath;
use App\Models\User;

class LearningPathProgressService
{
    /**
     * @return array{courses:array<int, array{course:Course, enrolled:bool, progress:float, status:string, statusLabel:string}>, totalCourses:int, enrolledCourses:int, completedCourses:int, progressPercentage:float, recommendedCourse:?Course}
     */
    public function forUser(LearningPath $learningPath, User $user): array
    {
        $learningPath->loadMissing('courses.lessons.contents.quiz.questions');
        $enrolledIds = $user->courses()
            ->whereIn('courses.id', $learningPath->courses->pluck('id'))
            ->pluck('courses.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $courses = $learningPath->courses->map(function (Course $course) use ($user, $enrolledIds) {
            $enrolled = in_array($course->id, $enrolledIds, true);
            $progress = $enrolled ? $user->courseProgress($course) : 0.0;
            [$status, $statusLabel] = match (true) {
                ! $enrolled => ['not_enrolled', 'Belum terdaftar'],
                $progress >= 100 => ['completed', 'Selesai'],
                $progress > 0 => ['in_progress', 'Sedang dipelajari'],
                default => ['not_started', 'Belum dimulai'],
            };

            return compact('course', 'enrolled', 'progress', 'status', 'statusLabel');
        })->values();

        $totalCourses = $courses->count();

        return [
            'courses' => $courses->all(),
            'totalCourses' => $totalCourses,
            'enrolledCourses' => $courses->where('enrolled', true)->count(),
            'completedCourses' => $courses->where('status', 'completed')->count(),
            'progressPercentage' => $totalCourses > 0 ? round($courses->sum('progress') / $totalCourses, 1) : 0.0,
            'recommendedCourse' => $courses->firstWhere('status', '!=', 'completed')['course'] ?? null,
        ];
    }
}
