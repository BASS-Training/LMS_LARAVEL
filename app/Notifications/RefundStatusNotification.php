<?php

namespace App\Notifications;

use App\Models\Refund;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RefundStatusNotification extends Notification
{
    use Queueable;

    public function __construct(public Refund $refund) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $this->refund->loadMissing('order.course');

        return [
            'type' => 'refund_status',
            'refund_id' => $this->refund->id,
            'order_id' => $this->refund->order_id,
            'order_code' => $this->refund->order->order_code,
            'course_title' => $this->refund->order->course->title,
            'amount' => $this->refund->amount,
            'status' => $this->refund->status->value,
            'status_label' => $this->refund->status_label,
            'updated_at' => now()->toISOString(),
        ];
    }
}
