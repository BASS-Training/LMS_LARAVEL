<?php

namespace App\Jobs;

use App\Models\PaymentWebhookReceipt;
use App\Services\Payment\OrderService;
use App\Services\Payment\RefundService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessPaymentWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 30;

    public array $backoff = [5, 15, 30, 60];

    public function __construct(public int $receiptId)
    {
        $this->onConnection('payment_database')->onQueue(config('payment_queue.queue'));
    }

    public function handle(OrderService $orders, RefundService $refunds): void
    {
        $receipt = PaymentWebhookReceipt::with('order')->find($this->receiptId);
        if (! $receipt || $receipt->processed_at) {
            return;
        }

        try {
            DB::transaction(function () use ($receipt, $orders, $refunds) {
                $locked = PaymentWebhookReceipt::query()->lockForUpdate()->findOrFail($receipt->id);
                if ($locked->processed_at) {
                    return;
                }

                $order = $locked->order;
                if (in_array($locked->payload['transaction_status'] ?? null, ['refund', 'partial_refund'], true)) {
                    $refunds->applyProviderNotification($order, $locked->payload);
                } else {
                    $orders->applyPaymentStatus($order, $locked->payload);
                }

                $locked->update(['processed_at' => now(), 'error' => null]);
            });
        } catch (Throwable $exception) {
            $receipt->update(['error' => $exception->getMessage()]);
            throw $exception;
        }
    }
}
