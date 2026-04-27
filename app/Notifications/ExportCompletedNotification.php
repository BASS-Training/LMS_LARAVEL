<?php

namespace App\Notifications;

use App\Models\ExportHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ExportCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ExportHistory $exportHistory) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        // Avoid calling route() here — queue worker may not have fresh route cache.
        // The download URL is constructed by the UI using export_id.
        return [
            'type'         => 'export_completed',
            'export_id'    => $this->exportHistory->id,
            'course_id'    => $this->exportHistory->course_id,
            'course_title' => optional($this->exportHistory->course)->title ?? '',
            'filter'       => $this->exportHistory->filter,
            'exported_at'  => now()->toISOString(),
        ];
    }
}
