<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Payment\OrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExpirePendingOrders extends Command
{
    protected $signature = 'orders:expire-pending';

    protected $description = 'Reconcile and expire unpaid orders whose payment window has ended';

    public function handle(OrderService $orders): int
    {
        $expired = 0;

        Order::query()
            ->where('status', Order::STATUS_PENDING)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->chunkById(100, function ($candidates) use ($orders, &$expired) {
                foreach ($candidates as $candidate) {
                    try {
                        $fresh = $orders->refreshFromGateway($candidate);

                        if (! $fresh->isPending()) {
                            continue;
                        }

                        if ($orders->expirePending($fresh)) {
                            $expired++;
                        }
                    } catch (Throwable $exception) {
                        Log::warning('Gagal merekonsiliasi order kedaluwarsa', [
                            'order_id' => $candidate->id,
                            'message' => $exception->getMessage(),
                        ]);
                    }
                }
            });

        $this->info("{$expired} order diubah menjadi kedaluwarsa.");

        return self::SUCCESS;
    }
}
