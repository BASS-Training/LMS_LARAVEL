<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Email berisi kode OTP 6-digit untuk verifikasi email / reset password.
 */
class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $purpose,
        public ?string $name = null,
        public int $expiresMinutes = 10,
    ) {}

    public function build(): self
    {
        $subject = $this->purpose === 'password_reset'
            ? 'Kode Reset Password - BASS Academy'
            : 'Kode Verifikasi Email - BASS Academy';

        return $this->subject($subject)
            ->view('emails.otp', [
                'code' => $this->code,
                'purpose' => $this->purpose,
                'name' => $this->name,
                'expiresMinutes' => $this->expiresMinutes,
            ]);
    }
}
