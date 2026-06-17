<?php

namespace App\Observers;

use App\Models\DiscussionReply;
use App\Models\User;
use App\Notifications\MobileNotification;

/**
 * Fires a "balasan diskusi" notification whenever a reply is created — from
 * either the web or the mobile API (both use the same DiscussionReply model),
 * so notifications stay in sync.
 */
class DiscussionReplyObserver
{
    public function created(DiscussionReply $reply): void
    {
        $reply->loadMissing(['discussion.content.lesson.course', 'user']);

        $discussion = $reply->discussion;
        if (! $discussion) {
            return;
        }

        $content = $discussion->content;
        $course = $content?->lesson?->course;
        $actorId = $reply->user_id;
        $actorName = $reply->user?->name ?? 'Seseorang';

        // Notify the topic owner and everyone else who has replied — minus the
        // person who just replied.
        $recipientIds = collect([$discussion->user_id])
            ->merge($discussion->replies()->pluck('user_id'))
            ->unique()
            ->reject(fn ($id) => (int) $id === (int) $actorId)
            ->values();

        if ($recipientIds->isEmpty()) {
            return;
        }

        $payload = [
            'category' => 'discussion_reply',
            'title' => 'Balasan diskusi baru',
            'message' => $actorName.' membalas diskusi "'.$discussion->title.'"',
            'courseId' => $course ? (string) $course->id : null,
            'courseTitle' => $course?->title,
            'contentId' => $content ? (string) $content->id : null,
            'lessonTitle' => $content?->title,
            'discussionId' => (string) $discussion->id,
        ];

        foreach (User::whereIn('id', $recipientIds)->get() as $user) {
            $user->notify(new MobileNotification($payload));
        }
    }
}
