<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Payment\OrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReconcileOrderCancellations extends Command
{
    protected $signature = 'orders:reconcile-cancellations';

    protected $description = 'Retry and reconcile order cancellations with Midtrans';

    public function handle(OrderService $orders): int
    {
        $confirmed = 0;

        Order::query()
            ->where(function ($query) {
                $query->where('status', Order::STATUS_CANCELLATION_PENDING)
                    ->orWhere(function ($legacy) {
                        $legacy->where('status', Order::STATUS_CANCELLED)
                            ->where('raw_response->transaction_status', 'pending');
                    });
            })
            ->chunkById(100, function ($candidates) use ($orders, &$confirmed) {
                foreach ($candidates as $candidate) {
                    try {
                        $fresh = $orders->reconcileCancellation($candidate);

                        if ($fresh->status === Order::STATUS_CANCELLED) {
                            $confirmed++;
                        }
                    } catch (Throwable $exception) {
                        Log::warning('Gagal merekonsiliasi pembatalan order', [
                            'order_id' => $candidate->id,
                            'message' => $exception->getMessage(),
                        ]);
                    }
                }
            });

        $this->info("{$confirmed} pembatalan dikonfirmasi Midtrans.");

        return self::SUCCESS;
    }
}
