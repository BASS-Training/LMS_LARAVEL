<?php

namespace App\Notifications;

use App\Models\Certificate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CertificateIssuedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public readonly string $certificateCode;

    public readonly string $courseTitle;

    public function __construct(Certificate $certificate)
    {
        $this->certificateCode = $certificate->certificate_code;
        $this->courseTitle = $certificate->course?->title ?? 'Program BASS Academy';
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Sertifikat Anda Telah Terbit - BASS Academy')
            ->greeting("Halo {$notifiable->name},")
            ->line("Sertifikat untuk {$this->courseTitle} telah terbit dan siap diunduh.")
            ->line("Kode verifikasi: {$this->certificateCode}")
            ->action('Unduh Sertifikat', route('certificates.public-download', $this->certificateCode))
            ->line('Keaslian sertifikat dapat diperiksa melalui halaman verifikasi publik: '.route('certificates.verify', $this->certificateCode));
    }
}
