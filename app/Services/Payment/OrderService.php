<?php

namespace App\Services\Payment;

use App\Jobs\PrepareSnapTransaction;
use App\Enums\RefundStatus;
use App\Models\Bundle;
use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use App\Notifications\PaymentStatusNotification;
use App\Services\FeatureAvailability;
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
        private CouponService $coupons,
        private FeatureAvailability $features,
    ) {}

    /**
     * Ambil pesanan yang masih bisa dibayar, atau buat yang baru lengkap
     * dengan link pembayaran Snap.
     *
     * @param  string|null  $methodKey  Metode yang dipilih pembeli di awal
     *                                  (mis. 'qris', 'bank_transfer'). Menentukan biaya layanan yang dipakai
     *                                  dan mengunci Snap ke metode itu. null → tarif gabungan + semua metode.
     */
    public function checkout(Course $course, User $user, ?string $methodKey = null, ?string $couponCode = null): Order
    {
        if (config('payment_queue.enabled')) {
            return DB::transaction(function () use ($course, $user, $methodKey, $couponCode) {
                User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

                return $this->checkoutCourseLocked($course, $user, $methodKey, $couponCode);
            });
        }

        return $this->checkoutCourseLocked($course, $user, $methodKey, $couponCode);
    }

    private function checkoutCourseLocked(Course $course, User $user, ?string $methodKey, ?string $couponCode): Order
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

        $confirmedOrder = Order::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->whereIn('status', [Order::STATUS_AWAITING_VERIFICATION, Order::STATUS_REJECTED])
            ->latest()
            ->first();

        if ($confirmedOrder) {
            throw new RuntimeException('Pembayaran sebelumnya masih dalam proses verifikasi atau refund.');
        }

        if (Order::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('status', Order::STATUS_CANCELLATION_PENDING)
            ->exists()) {
            throw new RuntimeException('Pembatalan tagihan sebelumnya masih diproses oleh penyedia pembayaran.');
        }

        // Wajib pilih metode jika fitur per-metode aktif — supaya biaya yang
        // ditagih benar-benar sesuai metode & Snap bisa dikunci ke metode itu.
        if ($this->fee->methodsEnabled() && ! $this->fee->channelsFor($methodKey)) {
            throw new RuntimeException('Silakan pilih metode pembayaran terlebih dahulu.');
        }

        // Jangan bikin pesanan baru kalau yang lama masih hidup DENGAN metode &
        // tarif yang sama — biar tidak menumpuk order pending dan pengguna bisa
        // lanjut bayar. Kalau metode/tarif berbeda, buat order baru supaya
        // rincian & popup Snap konsisten dengan pilihan sekarang.
        $existing = Order::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('status', Order::STATUS_PENDING)
            ->latest()
            ->first();

        $normalizedCoupon = $this->coupons->normalize($couponCode);
        $quote = $normalizedCoupon
            ? $this->coupons->quote($normalizedCoupon, (int) $course->price, $course, $user, $existing)
            : null;
        $discount = $quote['discount'] ?? 0;
        $breakdown = $this->fee->forMethod((int) $course->price - $discount, $methodKey);

        if ($existing
            && ($existing->isPayable() || (config('payment_queue.enabled') && in_array($existing->snap_status, ['queued', 'processing', 'needs_review'], true)))
            && $existing->payment_method_key === $methodKey
            && $existing->coupon_code === $normalizedCoupon
            && (int) $existing->discount_amount === $discount
            && (int) $existing->base_amount === $breakdown['base']
            && (int) $existing->amount === $breakdown['total']) {
            return $existing;
        }

        if ($existing) {
            if (config('payment_queue.enabled') && in_array($existing->snap_status, ['queued', 'processing', 'needs_review'], true)) {
                throw new RuntimeException('Tagihan sebelumnya masih disiapkan atau perlu diperiksa. Buka halaman pesanan untuk melihat statusnya.');
            }
            $cancelled = $this->cancelPending($existing, $user, 'Diganti dengan metode pembayaran atau harga terbaru.');

            if ($cancelled->isCancellationPending()) {
                throw new RuntimeException('Pembatalan tagihan sebelumnya masih diproses oleh penyedia pembayaran.');
            }
        }

        return DB::transaction(function () use ($course, $user, $methodKey, $normalizedCoupon) {
            $quote = $normalizedCoupon
                ? $this->coupons->quoteForReservation($normalizedCoupon, (int) $course->price, $course, $user)
                : null;
            $discount = $quote['discount'] ?? 0;
            $breakdown = $this->fee->forMethod((int) $course->price - $discount, $methodKey);

            $order = Order::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'product_title' => $course->title,
                'requires_payment_verification' => $course->requiresPaymentVerification(),
                'order_code' => $this->generateOrderCode(),
                'base_amount' => $breakdown['base'], // harga kursus (pendapatan penjual)
                'fee_amount' => $breakdown['fee'],   // biaya layanan yang dibebankan ke pembeli
                'coupon_code' => $quote['code'] ?? null,
                'discount_amount' => $discount,
                'amount' => $breakdown['total'],     // TOTAL yang ditagih ke Midtrans
                'payment_method_key' => $methodKey,  // metode pilihan (dasar biaya + kunci Snap)
                'status' => Order::STATUS_PENDING,
                'expires_at' => now()->addHours((int) config('midtrans.expiry_hours', 24)),
            ]);

            $order->items()->create([
                'course_id' => $course->id,
                'course_title' => $course->title,
                'sort_order' => 0,
            ]);

            if ($quote) {
                $this->coupons->createRedemption($quote, $order, $user);
            }

            if (config('payment_queue.enabled')) {
                $order->update(['snap_status' => 'queued']);
                PrepareSnapTransaction::dispatch($order->id);
            } else {
                $snap = $this->gateway->createSnapTransaction($order);
                $order->update([
                    'snap_token' => $snap['token'],
                    'snap_redirect_url' => $snap['redirect_url'],
                    'snap_status' => 'ready',
                ]);
            }

            return $order;
        });
    }

    /**
     * @return array{bundle_discount:int, payable_base:int, owned_ids:array<int>, all_owned:bool}
     */
    public function bundlePricing(Bundle $bundle, User $user): array
    {
        $bundle->loadMissing('courses');
        $ownedIds = $bundle->courses
            ->filter(fn (Course $course) => $course->isEnrolledBy($user) || $course->isManagedBy($user))
            ->pluck('id')
            ->all();
        $ownedValue = (int) $bundle->courses->whereIn('id', $ownedIds)->sum('price');
        $bundleDiscount = min((int) $bundle->price, $ownedValue);

        return [
            'bundle_discount' => $bundleDiscount,
            'payable_base' => max(0, (int) $bundle->price - $bundleDiscount),
            'owned_ids' => $ownedIds,
            'all_owned' => count($ownedIds) === $bundle->courses->count(),
        ];
    }

    public function checkoutBundle(Bundle $bundle, User $user, ?string $methodKey = null, ?string $couponCode = null): Order
    {
        if (config('payment_queue.enabled')) {
            return DB::transaction(function () use ($bundle, $user, $methodKey, $couponCode) {
                User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

                return $this->checkoutBundleLocked($bundle, $user, $methodKey, $couponCode);
            });
        }

        return $this->checkoutBundleLocked($bundle, $user, $methodKey, $couponCode);
    }

    private function checkoutBundleLocked(Bundle $bundle, User $user, ?string $methodKey, ?string $couponCode): Order
    {
        if (! $this->features->bundlesEnabled()) {
            throw new RuntimeException('Pembelian bundle sedang dinonaktifkan.');
        }

        if (! $this->gateway->isConfigured()) {
            throw new RuntimeException('Pembayaran belum dikonfigurasi. Hubungi admin.');
        }

        $bundle->load('courses');
        if (! $bundle->isInCatalog()) {
            throw new RuntimeException('Paket kursus ini tidak dijual.');
        }

        $pricing = $this->bundlePricing($bundle, $user);
        if ($pricing['payable_base'] <= 0) {
            throw new RuntimeException($pricing['all_owned']
                ? 'Anda sudah memiliki seluruh isi paket kursus ini.'
                : 'Tidak ada nominal yang dapat ditagihkan setelah potongan kepemilikan kursus.');
        }

        if ($this->fee->methodsEnabled() && ! $this->fee->channelsFor($methodKey)) {
            throw new RuntimeException('Silakan pilih metode pembayaran terlebih dahulu.');
        }

        $blocked = Order::query()
            ->where('user_id', $user->id)
            ->where('bundle_id', $bundle->id)
            ->whereIn('status', [Order::STATUS_AWAITING_VERIFICATION, Order::STATUS_REJECTED, Order::STATUS_CANCELLATION_PENDING])
            ->exists();
        if ($blocked) {
            throw new RuntimeException('Pembayaran atau pembatalan paket sebelumnya masih diproses.');
        }

        $existing = Order::query()
            ->where('user_id', $user->id)
            ->where('bundle_id', $bundle->id)
            ->where('status', Order::STATUS_PENDING)
            ->latest()
            ->first();
        $normalizedCoupon = $this->coupons->normalize($couponCode);
        $quote = $normalizedCoupon
            ? $this->coupons->quoteForBundle($normalizedCoupon, $pricing['payable_base'], $bundle, $user, $existing)
            : null;
        $discount = $quote['discount'] ?? 0;
        $breakdown = $this->fee->forMethod($pricing['payable_base'] - $discount, $methodKey);

        if ($existing
            && ($existing->isPayable() || (config('payment_queue.enabled') && in_array($existing->snap_status, ['queued', 'processing', 'needs_review'], true)))
            && $existing->payment_method_key === $methodKey
            && $existing->coupon_code === $normalizedCoupon
            && (int) $existing->bundle_discount_amount === $pricing['bundle_discount']
            && (int) $existing->discount_amount === $discount
            && (int) $existing->base_amount === $breakdown['base']
            && (int) $existing->amount === $breakdown['total']) {
            return $existing;
        }

        if ($existing) {
            if (config('payment_queue.enabled') && in_array($existing->snap_status, ['queued', 'processing', 'needs_review'], true)) {
                throw new RuntimeException('Tagihan sebelumnya masih disiapkan atau perlu diperiksa. Buka halaman pesanan untuk melihat statusnya.');
            }
            $cancelled = $this->cancelPending($existing, $user, 'Diganti dengan metode pembayaran atau harga terbaru.');
            if ($cancelled->isCancellationPending()) {
                throw new RuntimeException('Pembatalan tagihan sebelumnya masih diproses oleh penyedia pembayaran.');
            }
        }

        return DB::transaction(function () use ($bundle, $user, $methodKey, $normalizedCoupon, $pricing) {
            $quote = $normalizedCoupon
                ? $this->coupons->quoteBundleForReservation($normalizedCoupon, $pricing['payable_base'], $bundle, $user)
                : null;
            $discount = $quote['discount'] ?? 0;
            $breakdown = $this->fee->forMethod($pricing['payable_base'] - $discount, $methodKey);

            $order = Order::create([
                'user_id' => $user->id,
                'course_id' => null,
                'bundle_id' => $bundle->id,
                'product_title' => $bundle->title,
                'requires_payment_verification' => $bundle->requires_payment_verification,
                'order_code' => $this->generateOrderCode(),
                'base_amount' => $breakdown['base'],
                'fee_amount' => $breakdown['fee'],
                'coupon_code' => $quote['code'] ?? null,
                'discount_amount' => $discount,
                'bundle_discount_amount' => $pricing['bundle_discount'],
                'amount' => $breakdown['total'],
                'payment_method_key' => $methodKey,
                'status' => Order::STATUS_PENDING,
                'expires_at' => now()->addHours((int) config('midtrans.expiry_hours', 24)),
            ]);

            foreach ($bundle->courses as $index => $course) {
                $order->items()->create([
                    'course_id' => $course->id,
                    'course_title' => $course->title,
                    'sort_order' => $index,
                ]);
            }

            if ($quote) {
                $this->coupons->createRedemption($quote, $order, $user);
            }

            if (config('payment_queue.enabled')) {
                $order->update(['snap_status' => 'queued']);
                PrepareSnapTransaction::dispatch($order->id);
            } else {
                $snap = $this->gateway->createSnapTransaction($order);
                $order->update([
                    'snap_token' => $snap['token'],
                    'snap_redirect_url' => $snap['redirect_url'],
                    'snap_status' => 'ready',
                ]);
            }

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
    public function abandon(Order $order, ?User $user = null): Order
    {
        return $this->cancelPending($order, $user, 'Mengganti metode pembayaran.');
    }

    public function cancelPending(Order $order, ?User $user = null, ?string $reason = null): Order
    {
        if (config('payment_queue.enabled') && in_array($order->snap_status, ['queued', 'processing', 'needs_review'], true)) {
            throw new RuntimeException('Tagihan sedang disiapkan atau perlu diperiksa sebelum dibatalkan.');
        }
        $fresh = $this->refreshFromGateway($order->fresh());

        if ($fresh->isPaymentConfirmed() || ! $fresh->isPending()) {
            throw new RuntimeException('Pesanan ini tidak dapat dibatalkan karena statusnya sudah berubah.');
        }

        $gatewayIdentifier = $fresh->transaction_id ?: $fresh->order_code;

        $gatewayCancelled = ! $this->gateway->isConfigured()
            || $this->gateway->cancelTransaction($gatewayIdentifier);

        if (! $gatewayCancelled) {
            $fresh = $this->refreshFromGateway($fresh->fresh());

            if ($fresh->isPaymentConfirmed()) {
                throw new RuntimeException('Pembayaran sudah diterima sehingga pesanan tidak dapat dibatalkan.');
            }

            // Cancel API dapat sempat merespons gagal walau pembatalan akhirnya
            // diterima. Hasil rekonsiliasi gateway adalah sumber kebenarannya.
            if (in_array($fresh->status, [Order::STATUS_CANCELLED, Order::STATUS_EXPIRED], true)) {
                return $this->markCancellationConfirmed($fresh, $user, $reason);
            }

            if (! $fresh->isPending()) {
                throw new RuntimeException('Pesanan ini tidak dapat dibatalkan karena statusnya sudah berubah.');
            }

            Log::warning('Pembatalan order menunggu konfirmasi Midtrans', [
                'order_code' => $fresh->order_code,
                'transaction_id' => $fresh->transaction_id,
            ]);

            return DB::transaction(function () use ($fresh, $user, $reason) {
                $locked = Order::query()->lockForUpdate()->findOrFail($fresh->id);

                if ($locked->isPaymentConfirmed() || ! $locked->isPending()) {
                    throw new RuntimeException('Pesanan ini tidak dapat dibatalkan karena statusnya sudah berubah.');
                }

                $locked->update([
                    'status' => Order::STATUS_CANCELLATION_PENDING,
                    'cancelled_by' => $user?->id,
                    'cancellation_reason' => $reason,
                ]);

                return $locked->refresh();
            });
        }

        return $this->markCancellationConfirmed($fresh, $user, $reason);
    }

    public function reconcileCancellation(Order $order): Order
    {
        if (! $this->gateway->isConfigured()) {
            return $order;
        }

        $payload = $this->gateway->fetchStatus($order->order_code);

        if (! $payload) {
            return $order;
        }

        $providerStatus = (string) ($payload['transaction_status'] ?? '');

        if (in_array($providerStatus, ['settlement', 'capture'], true)) {
            return $this->applyPaymentStatus($order, $payload);
        }

        if (in_array($providerStatus, ['cancel', 'expire'], true)) {
            $fresh = DB::transaction(function () use ($order, $payload) {
                $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
                $locked->update([
                    'payment_type' => $payload['payment_type'] ?? $locked->payment_type,
                    'transaction_id' => $payload['transaction_id'] ?? $locked->transaction_id,
                    'raw_response' => $payload,
                ]);

                return $locked->refresh();
            });

            return $this->markCancellationConfirmed($fresh);
        }

        $fresh = DB::transaction(function () use ($order, $payload) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            $locked->update([
                'status' => Order::STATUS_CANCELLATION_PENDING,
                'payment_type' => $payload['payment_type'] ?? $locked->payment_type,
                'transaction_id' => $payload['transaction_id'] ?? $locked->transaction_id,
                'raw_response' => $payload,
                'cancelled_at' => null,
            ]);

            return $locked->refresh();
        });

        $identifier = $fresh->transaction_id ?: $fresh->order_code;

        if ($this->gateway->cancelTransaction($identifier)) {
            return $this->markCancellationConfirmed($fresh);
        }

        return $fresh;
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
        if (isset($payload['gross_amount']) && (int) round((float) $payload['gross_amount']) !== $order->amount) {
            Log::warning('Nominal notifikasi Midtrans tidak cocok', [
                'order_code' => $order->order_code,
                'expected' => $order->amount,
                'received' => $payload['gross_amount'],
            ]);

            throw new RuntimeException('Nominal pembayaran dari Midtrans tidak cocok dengan pesanan.');
        }

        if (isset($payload['currency']) && strtoupper((string) $payload['currency']) !== 'IDR') {
            throw new RuntimeException('Mata uang pembayaran tidak valid.');
        }

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
        return DB::transaction(function () use ($order, $payload, $status) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($locked->isPaymentConfirmed() || $locked->isRejected()) {
                return $locked;
            }

            if (in_array($locked->status, [
                Order::STATUS_CANCELLED,
                Order::STATUS_EXPIRED,
                Order::STATUS_FAILED,
            ], true)) {
                return $locked;
            }

            if ($locked->isCancellationPending() && $status === Order::STATUS_PENDING) {
                $locked->update([
                    'payment_type' => $payload['payment_type'] ?? $locked->payment_type,
                    'transaction_id' => $payload['transaction_id'] ?? $locked->transaction_id,
                    'raw_response' => $payload,
                ]);

                return $locked->refresh();
            }

            $locked->update([
                'payment_type' => $payload['payment_type'] ?? $locked->payment_type,
                'transaction_id' => $payload['transaction_id'] ?? $locked->transaction_id,
                'raw_response' => $payload,
                'status' => $status,
            ]);

            if (in_array($status, [Order::STATUS_CANCELLED, Order::STATUS_EXPIRED, Order::STATUS_FAILED], true)) {
                $this->coupons->releaseForOrder($locked);
            }

            return $locked->refresh();
        });
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
            $locked = Order::whereKey($order->id)->with(['course', 'items.course'])->lockForUpdate()->first();

            if (! $locked || $locked->isPaymentConfirmed()) {
                return $locked ?? $order;
            }

            $locked->fill([
                'payment_type' => $payload['payment_type'] ?? $locked->payment_type,
                'transaction_id' => $payload['transaction_id'] ?? $locked->transaction_id,
                'raw_response' => $payload,
                'payment_confirmed_at' => now(),
                'invoice_number' => 'INV/'.now()->format('ymd').'/'
                    .str_pad((string) $locked->id, 4, '0', STR_PAD_LEFT),
            ]);

            $requiresVerification = $locked->requires_payment_verification
                || (! $locked->product_title && $locked->course?->requiresPaymentVerification());

            if ($requiresVerification) {
                $locked->status = Order::STATUS_AWAITING_VERIFICATION;
                $locked->save();

                $locked->user->notify(new PaymentStatusNotification($locked, PaymentStatusNotification::AWAITING_VERIFICATION));

                Log::info('Pembayaran dikonfirmasi — menunggu verifikasi manual', [
                    'order_code' => $locked->order_code,
                    'user_id' => $locked->user_id,
                    'course_id' => $locked->course_id,
                    'bundle_id' => $locked->bundle_id,
                ]);
            } else {
                $locked->status = Order::STATUS_PAID;
                $locked->paid_at = now();
                $locked->save();

                $this->grantAccess($locked);

                $locked->user->notify(new PaymentStatusNotification($locked, PaymentStatusNotification::PAID));

                Log::info('Pesanan lunas & peserta di-enroll (otomatis)', [
                    'order_code' => $locked->order_code,
                    'user_id' => $locked->user_id,
                    'course_id' => $locked->course_id,
                    'bundle_id' => $locked->bundle_id,
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
            $locked = Order::whereKey($order->id)->with(['course', 'items.course'])->lockForUpdate()->first();

            if (! $locked || ! $locked->isAwaitingVerification()) {
                return $locked ?? $order;
            }

            $locked->update([
                'status' => Order::STATUS_PAID,
                'paid_at' => now(),
                'verified_by' => $admin->id,
                'verified_at' => now(),
            ]);

            $this->grantAccess($locked);

            $locked->user->notify(new PaymentStatusNotification($locked, PaymentStatusNotification::APPROVED));

            Log::info('Pembayaran diverifikasi & peserta di-enroll', [
                'order_code' => $locked->order_code,
                'user_id' => $locked->user_id,
                'course_id' => $locked->course_id,
                'bundle_id' => $locked->bundle_id,
                'verified_by' => $admin->id,
            ]);

            return $locked->refresh();
        });
    }

    /**
     * Super-admin MENOLAK pembayaran setelah ditinjau (mis. dana tak cocok saat
     * rekonsiliasi bank). Akses tidak dibuka dan full refund otomatis dibuat.
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
                'status' => Order::STATUS_REJECTED,
                'rejection_reason' => $reason,
                'verified_by' => $admin->id,
                'verified_at' => now(),
            ]);

            $locked->refund()->firstOrCreate([], [
                'requested_by' => $admin->id,
                'reviewed_by' => $admin->id,
                'amount' => $locked->amount,
                'reason' => 'Refund otomatis: pembayaran ditolak saat verifikasi. '.$reason,
                'admin_note' => 'Refund otomatis karena pembayaran ditolak saat verifikasi.',
                'status' => RefundStatus::Approved,
                'idempotency_key' => (string) Str::uuid(),
                'requested_at' => now(),
                'reviewed_at' => now(),
            ]);

            $locked->user->notify(new PaymentStatusNotification($locked, PaymentStatusNotification::REJECTED));

            Log::warning('Pembayaran ditolak setelah ditinjau', [
                'order_code' => $locked->order_code,
                'user_id' => $locked->user_id,
                'course_id' => $locked->course_id,
                'verified_by' => $admin->id,
            ]);

            return $locked->refresh()->load('refund');
        });
    }

    public function expirePending(Order $order): bool
    {
        return DB::transaction(function () use ($order) {
            $locked = Order::query()->lockForUpdate()->find($order->id);

            if (! $locked?->isPending() || ! $locked->expires_at?->isPast()) {
                return false;
            }

            $locked->update(['status' => Order::STATUS_EXPIRED]);
            $this->coupons->releaseForOrder($locked);

            return true;
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

    private function markCancellationConfirmed(Order $order, ?User $user = null, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($order, $user, $reason) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($locked->isPaymentConfirmed()) {
                throw new RuntimeException('Pembayaran sudah diterima sehingga pesanan tidak dapat dibatalkan.');
            }

            $locked->update([
                'status' => Order::STATUS_CANCELLED,
                'cancelled_by' => $user?->id ?? $locked->cancelled_by,
                'cancelled_at' => $locked->cancelled_at ?: now(),
                'cancellation_reason' => $reason ?? $locked->cancellation_reason,
            ]);

            $this->coupons->releaseForOrder($locked);

            return $locked->refresh();
        });
    }

    private function generateOrderCode(): string
    {
        // Harus unik selamanya di sisi Midtrans, termasuk lintas percobaan bayar.
        do {
            $code = 'BASS-'.now()->format('ymd').'-'.strtoupper(Str::random(6));
        } while (Order::where('order_code', $code)->exists());

        return $code;
    }

    private function grantAccess(Order $order): void
    {
        if ($order->items->isEmpty() && $order->course) {
            $order->items()->create([
                'course_id' => $order->course_id,
                'course_title' => $order->course->title,
                'sort_order' => 0,
            ]);
            $order->load('items.course');
        }

        foreach ($order->items as $item) {
            $course = $item->course;
            if (! $course || $course->enrolledUsers()->whereKey($order->user_id)->exists()) {
                continue;
            }

            $course->enrolledUsers()->attach($order->user_id, [
                'order_id' => $order->id,
                'has_independent_access' => false,
            ]);
        }
    }
}
