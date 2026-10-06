<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\Payment\MidtransGateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class PrepareSnapTransaction implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public array $backoff = [10, 30];

    public function __construct(public int $orderId)
    {
        $this->onConnection('payment_database')->onQueue(config('payment_queue.queue'));
    }

    public function handle(MidtransGateway $gateway): void
    {
        $order = Order::find($this->orderId);
        if (! $order || ! $order->isPending() || $order->snap_redirect_url
            || $order->snap_status === 'needs_review') {
            return;
        }

        $order->update(['snap_status' => 'processing', 'snap_error' => null]);

        try {
            $snap = $gateway->createSnapTransaction($order);
            if (! is_string($snap['token'] ?? null) || ! is_string($snap['redirect_url'] ?? null)
                || $snap['token'] === '' || $snap['redirect_url'] === '') {
                throw new RuntimeException('Respons Snap tidak lengkap.');
            }

            Order::query()->whereKey($order->id)->where('status', Order::STATUS_PENDING)
                ->whereNull('snap_redirect_url')->update([
                    'snap_token' => $snap['token'],
                    'snap_redirect_url' => $snap['redirect_url'],
                    'snap_status' => 'ready',
                    'snap_error' => null,
                ]);
        } catch (Throwable $exception) {
            // A timeout can happen after Midtrans created the transaction. Never
            // issue a second order code; leave ambiguous attempts for review.
            try {
                $status = $gateway->fetchStatus($order->order_code);
            } catch (Throwable) {
                $status = null;
            }

            if ($status) {
                $order->update(['snap_status' => 'needs_review', 'snap_error' => 'Transaksi sudah tercatat di Midtrans tanpa tautan Snap.']);
                Log::warning('Snap membutuhkan rekonsiliasi manual', ['order_id' => $order->id]);

                return;
            }

            $order->update(['snap_status' => 'queued', 'snap_error' => $exception->getMessage()]);
            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        Order::query()->whereKey($this->orderId)->where('status', Order::STATUS_PENDING)
            ->whereNull('snap_redirect_url')->update([
                'snap_status' => 'needs_review',
                'snap_error' => $exception?->getMessage() ?? 'Pembuatan pembayaran gagal.',
            ]);
    }
}
