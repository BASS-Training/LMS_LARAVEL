<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const PAID = 'paid';
    public const AWAITING_VERIFICATION = 'awaiting_verification';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';

    public readonly int $orderId;

    public readonly string $orderCode;

    public readonly string $productTitle;

    public readonly int $amount;

    public readonly ?string $reason;

    public function __construct(Order $order, public readonly string $event)
    {
        $this->orderId = $order->id;
        $this->orderCode = $order->order_code;
        $this->productTitle = $order->order_title;
        $this->amount = $order->amount;
        $this->reason = $event === self::REJECTED ? $order->rejection_reason : null;

        // A queue worker must only see a committed order transition.
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = match ($this->event) {
            self::PAID => 'Pembayaran Lunas - BASS Academy',
            self::AWAITING_VERIFICATION => 'Pembayaran Diterima, Menunggu Verifikasi - BASS Academy',
            self::APPROVED => 'Pembayaran Disetujui - BASS Academy',
            self::REJECTED => 'Pembayaran Ditolak - BASS Academy',
        };

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.payment-status', [
                'name' => $notifiable->name,
                'event' => $this->event,
                'orderCode' => $this->orderCode,
                'productTitle' => $this->productTitle,
                'amount' => 'Rp '.number_format($this->amount, 0, ',', '.'),
                'reason' => $this->reason,
                'orderUrl' => route('checkout.finish', $this->orderId),
                'invoiceUrl' => $this->event === self::REJECTED
                    ? null
                    : route('checkout.invoice', $this->orderId),
            ]);
    }
}
