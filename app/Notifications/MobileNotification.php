<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Generic database notification for the mobile app.
 *
 * Stored in the standard Laravel `notifications` table (shared with web) and
 * read back by the mobile NotificationApiController. The `category` inside the
 * payload drives the icon/colour and any deep-link on the client.
 *
 * Categories: discussion_reply | grade | new_content
 */
class MobileNotification extends Notification
{
    /**
     * @param array{category:string,title:string,message:string,courseId?:string|null,courseTitle?:string|null,contentId?:string|null,discussionId?:string|null} $payload
     */
    public function __construct(private array $payload)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->payload;
    }
}
