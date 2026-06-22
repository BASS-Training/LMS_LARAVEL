<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\Course;
use App\Models\Discussion;
use App\Models\DiscussionReply;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Mobile discussion API. Mirrors the web discussion feature (topic + replies)
 * for a given Content (a "lesson" in the mobile app). Shares the same
 * Discussion/DiscussionReply tables as the web, so posts sync both ways.
 */
class DiscussionApiController extends Controller
{
    /**
     * Aggregated discussion feed across all courses the user can access
     * (enrolled / instructs / admin), most-recent-activity first. Powers the
     * mobile discussion hub. Still grouped per lesson via `contentId`.
     */
    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();
        $courseIds = $this->accessibleCourseIds($user);

        $discussions = Discussion::with([
                'user:id,name',
                'content:id,title,lesson_id',
                'content.lesson:id,title,course_id',
                'content.lesson.course:id,title',
            ])
            ->withCount('replies')
            ->withMax('replies', 'created_at')
            ->whereHas('content.lesson', fn ($q) => $q->whereIn('course_id', $courseIds))
            ->get();

        $items = $discussions->map(function (Discussion $d) {
            $lastActivity = $d->replies_max_created_at
                ? Carbon::parse($d->replies_max_created_at)
                : $d->created_at;
            $course = $d->content?->lesson?->course;

            return [
                'id' => (string) $d->id,
                'title' => $d->title,
                'snippet' => Str::limit(trim(strip_tags($d->body)), 120),
                'contentId' => (string) $d->content_id,
                'lessonTitle' => $d->content?->title,
                'courseId' => $course ? (string) $course->id : null,
                'courseTitle' => $course?->title,
                'authorName' => $d->user?->name ?? 'Pengguna',
                'repliesCount' => (int) ($d->replies_count ?? 0),
                'lastActivityAt' => optional($lastActivity)?->toISOString(),
            ];
        })
        ->sortByDesc('lastActivityAt')
        ->take(60)
        ->values();

        return response()->json(['status' => 'success', 'data' => $items]);
    }

    /**
     * Course → lesson (content) structure for the discussion hub's context
     * selector, with a discussion count per lesson. Scoped to courses the user
     * can access. "Lesson" here == backend Content (mobile maps lesson==content).
     */
    public function structure(Request $request): JsonResponse
    {
        $user = $request->user();
        $courseIds = $this->accessibleCourseIds($user);

        $counts = Discussion::query()
            ->selectRaw('content_id, COUNT(*) as c')
            ->groupBy('content_id')
            ->pluck('c', 'content_id');

        $courses = Course::whereIn('id', $courseIds)
            ->with([
                'lessons' => fn ($q) => $q->orderBy('order'),
                'lessons.contents' => fn ($q) => $q->orderBy('order'),
            ])
            ->orderBy('title')
            ->get();

        $data = $courses->map(function (Course $course) use ($counts) {
            $lessons = collect();
            foreach ($course->lessons as $lesson) {
                foreach ($lesson->contents as $content) {
                    $lessons->push([
                        'contentId' => (string) $content->id,
                        'lessonTitle' => $content->title,
                        'type' => $content->type,
                        'discussionCount' => (int) ($counts[$content->id] ?? 0),
                    ]);
                }
            }

            return [
                'courseId' => (string) $course->id,
                'courseTitle' => $course->title,
                'lessons' => $lessons->values(),
            ];
        })
        ->filter(fn ($c) => $c['lessons']->isNotEmpty())
        ->values();

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    /** Course ids the user can access: enrolled / instructs, or admin = all. */
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

    /** List discussions (with replies) for a lesson/content. */
    public function index(Content $content): JsonResponse
    {
        if ($error = $this->ensureAccess($content, request())) {
            return $error;
        }

        $content->load(['discussions.user', 'discussions.replies.user']);

        return response()->json([
            'status' => 'success',
            'data' => $content->discussions->map(fn (Discussion $d) => $this->transform($d))->values(),
        ]);
    }

    /** Start a new discussion topic on a lesson/content. */
    public function store(Request $request, Content $content): JsonResponse
    {
        if ($error = $this->ensureAccess($content, $request)) {
            return $error;
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        $discussion = $content->discussions()->create([
            'user_id' => $request->user()->id,
            'title' => $data['title'],
            'body' => $data['body'],
        ]);

        $discussion->load(['user', 'replies.user']);

        return response()->json([
            'status' => 'success',
            'data' => $this->transform($discussion),
        ], 201);
    }

    /** Reply to an existing discussion. */
    public function storeReply(Request $request, Discussion $discussion): JsonResponse
    {
        $discussion->loadMissing('content');

        if ($error = $this->ensureAccess($discussion->content, $request)) {
            return $error;
        }

        $data = $request->validate([
            'body' => 'required|string',
        ]);

        $reply = $discussion->replies()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        $reply->load('user');

        return response()->json([
            'status' => 'success',
            'data' => $this->transformReply($reply),
        ], 201);
    }

    /** Only enrolled participants / instructors / EOs / admins may view or post. */
    private function ensureAccess(?Content $content, Request $request): ?JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        if (! $content) {
            return response()->json(['status' => 'error', 'message' => 'Konten tidak ditemukan.'], 404);
        }

        $content->loadMissing('lesson.course');
        $course = $content->lesson?->course;
        if (! $course) {
            return response()->json(['status' => 'error', 'message' => 'Course tidak ditemukan.'], 404);
        }

        if (
            $user->can('manage all courses') ||
            $user->isInstructorFor($course) ||
            $user->isEventOrganizerFor($course) ||
            $user->isEnrolled($course)
        ) {
            return null;
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Anda belum terdaftar di course ini.',
        ], 403);
    }

    private function transform(Discussion $discussion): array
    {
        return [
            'id' => (string) $discussion->id,
            'title' => $discussion->title,
            'body' => $discussion->body,
            'authorId' => (string) $discussion->user_id,
            'authorName' => $discussion->user?->name ?? 'Pengguna',
            'createdAt' => optional($discussion->created_at)?->toISOString(),
            'repliesCount' => $discussion->replies->count(),
            'replies' => $discussion->replies->map(fn (DiscussionReply $r) => $this->transformReply($r))->values(),
        ];
    }

    private function transformReply(DiscussionReply $reply): array
    {
        return [
            'id' => (string) $reply->id,
            'body' => $reply->body,
            'authorId' => (string) $reply->user_id,
            'authorName' => $reply->user?->name ?? 'Pengguna',
            'createdAt' => optional($reply->created_at)?->toISOString(),
        ];
    }
}
