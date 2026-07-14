<?php

namespace App\Services\Payment;

use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Aturan bisnis pembelian kursus.
 *
 * Prinsip yang dipegang di sini:
 *  1. Harga SELALU diambil dari database, tidak pernah dari request.
 *  2. Enrollment hanya terjadi setelah Midtrans menyatakan lunas — tidak
 *     pernah karena browser mendarat di halaman "sukses".
 *  3. Pemenuhan pesanan idempotent — Midtrans bisa mengirim notifikasi yang
 *     sama berkali-kali, dan pengguna bisa me-refresh halaman selesai.
 */
class OrderService
{
    public function __construct(private MidtransGateway $gateway) {}

    /**
     * Ambil pesanan yang masih bisa dibayar, atau buat yang baru lengkap
     * dengan link pembayaran Snap.
     */
    public function checkout(Course $course, User $user): Order
    {
        if (! $this->gateway->isConfigured()) {
            throw new RuntimeException('Pembayaran belum dikonfigurasi. Hubungi admin.');
        }

        if (! $course->isInCatalog() || $course->isFree()) {
            throw new RuntimeException('Kursus ini tidak dijual.');
        }

        if ($course->isEnrolledBy($user)) {
            throw new RuntimeException('Anda sudah terdaftar di kursus ini.');
        }

        // Jangan bikin pesanan baru kalau yang lama masih hidup — biar tidak
        // menumpuk order pending dan pengguna bisa lanjut bayar.
        $existing = Order::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if ($existing && $existing->isPayable() && $existing->amount === (int) $course->price) {
            return $existing;
        }

        return DB::transaction(function () use ($course, $user) {
            $order = Order::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'order_code' => $this->generateOrderCode(),
                'amount' => (int) $course->price, // snapshot harga dari DB
                'status' => 'pending',
                'expires_at' => now()->addHours((int) config('midtrans.expiry_hours', 24)),
            ]);

            $snap = $this->gateway->createSnapTransaction($order);

            $order->update([
                'snap_token' => $snap['token'],
                'snap_redirect_url' => $snap['redirect_url'],
            ]);

            return $order;
        });
    }

    /**
     * Terapkan status dari Midtrans ke pesanan. Sumbernya boleh webhook
     * maupun hasil query status — keduanya berasal dari Midtrans, bukan dari
     * browser pengguna.
     *
     * @param  array<string, mixed>  $payload
     */
    public function applyPaymentStatus(Order $order, array $payload): Order
    {
        $status = $this->mapStatus(
            $payload['transaction_status'] ?? '',
            $payload['fraud_status'] ?? null
        );

        // Sudah lunas → jangan proses ulang, jangan pernah turunkan statusnya.
        if ($order->isPaid()) {
            return $order;
        }

        $order->fill([
            'payment_type' => $payload['payment_type'] ?? $order->payment_type,
            'transaction_id' => $payload['transaction_id'] ?? $order->transaction_id,
            'raw_response' => $payload,
        ]);

        if ($status === 'paid') {
            $this->fulfill($order, $payload);

            return $order->refresh();
        }

        $order->status = $status;
        $order->save();

        return $order;
    }

    /**
     * Tanya Midtrans status terkini, lalu terapkan. Dipakai halaman "selesai"
     * agar pengguna tidak menunggu webhook (yang tidak sampai di localhost).
     */
    public function refreshFromGateway(Order $order): Order
    {
        if ($order->isPaid() || ! $this->gateway->isConfigured()) {
            return $order;
        }

        $payload = $this->gateway->fetchStatus($order->order_code);

        if (! $payload) {
            return $order;
        }

        return $this->applyPaymentStatus($order, $payload);
    }

    /**
     * Tandai lunas + daftarkan peserta ke kursus.
     *
     * Enroll di level course (tanpa CourseClass) — sama seperti jalur kode
     * enrollment yang tidak terikat kelas. syncWithoutDetaching membuatnya
     * aman dipanggil berkali-kali.
     *
     * @param  array<string, mixed>  $payload
     */
    private function fulfill(Order $order, array $payload): void
    {
        DB::transaction(function () use ($order, $payload) {
            // Kunci barisnya: dua notifikasi yang datang bersamaan tidak boleh
            // sama-sama lolos pengecekan "belum lunas".
            $locked = Order::whereKey($order->id)->lockForUpdate()->first();

            if (! $locked || $locked->status === 'paid') {
                return;
            }

            $locked->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_type' => $payload['payment_type'] ?? $locked->payment_type,
                'transaction_id' => $payload['transaction_id'] ?? $locked->transaction_id,
                'raw_response' => $payload,
            ]);

            $locked->course->enrolledUsers()->syncWithoutDetaching([$locked->user_id]);

            Log::info('Pesanan lunas & peserta di-enroll', [
                'order_code' => $locked->order_code,
                'user_id' => $locked->user_id,
                'course_id' => $locked->course_id,
            ]);
        });
    }

    /**
     * Terjemahkan transaction_status Midtrans ke status internal kita.
     */
    private function mapStatus(string $transactionStatus, ?string $fraudStatus): string
    {
        return match ($transactionStatus) {
            'settlement' => 'paid',
            // 'capture' hanya lunas kalau lolos pemeriksaan fraud.
            'capture' => $fraudStatus === 'accept' ? 'paid' : 'pending',
            'pending' => 'pending',
            'deny', 'failure' => 'failed',
            'cancel' => 'cancelled',
            'expire' => 'expired',
            default => 'pending',
        };
    }

    private function generateOrderCode(): string
    {
        // Harus unik selamanya di sisi Midtrans, termasuk lintas percobaan bayar.
        do {
            $code = 'BASS-' . now()->format('ymd') . '-' . strtoupper(Str::random(6));
        } while (Order::where('order_code', $code)->exists());

        return $code;
    }
}
