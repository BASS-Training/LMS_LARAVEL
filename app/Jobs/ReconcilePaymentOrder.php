<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\Payment\OrderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ReconcilePaymentOrder implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public int $orderId)
    {
        $this->onConnection('payment_database')->onQueue(config('payment_queue.queue'));
    }

    public function handle(OrderService $orders): void
    {
        $order = Order::find($this->orderId);
        if ($order && $order->isPending() && $order->snap_redirect_url) {
            $orders->refreshFromGateway($order);
        }
    }
}
