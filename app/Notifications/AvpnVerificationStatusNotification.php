<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AvpnVerificationStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    public function __construct(
        public readonly string $status,
        public readonly ?string $reason = null,
    ) {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $approved = $this->status === self::APPROVED;
        $mail = (new MailMessage)
            ->subject($approved
                ? 'Verifikasi AVPN Disetujui - BASS Academy'
                : 'Verifikasi AVPN Belum Disetujui - BASS Academy')
            ->greeting("Halo {$notifiable->name},")
            ->line($approved
                ? 'Verifikasi pendaftaran AVPN Anda telah disetujui. Anda sekarang dapat mengakses program AVPN yang tersedia.'
                : 'Verifikasi pendaftaran AVPN Anda belum dapat disetujui.');

        if (! $approved && $this->reason) {
            $mail->line("Alasan: {$this->reason}");
        }

        return $mail
            ->line($approved
                ? 'Silakan masuk ke dashboard untuk mulai belajar.'
                : 'Periksa kembali data Anda lalu ajukan verifikasi ulang melalui halaman profil.')
            ->action($approved ? 'Buka Dashboard' : 'Buka Profil', $approved ? route('dashboard') : route('profile.edit'));
    }
}
