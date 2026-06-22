<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Mobile agenda API. Lists upcoming & ongoing *scheduled* sessions (Zoom
 * content with a scheduled window) across every course the user can access.
 * Reads the same `contents.scheduled_start/scheduled_end` fields the web uses,
 * so the agenda stays in sync.
 */
class AgendaApiController extends Controller
{
    /**
     * Upcoming + ongoing scheduled sessions, soonest first. A session is kept
     * while it has not yet ended (`scheduled_end >= now`).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $courseIds = $this->accessibleCourseIds($user);
        $now = Carbon::now();

        $contents = Content::query()
            ->where('is_scheduled', true)
            ->whereNotNull('scheduled_start')
            ->where(function ($q) use ($now) {
                // Keep ongoing/upcoming; if no end set, fall back to start.
                $q->where('scheduled_end', '>=', $now)
                    ->orWhere(function ($x) use ($now) {
                        $x->whereNull('scheduled_end')->where('scheduled_start', '>=', $now);
                    });
            })
            ->whereHas('lesson', fn ($q) => $q->whereIn('course_id', $courseIds))
            ->with([
                'lesson:id,title,course_id',
                'lesson.course:id,title',
            ])
            ->orderBy('scheduled_start')
            ->limit(50)
            ->get();

        $items = $contents->map(function (Content $c) use ($now) {
            $course = $c->lesson?->course;
            $start = $c->scheduled_start;
            $end = $c->scheduled_end;

            $status = 'upcoming';
            if ($start && $now->lt($start)) {
                $status = 'upcoming';
            } elseif ($end && $now->between($start, $end)) {
                $status = 'ongoing';
            } elseif ($start && $now->gte($start) && !$end) {
                $status = 'ongoing';
            }

            return [
                'contentId' => (string) $c->id,
                'title' => $c->title,
                'type' => $c->type,
                'courseId' => $course ? (string) $course->id : null,
                'courseTitle' => $course?->title,
                'scheduledStart' => $start?->toISOString(),
                'scheduledEnd' => $end?->toISOString(),
                'status' => $status,
            ];
        })->values();

        return response()->json([
            'status' => 'success',
            'data' => $items,
        ]);
    }

    /**
     * Course ids the user may see: all for admins, otherwise courses they are
     * enrolled in or instruct. Mirrors DiscussionApiController.
     */
    private function accessibleCourseIds($user)
    {
        if ($user->can('manage all courses')) {
            return Course::pluck('id');
        }

        return Course::where(function ($q) use ($user) {
            $q->whereHas('enrolledUsers', fn ($x) => $x->where('users.id', $user->id))
                ->orWhereHas('instructors', fn ($x) => $x->where('users.id', $user->id));
        })->pluck('id');
    }
}
