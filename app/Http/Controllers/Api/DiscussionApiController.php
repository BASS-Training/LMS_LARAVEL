<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\Discussion;
use App\Models\DiscussionReply;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mobile discussion API. Mirrors the web discussion feature (topic + replies)
 * for a given Content (a "lesson" in the mobile app). Shares the same
 * Discussion/DiscussionReply tables as the web, so posts sync both ways.
 */
class DiscussionApiController extends Controller
{
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
