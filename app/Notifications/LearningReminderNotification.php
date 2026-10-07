<?php

namespace App\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LearningReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $reminderType,
        public readonly int $contentId,
        public readonly string $contentTitle,
        public readonly string $courseTitle,
        public readonly CarbonInterface $scheduledStart,
    ) {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isToday = $this->reminderType === 'h_0';

        return (new MailMessage)
            ->subject(($isToday ? 'Pengingat Hari Ini' : 'Pengingat Besok').' - BASS Academy')
            ->greeting("Halo {$notifiable->name},")
            ->line($isToday
                ? 'Sesi pembelajaran Anda akan segera dimulai.'
                : 'Anda memiliki sesi pembelajaran dalam 24 jam ke depan.')
            ->line("Sesi: {$this->contentTitle}")
            ->line("Course: {$this->courseTitle}")
            ->line('Jadwal: '.$this->scheduledStart->translatedFormat('d F Y, H:i').' WIB')
            ->action('Buka Materi', route('contents.show', $this->contentId))
            ->line('Preferensi pengingat jadwal dapat diubah melalui halaman profil.');
    }
}
