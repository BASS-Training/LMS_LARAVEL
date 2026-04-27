<?php

namespace App\Services;

use App\Models\Course;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class CourseParticipantQueryService
{
    public function buildQuery(Course $course, string $filter, ?int $classId = null): Builder
    {
        return $filter === 'class'
            ? $this->buildClassQuery($course, $classId)
            : $this->buildAllQuery($course);
    }

    /**
     * Build query for all course enrollees.
     *
     * Uses a subquery to pick MAX(id) from course_class_user per user,
     * guaranteeing exactly one row per user even if enrolled in multiple classes.
     */
    public function buildAllQuery(Course $course): Builder
    {
        // Subquery: latest class enrollment per user (by highest id → no timestamp ties)
        $latestSub = DB::table('course_class_user as ccu_inner')
            ->select('ccu_inner.user_id', DB::raw('MAX(ccu_inner.id) as max_id'))
            ->join('course_classes as cc_inner', 'cc_inner.id', '=', 'ccu_inner.course_class_id')
            ->where('cc_inner.course_id', $course->id)
            ->groupBy('ccu_inner.user_id');

        return DB::table('users')
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'cu.created_at as course_enrolled_at',
                'ccu.created_at as class_enrolled_at',
                'cc.name as class_name',
                'cc.status as class_status',
            ])
            ->join('course_user as cu', function ($join) use ($course) {
                $join->on('cu.user_id', '=', 'users.id')
                     ->where('cu.course_id', '=', $course->id);
            })
            ->leftJoinSub($latestSub, 'latest', fn ($j) => $j->on('latest.user_id', '=', 'users.id'))
            ->leftJoin('course_class_user as ccu', 'ccu.id', '=', 'latest.max_id')
            ->leftJoin('course_classes as cc', 'cc.id', '=', 'ccu.course_class_id')
            ->orderBy('users.name');
    }

    /**
     * Build query for a single class's participants.
     */
    public function buildClassQuery(Course $course, int $classId): Builder
    {
        return DB::table('users')
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'cu.created_at as course_enrolled_at',
                'ccu.created_at as class_enrolled_at',
                'cc.name as class_name',
                'cc.status as class_status',
            ])
            ->join('course_class_user as ccu', function ($join) use ($classId) {
                $join->on('ccu.user_id', '=', 'users.id')
                     ->where('ccu.course_class_id', '=', $classId);
            })
            ->leftJoin('course_user as cu', function ($join) use ($course) {
                $join->on('cu.user_id', '=', 'users.id')
                     ->where('cu.course_id', '=', $course->id);
            })
            ->join('course_classes as cc', 'cc.id', '=', 'ccu.course_class_id')
            ->orderBy('users.name');
    }

    public function countForFilter(Course $course, string $filter, ?int $classId = null): int
    {
        if ($filter === 'class' && $classId) {
            return DB::table('course_class_user')
                ->where('course_class_id', $classId)
                ->count();
        }

        return DB::table('course_user')
            ->where('course_id', $course->id)
            ->count();
    }
}
