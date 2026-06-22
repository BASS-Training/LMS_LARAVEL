<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Unified notification feed for the mobile app.
 *
 * Merges two sources, kept in sync with the web:
 *  - Laravel database notifications (discussion replies, grades, new content)
 *  - Announcements (pengumuman) — reuses the web announcement read-tracking.
 */
class NotificationApiController extends Controller
{
    /** Categories from the notifications table we surface in the mobile feed. */
    private const CATEGORIES = ['discussion_reply', 'grade', 'new_content'];

    /** Merged, date-sorted feed (most recent first). */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $items = collect()
            ->merge($this->notificationItems($user))
            ->merge($this->announcementItems($user))
            ->sortByDesc('createdAt')
            ->take(60)
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $items,
            'meta' => ['unreadCount' => $items->where('isRead', false)->count()],
        ]);
    }

    /** Total unread across both sources (for the bell badge). */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();

        $unreadNotifs = $user->unreadNotifications
            ->filter(fn ($n) => in_array($n->data['category'] ?? null, self::CATEGORIES, true))
            ->count();

        $unreadAnnouncements = Announcement::unreadForUser($user)->count();

        return response()->json([
            'status' => 'success',
            'data' => ['unreadCount' => $unreadNotifs + $unreadAnnouncements],
        ]);
    }

    /** Mark a single item read. Body: { source: notification|announcement, id }. */
    public function markRead(Request $request): JsonResponse
    {
        $data = $request->validate([
            'source' => 'required|in:notification,announcement',
            'id' => 'required|string',
        ]);
        $user = $request->user();

        if ($data['source'] === 'notification') {
            $user->notifications()->where('id', $data['id'])->first()?->markAsRead();
        } else {
            Announcement::find($data['id'])?->markAsReadBy($user);
        }

        return response()->json(['status' => 'success']);
    }

    /** Mark everything (both sources) read. */
    public function markAllRead(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->unreadNotifications
            ->filter(fn ($n) => in_array($n->data['category'] ?? null, self::CATEGORIES, true))
            ->each(fn ($n) => $n->markAsRead());

        Announcement::unreadForUser($user)->get()
            ->each(fn ($a) => $a->markAsReadBy($user));

        return response()->json(['status' => 'success']);
    }

    /** @return \Illuminate\Support\Collection<int,array> */
    private function notificationItems($user)
    {
        return $user->notifications()
            ->latest()
            ->take(60)
            ->get()
            ->filter(fn ($n) => in_array($n->data['category'] ?? null, self::CATEGORIES, true))
            ->map(function ($n) {
                $d = $n->data;

                return [
                    'id' => (string) $n->id,
                    'source' => 'notification',
                    'category' => $d['category'] ?? 'info',
                    'title' => $d['title'] ?? 'Notifikasi',
                    'message' => $d['message'] ?? '',
                    'courseId' => $d['courseId'] ?? null,
                    'courseTitle' => $d['courseTitle'] ?? null,
                    'contentId' => $d['contentId'] ?? null,
                    'lessonTitle' => $d['lessonTitle'] ?? null,
                    'discussionId' => $d['discussionId'] ?? null,
                    'isRead' => $n->read_at !== null,
                    'createdAt' => optional($n->created_at)?->toISOString(),
                ];
            })
            ->values();
    }

    /** @return \Illuminate\Support\Collection<int,array> */
    private function announcementItems($user)
    {
        $readIds = $user->readAnnouncements()->pluck('announcements.id')->all();

        return Announcement::forUser($user)
            ->latest()
            ->take(30)
            ->get()
            ->map(fn (Announcement $a) => [
                'id' => (string) $a->id,
                'source' => 'announcement',
                'category' => 'announcement',
                'title' => $a->title,
                'message' => Str::limit(trim(strip_tags($a->content)), 160),
                'level' => $a->level,
                'courseId' => null,
                'courseTitle' => null,
                'contentId' => null,
                'isRead' => in_array($a->id, $readIds),
                'createdAt' => optional($a->created_at)?->toISOString(),
            ])
            ->values();
    }
}
