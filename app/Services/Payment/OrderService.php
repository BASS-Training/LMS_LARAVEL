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
    public function __construct(
        private MidtransGateway $gateway,
        private ServiceFee $fee,
    ) {}

    /**
     * Ambil pesanan yang masih bisa dibayar, atau buat yang baru lengkap
     * dengan link pembayaran Snap.
     *
     * @param  string|null  $methodKey  Metode yang dipilih pembeli di awal
     *   (mis. 'qris', 'bank_transfer'). Menentukan biaya layanan yang dipakai
     *   dan mengunci Snap ke metode itu. null → tarif gabungan + semua metode.
     */
    public function checkout(Course $course, User $user, ?string $methodKey = null): Order
    {
        if (! $this->gateway->isConfigured()) {
            throw new RuntimeException('Pembayaran belum dikonfigurasi. Hubungi admin.');
        }

        if (! $course->isInCatalog() || $course->isFree()) {
            throw new RuntimeException('Kursus ini tidak dijual.');
        }

        // Pengelola (super-admin / instruktur course ini) sudah punya akses —
        // jangan biarkan membuat order (mencegah salah beli, apalagi di produksi).
        if ($course->isManagedBy($user)) {
            throw new RuntimeException('Anda pengelola kursus ini, jadi tidak perlu membelinya.');
        }

        if ($course->isEnrolledBy($user)) {
            throw new RuntimeException('Anda sudah terdaftar di kursus ini.');
        }

        // Wajib pilih metode jika fitur per-metode aktif — supaya biaya yang
        // ditagih benar-benar sesuai metode & Snap bisa dikunci ke metode itu.
        if ($this->fee->methodsEnabled() && ! $this->fee->channelsFor($methodKey)) {
            throw new RuntimeException('Silakan pilih metode pembayaran terlebih dahulu.');
        }

        // Rincian harga: pembeli menanggung biaya layanan gateway sesuai metode.
        // total = harga kursus + biaya layanan (di-snapshot ke order).
        $breakdown = $this->fee->forMethod((int) $course->price, $methodKey);

        // Jangan bikin pesanan baru kalau yang lama masih hidup DENGAN metode &
        // tarif yang sama — biar tidak menumpuk order pending dan pengguna bisa
        // lanjut bayar. Kalau metode/tarif berbeda, buat order baru supaya
        // rincian & popup Snap konsisten dengan pilihan sekarang.
        $existing = Order::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if ($existing
            && $existing->isPayable()
            && $existing->payment_method_key === $methodKey
            && (int) $existing->base_amount === $breakdown['base']
            && (int) $existing->amount === $breakdown['total']) {
            return $existing;
        }

        return DB::transaction(function () use ($course, $user, $breakdown, $methodKey) {
            $order = Order::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'order_code' => $this->generateOrderCode(),
                'base_amount' => $breakdown['base'], // harga kursus (pendapatan penjual)
                'fee_amount' => $breakdown['fee'],   // biaya layanan yang dibebankan ke pembeli
                'amount' => $breakdown['total'],     // TOTAL yang ditagih ke Midtrans
                'payment_method_key' => $methodKey,  // metode pilihan (dasar biaya + kunci Snap)
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
     * Batalkan pesanan pending supaya pengguna bisa mulai lagi dengan metode
     * pembayaran berbeda. Transaksi lama juga dimatikan di sisi Midtrans agar
     * tidak ada dua transaksi hidup untuk kursus yang sama.
     *
     * Aman: pesanan yang SUDAH lunas tidak pernah dibatalkan.
     */
    public function abandon(Order $order): void
    {
        // Jangan pernah membatalkan pesanan yang uangnya sudah dikonfirmasi
        // (lunas ATAU menunggu verifikasi) — dananya sudah masuk.
        if ($order->isPaymentConfirmed()) {
            return;
        }

        if ($this->gateway->isConfigured()) {
            $this->gateway->cancelTransaction($order->order_code);
        }

        if ($order->status === 'pending') {
            $order->update(['status' => 'cancelled']);
        }
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

        // Uang sudah dikonfirmasi (lunas / menunggu verifikasi) atau sudah
        // ditolak → jangan proses ulang & jangan pernah turunkan statusnya.
        if ($order->isPaymentConfirmed() || $order->isRejected()) {
            return $order;
        }

        // Uang masuk → konfirmasi + tentukan akses otomatis vs verifikasi manual.
        if ($status === 'paid') {
            return $this->confirmPayment($order, $payload);
        }

        // Belum lunas (pending/failed/cancelled/expired) — catat status apa adanya.
        $order->fill([
            'payment_type' => $payload['payment_type'] ?? $order->payment_type,
            'transaction_id' => $payload['transaction_id'] ?? $order->transaction_id,
            'raw_response' => $payload,
            'status' => $status,
        ]);
        $order->save();

        return $order;
    }

    /**
     * Tanya Midtrans status terkini, lalu terapkan. Dipakai halaman "selesai"
     * agar pengguna tidak menunggu webhook (yang tidak sampai di localhost).
     */
    public function refreshFromGateway(Order $order): Order
    {
        // Uang sudah dikonfirmasi (paid / awaiting_verification / rejected) →
        // tak perlu tanya ulang.
        if ($order->isPaymentConfirmed() || ! $this->gateway->isConfigured()) {
            return $order;
        }

        $payload = $this->gateway->fetchStatus($order->order_code);

        if (! $payload) {
            return $order;
        }

        return $this->applyPaymentStatus($order, $payload);
    }

    /**
     * Midtrans memastikan UANG masuk. Ini memisahkan "uang diterima" dari
     * "akses diberikan":
     *  - course biasa  → akses OTOMATIS (langsung enroll + lunas).
     *  - course dgn requires_payment_verification → status awaiting_verification
     *    (uang aman, tapi akses ditahan sampai super-admin menyetujui).
     *
     * Idempotent + lockForUpdate: notifikasi ganda dari Midtrans tidak boleh
     * dobel-proses, dan nomor invoice hanya dibuat sekali.
     *
     * @param  array<string, mixed>  $payload
     */
    private function confirmPayment(Order $order, array $payload): Order
    {
        return DB::transaction(function () use ($order, $payload) {
            $locked = Order::whereKey($order->id)->with('course')->lockForUpdate()->first();

            if (! $locked || $locked->isPaymentConfirmed()) {
                return $locked ?? $order;
            }

            $locked->fill([
                'payment_type' => $payload['payment_type'] ?? $locked->payment_type,
                'transaction_id' => $payload['transaction_id'] ?? $locked->transaction_id,
                'raw_response' => $payload,
                'payment_confirmed_at' => now(),
                'invoice_number' => 'INV/' . now()->format('ymd') . '/'
                    . str_pad((string) $locked->id, 4, '0', STR_PAD_LEFT),
            ]);

            if ($locked->course->requiresPaymentVerification()) {
                $locked->status = 'awaiting_verification';
                $locked->save();

                Log::info('Pembayaran dikonfirmasi — menunggu verifikasi manual', [
                    'order_code' => $locked->order_code,
                    'user_id' => $locked->user_id,
                    'course_id' => $locked->course_id,
                ]);
            } else {
                $locked->status = 'paid';
                $locked->paid_at = now();
                $locked->save();

                $locked->course->enrolledUsers()->syncWithoutDetaching([$locked->user_id]);

                Log::info('Pesanan lunas & peserta di-enroll (otomatis)', [
                    'order_code' => $locked->order_code,
                    'user_id' => $locked->user_id,
                    'course_id' => $locked->course_id,
                ]);
            }

            return $locked->refresh();
        });
    }

    /**
     * Super-admin MENYETUJUI pembayaran yang sedang ditinjau → buka akses.
     * Hanya boleh dari status awaiting_verification; idempotent.
     */
    public function approve(Order $order, User $admin): Order
    {
        return DB::transaction(function () use ($order, $admin) {
            $locked = Order::whereKey($order->id)->with('course')->lockForUpdate()->first();

            if (! $locked || ! $locked->isAwaitingVerification()) {
                return $locked ?? $order;
            }

            $locked->update([
                'status' => 'paid',
                'paid_at' => now(),
                'verified_by' => $admin->id,
                'verified_at' => now(),
            ]);

            $locked->course->enrolledUsers()->syncWithoutDetaching([$locked->user_id]);

            Log::info('Pembayaran diverifikasi & peserta di-enroll', [
                'order_code' => $locked->order_code,
                'user_id' => $locked->user_id,
                'course_id' => $locked->course_id,
                'verified_by' => $admin->id,
            ]);

            return $locked->refresh();
        });
    }

    /**
     * Super-admin MENOLAK pembayaran setelah ditinjau (mis. dana tak cocok saat
     * rekonsiliasi bank). Akses tidak dibuka. Refund diproses manual di Midtrans.
     * Hanya boleh dari status awaiting_verification; idempotent.
     */
    public function reject(Order $order, User $admin, string $reason): Order
    {
        return DB::transaction(function () use ($order, $admin, $reason) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->first();

            if (! $locked || ! $locked->isAwaitingVerification()) {
                return $locked ?? $order;
            }

            $locked->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'verified_by' => $admin->id,
                'verified_at' => now(),
            ]);

            Log::warning('Pembayaran ditolak setelah ditinjau', [
                'order_code' => $locked->order_code,
                'user_id' => $locked->user_id,
                'course_id' => $locked->course_id,
                'verified_by' => $admin->id,
            ]);

            return $locked->refresh();
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
