<?php

namespace App\Notifications;

use App\Models\ExportHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExportCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ExportHistory $exportHistory)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $courseTitle = optional($this->exportHistory->course)->title ?? 'Course BASS Academy';

        return (new MailMessage)
            ->subject('Export Peserta Selesai - BASS Academy')
            ->greeting("Halo {$notifiable->name},")
            ->line("Export data peserta untuk {$courseTitle} telah selesai diproses.")
            ->line("Filter: {$this->exportHistory->filter}")
            ->action('Unduh Export', route('exports.download', $this->exportHistory->id))
            ->line('Tautan unduhan hanya dapat diakses oleh akun yang membuat export.');
    }

    public function toDatabase(object $notifiable): array
    {
        // Avoid calling route() here — queue worker may not have fresh route cache.
        // The download URL is constructed by the UI using export_id.
        return [
            'type' => 'export_completed',
            'export_id' => $this->exportHistory->id,
            'course_id' => $this->exportHistory->course_id,
            'course_title' => optional($this->exportHistory->course)->title ?? '',
            'filter' => $this->exportHistory->filter,
            'exported_at' => now()->toISOString(),
        ];
    }
}
