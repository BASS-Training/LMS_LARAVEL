<?php

namespace App\Observers;

use App\Models\Discussion;
use App\Models\User;
use App\Notifications\MobileNotification;

/**
 * Memberi tahu instruktur pengampu kelas ketika ada diskusi/topik baru dibuat
 * peserta — dari web maupun mobile (keduanya memakai model Discussion yang sama).
 * Balasan ditangani terpisah oleh DiscussionReplyObserver.
 *
 * Memakai kategori `discussion_reply` agar deep-link klien membuka utas yang
 * sama (highlight topik ini).
 */
class DiscussionObserver
{
    public function created(Discussion $discussion): void
    {
        $discussion->loadMissing(['content.lesson.course.instructors', 'user']);

        $content = $discussion->content;
        $course = $content?->lesson?->course;
        if (! $course) {
            return;
        }

        $authorId = $discussion->user_id;
        $authorName = $discussion->user?->name ?? 'Seseorang';

        // Instruktur pengampu, kecuali bila dia sendiri yang membuat diskusi.
        $recipientIds = $course->instructors
            ->pluck('id')
            ->reject(fn ($id) => (int) $id === (int) $authorId)
            ->unique()
            ->values();

        if ($recipientIds->isEmpty()) {
            return;
        }

        $payload = [
            'category' => 'discussion_reply',
            'title' => 'Diskusi baru',
            'message' => $authorName.' membuat diskusi "'.$discussion->title.'" di '.$course->title,
            'courseId' => (string) $course->id,
            'courseTitle' => $course->title,
            'contentId' => $content ? (string) $content->id : null,
            'lessonTitle' => $content?->title,
            'discussionId' => (string) $discussion->id,
        ];

        foreach (User::whereIn('id', $recipientIds)->get() as $user) {
            $user->notify(new MobileNotification($payload));
        }
    }
}
