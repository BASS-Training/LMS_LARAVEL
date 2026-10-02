<?php

namespace App\Services\Payment;

use App\Enums\RefundStatus;
use App\Models\Order;
use App\Models\Refund;
use App\Models\User;
use App\Notifications\RefundStatusNotification;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class RefundService
{
    public function __construct(private MidtransGateway $gateway) {}

    public function request(Order $order, User $user, string $reason): Refund
    {
        $refund = DB::transaction(function () use ($order, $user, $reason) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($locked->user_id !== $user->id) {
                throw new RuntimeException('Anda tidak dapat mengajukan refund untuk pesanan ini.');
            }

            if (! $locked->isPaid()) {
                throw new RuntimeException('Refund hanya dapat diajukan untuk pesanan yang sudah lunas.');
            }

            if ($locked->refund()->exists()) {
                throw new RuntimeException('Refund untuk pesanan ini sudah pernah diajukan.');
            }

            return $locked->refund()->create([
                'requested_by' => $user->id,
                'amount' => $locked->amount,
                'reason' => $reason,
                'status' => RefundStatus::Requested,
                'idempotency_key' => (string) Str::uuid(),
                'requested_at' => now(),
            ]);
        });

        $this->notifyCustomer($refund);

        return $refund;
    }

    public function createApprovedForRejectedOrder(Order $order, User $admin, string $reason): Refund
    {
        $refund = DB::transaction(function () use ($order, $admin, $reason) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (! $locked->isRejected() || ! $locked->isPaymentConfirmed()) {
                throw new RuntimeException('Pesanan ini tidak memenuhi syarat refund otomatis.');
            }

            return $locked->refund()->firstOrCreate([], [
                'requested_by' => $admin->id,
                'reviewed_by' => $admin->id,
                'amount' => $locked->amount,
                'reason' => $reason,
                'admin_note' => 'Refund otomatis karena pembayaran ditolak saat verifikasi.',
                'status' => RefundStatus::Approved,
                'idempotency_key' => (string) Str::uuid(),
                'requested_at' => now(),
                'reviewed_at' => now(),
            ]);
        });

        return $this->process($refund);
    }

    public function approve(Refund $refund, User $admin, ?string $note = null): Refund
    {
        $approved = DB::transaction(function () use ($refund, $admin, $note) {
            $locked = Refund::query()->lockForUpdate()->findOrFail($refund->id);

            if (! $locked->isRequested()) {
                throw new RuntimeException('Pengajuan refund ini sudah diproses.');
            }

            $locked->update([
                'reviewed_by' => $admin->id,
                'admin_note' => $note,
                'status' => RefundStatus::Approved,
                'reviewed_at' => now(),
            ]);

            return $locked;
        });

        return $this->process($approved);
    }

    public function reject(Refund $refund, User $admin, string $note): Refund
    {
        $rejected = DB::transaction(function () use ($refund, $admin, $note) {
            $locked = Refund::query()->lockForUpdate()->findOrFail($refund->id);

            if (! $locked->isRequested()) {
                throw new RuntimeException('Pengajuan refund ini sudah diproses.');
            }

            $locked->update([
                'reviewed_by' => $admin->id,
                'admin_note' => $note,
                'status' => RefundStatus::Rejected,
                'reviewed_at' => now(),
            ]);

            return $locked->refresh();
        });

        $this->notifyCustomer($rejected);

        return $rejected;
    }

    public function retry(Refund $refund, User $admin): Refund
    {
        $retry = DB::transaction(function () use ($refund, $admin) {
            $locked = Refund::query()->lockForUpdate()->findOrFail($refund->id);

            if (! $locked->isFailed() && ! $locked->isApproved()) {
                throw new RuntimeException('Refund ini tidak dapat diproses ulang.');
            }

            if ($locked->isFailed()) {
                $locked->update([
                    'reviewed_by' => $admin->id,
                    'status' => RefundStatus::Approved,
                    'failure_message' => null,
                    'failed_at' => null,
                ]);
            }

            return $locked;
        });

        return $this->process($retry);
    }

    public function process(Refund $refund): Refund
    {
        $processing = DB::transaction(function () use ($refund) {
            $locked = Refund::query()->with('order')->lockForUpdate()->findOrFail($refund->id);

            if ($locked->status !== RefundStatus::Approved) {
                return null;
            }

            $locked->update([
                'status' => RefundStatus::Processing,
                'attempts' => $locked->attempts + 1,
            ]);

            return $locked->refresh();
        });

        if (! $processing) {
            return $refund->refresh();
        }

        if (! $this->gateway->supportsRefund($processing->order)) {
            $processing->update([
                'status' => RefundStatus::ManualRequired,
                'failure_message' => 'Metode pembayaran ini tidak mendukung refund otomatis Midtrans.',
            ]);
            $processing = $processing->refresh();
            $this->notifyCustomer($processing);

            return $processing;
        }

        try {
            if (data_get($processing->order->raw_response, 'transaction_status') === 'capture') {
                if (! $this->gateway->cancelTransaction($processing->order->order_code)) {
                    throw new RuntimeException('Midtrans gagal membatalkan transaksi kartu yang belum settlement.');
                }

                return $this->complete($processing, [
                    'transaction_status' => 'cancel',
                    'refund_key' => $processing->idempotency_key,
                    'refund_amount' => $processing->amount,
                ]);
            }

            $response = $this->gateway->refundTransaction($processing->order, $processing);
        } catch (ConnectionException $exception) {
            $processing->update([
                'failure_message' => 'Respons Midtrans tidak diketahui karena koneksi terputus. Menunggu rekonsiliasi atau webhook.',
            ]);
            Log::warning('Status refund belum diketahui setelah gangguan koneksi', [
                'refund_id' => $processing->id,
                'message' => $exception->getMessage(),
            ]);

            return $processing->refresh();
        } catch (Throwable $exception) {
            $failed = DB::transaction(function () use ($processing, $exception) {
                $locked = Refund::query()->lockForUpdate()->findOrFail($processing->id);

                if ($locked->status === RefundStatus::Processing) {
                    $locked->update([
                        'status' => RefundStatus::Failed,
                        'failure_message' => mb_substr($exception->getMessage(), 0, 1000),
                        'failed_at' => now(),
                    ]);
                }

                return $locked->refresh();
            });

            Log::error('Refund gagal diproses', [
                'refund_id' => $failed->id,
                'order_id' => $failed->order_id,
                'message' => $exception->getMessage(),
            ]);
            $this->notifyCustomer($failed);

            return $failed;
        }

        $processing->update([
            'provider_refund_id' => $response['refund_chargeback_id'] ?? null,
            'raw_response' => $response,
            'failure_message' => null,
        ]);
        $processing = $processing->refresh();
        $this->notifyCustomer($processing);

        return $processing;
    }

    /** @param array<string, mixed> $payload */
    public function applyProviderNotification(Order $order, array $payload): Refund
    {
        $refund = $order->refund;

        if (! $refund || $refund->status !== RefundStatus::Processing) {
            throw new RuntimeException('Tidak ada refund aktif untuk notifikasi ini.');
        }

        if (($payload['transaction_status'] ?? null) !== 'refund'
            || (int) round((float) ($payload['gross_amount'] ?? 0)) !== $order->amount) {
            throw new RuntimeException('Notifikasi refund tidak cocok dengan pesanan.');
        }

        $providerRefund = collect($payload['refunds'] ?? [])->first(
            fn ($item) => ($item['refund_key'] ?? null) === $refund->idempotency_key
        );

        if (! $providerRefund
            || (int) round((float) ($providerRefund['refund_amount'] ?? 0)) !== $refund->amount) {
            throw new RuntimeException('Detail refund Midtrans tidak cocok.');
        }

        if (empty($providerRefund['bank_confirmed_at'])) {
            $refund->update([
                'provider_refund_id' => $providerRefund['refund_chargeback_id'] ?? $refund->provider_refund_id,
                'raw_response' => $payload,
            ]);

            return $refund->refresh();
        }

        return $this->complete($refund, $payload, $providerRefund['refund_chargeback_id'] ?? null);
    }

    public function completeManually(Refund $refund, User $admin, string $reference, ?string $note = null): Refund
    {
        if (! $refund->requiresManualProcessing()) {
            throw new RuntimeException('Refund ini tidak menunggu penyelesaian manual.');
        }

        $refund->update([
            'reviewed_by' => $admin->id,
            'admin_note' => $note ?: $refund->admin_note,
        ]);

        return $this->complete($refund, [
            'manual' => true,
            'reference' => $reference,
            'amount' => $refund->amount,
        ], $reference);
    }

    public function reconcile(Refund $refund): Refund
    {
        $refund = $refund->fresh(['order']);

        if ($refund->status !== RefundStatus::Processing) {
            throw new RuntimeException('Hanya refund yang sedang diproses yang dapat direkonsiliasi.');
        }

        $payload = $this->gateway->fetchStatus($refund->order->order_code);

        if (! $payload) {
            throw new RuntimeException('Status refund belum dapat diambil dari Midtrans.');
        }

        if (($payload['transaction_status'] ?? null) !== 'refund') {
            $refund->update(['raw_response' => $payload]);

            return $refund->refresh();
        }

        return $this->applyProviderNotification($refund->order, $payload);
    }

    /** @param array<string, mixed> $response */
    private function complete(Refund $refund, array $response, string|int|null $providerId = null): Refund
    {
        $completed = DB::transaction(function () use ($refund, $response, $providerId) {
            $locked = Refund::query()->with('order')->lockForUpdate()->findOrFail($refund->id);

            if (! in_array($locked->status, [RefundStatus::Processing, RefundStatus::ManualRequired], true)) {
                return $locked;
            }

            $locked->update([
                'status' => RefundStatus::Refunded,
                'provider_refund_id' => $providerId ?? $locked->provider_refund_id,
                'raw_response' => $response,
                'failure_message' => null,
                'failed_at' => null,
                'processed_at' => now(),
            ]);

            $locked->order()->update(['status' => Order::STATUS_REFUNDED]);

            $enrollment = DB::table('course_user')
                ->where('course_id', $locked->order->course_id)
                ->where('user_id', $locked->order->user_id)
                ->where('order_id', $locked->order_id)
                ->first();

            if ($enrollment && $enrollment->has_independent_access) {
                DB::table('course_user')->where('id', $enrollment->id)->update(['order_id' => null]);
            } elseif ($enrollment) {
                DB::table('course_user')->where('id', $enrollment->id)->delete();
            }

            return $locked->refresh();
        });

        Log::info('Refund berhasil dikonfirmasi', [
            'refund_id' => $completed->id,
            'order_id' => $completed->order_id,
            'amount' => $completed->amount,
        ]);
        $this->notifyCustomer($completed);

        return $completed;
    }

    private function notifyCustomer(Refund $refund): void
    {
        $refund->loadMissing('order.user');
        $refund->order->user->notify(new RefundStatusNotification($refund));
    }
}
