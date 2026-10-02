<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Refund;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Pembungkus tipis Midtrans Snap + Core API.
 *
 * Sengaja pakai HTTP client Laravel, bukan SDK — hanya butuh dua endpoint,
 * dan ini membuat gateway mudah ditukar tanpa menyeret dependency.
 *
 * Kelas ini HANYA bicara ke Midtrans. Semua keputusan bisnis (order lunas →
 * peserta di-enroll) ada di OrderService.
 */
class MidtransGateway
{
    public function __construct(
        private ServiceFee $fee,
    ) {}

    public function isConfigured(): bool
    {
        return ! empty(config('midtrans.server_key'));
    }

    /**
     * Buat transaksi Snap, kembalikan token + URL halaman pembayaran.
     *
     * @return array{token: string, redirect_url: string}
     */
    public function createSnapTransaction(Order $order): array
    {
        $order->loadMissing(['user', 'course']);

        $expiryHours = (int) config('midtrans.expiry_hours', 24);

        // Midtrans mewajibkan gross_amount == Σ(price × quantity) item_details.
        // Harga kursus & biaya layanan jadi baris terpisah agar transparan di
        // popup Snap sekaligus menjaga totalnya tetap cocok. Base dihitung dari
        // (amount − fee) supaya selalu pas walau kolom base_amount belum terisi.
        $fee = (int) $order->fee_amount;
        $base = (int) $order->amount - $fee;

        $items = [[
            'id' => (string) $order->course_id,
            'name' => mb_substr($order->course->title, 0, 50),
            'price' => $base,
            'quantity' => 1,
        ]];

        if ($fee > 0) {
            $items[] = [
                'id' => 'SERVICE-FEE',
                'name' => mb_substr((string) config('midtrans.fee.label', 'Biaya layanan'), 0, 50),
                'price' => $fee,
                'quantity' => 1,
            ];
        }

        $payload = [
            'transaction_details' => [
                'order_id' => $order->order_code,
                'gross_amount' => (int) $order->amount,
            ],
            'item_details' => $items,
            'customer_details' => [
                'first_name' => $order->user->name,
                'email' => $order->user->email,
            ],
            'expiry' => [
                'unit' => 'hour',
                'duration' => $expiryHours,
            ],
            'callbacks' => [
                'finish' => route('checkout.finish', $order),
            ],
        ];

        // Kunci popup Snap ke metode yang sudah dipilih pembeli. Biaya layanan
        // di order sudah dihitung untuk metode INI, jadi jangan biarkan pembeli
        // beralih ke metode lain di dalam Snap (bisa bikin biaya tak cocok).
        $channels = $this->fee->channelsFor($order->payment_method_key);

        if ($channels) {
            $payload['enabled_payments'] = $channels;
        }

        $response = $this->client()
            ->acceptJson()
            ->asJson()
            ->post($this->snapUrl(), $payload);

        if ($response->failed()) {
            Log::error('Midtrans Snap gagal', [
                'order_code' => $order->order_code,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Gagal membuat transaksi pembayaran. Silakan coba lagi.');
        }

        return [
            'token' => $response->json('token'),
            'redirect_url' => $response->json('redirect_url'),
        ];
    }

    /**
     * Tanya langsung ke Midtrans: status transaksi ini apa?
     *
     * Dipakai untuk rekonsiliasi di halaman "selesai" — penting saat
     * pengembangan lokal, karena webhook Midtrans tidak bisa menjangkau
     * localhost. Ini tetap aman: jawabannya datang dari Midtrans, bukan
     * dari browser pengguna.
     *
     * @return array<string, mixed>|null
     */
    public function fetchStatus(string $orderCode): ?array
    {
        $response = $this->client()
            ->acceptJson()
            ->get($this->statusUrl($orderCode));

        $payload = (array) $response->json();
        $hasTransaction = ! empty($payload['transaction_status'])
            && ($payload['order_id'] ?? null) === $orderCode;

        // Get Status memakai 200 untuk settlement, 201 untuk pending, dan 202
        // untuk beberapa status terminal. transaction_status + order_id adalah
        // indikator bahwa respons tersebut benar-benar data transaksi.
        if ($response->failed() || ! $hasTransaction) {
            Log::warning('Midtrans status gagal diambil', [
                'order_code' => $orderCode,
                'http_status' => $response->status(),
                'status_code' => $payload['status_code'] ?? null,
                'status_message' => $payload['status_message'] ?? null,
            ]);

            return null;
        }

        return $payload;
    }

    /**
     * Batalkan transaksi di sisi Midtrans (best-effort).
     *
     * Dipakai saat pengguna ingin ganti metode pembayaran: transaksi lama
     * harus benar-benar dimatikan supaya tidak ada dua transaksi hidup untuk
     * kursus yang sama. Midtrans hanya mengizinkan cancel untuk transaksi yang
     * belum settle — kalau gagal (misal sudah expire/settle), cukup diabaikan.
     */
    public function cancelTransaction(string $orderCode): bool
    {
        $response = $this->client()
            ->acceptJson()
            // Endpoint cancel tidak menerima payload. PendingRequest::post()
            // mengirim JSON `[]` saat data dikosongkan, jadi gunakan send().
            ->send('POST', $this->cancelUrl($orderCode));

        if ($response->failed() || (string) $response->json('status_code') !== '200') {
            Log::info('Midtrans cancel tidak berhasil (mungkin belum ada / sudah selesai)', [
                'order_code' => $orderCode,
                'http_status' => $response->status(),
                'status_code' => $response->json('status_code'),
                'status_message' => $response->json('status_message'),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Kembalikan seluruh nominal yang dibayar pembeli. Refund key tetap sama
     * pada retry agar permintaan yang meragukan tidak menghasilkan refund ganda.
     *
     * @return array<string, mixed>
     */
    public function refundTransaction(Order $order, Refund $refund): array
    {
        $response = $this->client()
            ->acceptJson()
            ->asJson()
            ->post($this->refundUrl($order->order_code), [
                'refund_key' => $refund->idempotency_key,
                'amount' => $refund->amount,
                'reason' => mb_substr($refund->reason, 0, 255),
            ]);

        $payload = (array) $response->json();
        $valid = (string) ($payload['status_code'] ?? '') === '200'
            && ($payload['refund_key'] ?? null) === $refund->idempotency_key
            && (int) round((float) ($payload['refund_amount'] ?? 0)) === $refund->amount
            && ($payload['transaction_status'] ?? null) === 'refund';

        if ($response->failed() || ! $valid) {
            Log::error('Refund Midtrans gagal', [
                'order_code' => $order->order_code,
                'refund_id' => $refund->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException(
                (string) ($response->json('status_message') ?: 'Respons refund Midtrans tidak valid.')
            );
        }

        return $payload;
    }

    public function supportsRefund(Order $order): bool
    {
        return in_array($order->payment_type, [
            'credit_card',
            'gopay',
            'shopeepay',
            'dana',
            'ovo',
            'qris',
            'kredivo',
            'akulaku',
        ], true);
    }

    /**
     * Pastikan notifikasi webhook benar-benar dari Midtrans.
     *
     * signature_key = sha512(order_id + status_code + gross_amount + server_key)
     *
     * @param  array<string, mixed>  $payload
     */
    public function verifySignature(array $payload): bool
    {
        $signature = $payload['signature_key'] ?? null;

        if (! $signature) {
            return false;
        }

        $expected = hash('sha512',
            ($payload['order_id'] ?? '')
            .($payload['status_code'] ?? '')
            .($payload['gross_amount'] ?? '')
            .config('midtrans.server_key')
        );

        return hash_equals($expected, $signature);
    }

    private function snapUrl(): string
    {
        return config('midtrans.is_production')
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    }

    private function statusUrl(string $orderCode): string
    {
        $base = config('midtrans.is_production')
            ? 'https://api.midtrans.com'
            : 'https://api.sandbox.midtrans.com';

        return $base.'/v2/'.urlencode($orderCode).'/status';
    }

    private function cancelUrl(string $orderCode): string
    {
        $base = config('midtrans.is_production')
            ? 'https://api.midtrans.com'
            : 'https://api.sandbox.midtrans.com';

        return $base.'/v2/'.urlencode($orderCode).'/cancel';
    }

    private function refundUrl(string $orderCode): string
    {
        $base = config('midtrans.is_production')
            ? 'https://api.midtrans.com'
            : 'https://api.sandbox.midtrans.com';

        return $base.'/v2/'.urlencode($orderCode).'/refund';
    }

    private function client()
    {
        return Http::withBasicAuth(config('midtrans.server_key'), '')
            ->connectTimeout(5)
            ->timeout(15);
    }
}
