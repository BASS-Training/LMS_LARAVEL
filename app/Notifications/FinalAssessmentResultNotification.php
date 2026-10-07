<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FinalAssessmentResultNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $assessmentType,
        public readonly int $submissionId,
        public readonly int $contentId,
        public readonly string $contentTitle,
        public readonly string $courseTitle,
        public readonly string $status,
        public readonly ?int $attempt = null,
    ) {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $typeLabel = match ($this->assessmentType) {
            'essay' => 'essay',
            'case_study' => 'studi kasus',
            'document' => 'tugas dokumen',
        };
        $resultLabel = match ($this->status) {
            'passed' => 'Lulus',
            'failed' => 'Perlu revisi',
            'reviewed' => 'Selesai ditinjau',
            default => 'Selesai dinilai',
        };
        $url = $this->assessmentType === 'essay'
            ? route('essays.result', $this->submissionId)
            : route('contents.show', $this->contentId);

        return (new MailMessage)
            ->subject("Hasil {$typeLabel} tersedia - BASS Academy")
            ->greeting("Halo {$notifiable->name},")
            ->line("Hasil {$typeLabel} \"{$this->contentTitle}\" pada course {$this->courseTitle} sudah tersedia.")
            ->line("Status: {$resultLabel}")
            ->action('Lihat Hasil', $url)
            ->line('Silakan masuk menggunakan akun Anda untuk melihat nilai dan feedback instruktur.');
    }
}
