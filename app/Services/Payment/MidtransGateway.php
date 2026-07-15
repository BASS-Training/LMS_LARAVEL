<?php

namespace App\Services\Payment;

use App\Models\Order;
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

        $response = Http::withBasicAuth(config('midtrans.server_key'), '')
            ->acceptJson()
            ->asJson()
            ->post($this->snapUrl(), [
                'transaction_details' => [
                    'order_id' => $order->order_code,
                    'gross_amount' => (int) $order->amount,
                ],
                'item_details' => [[
                    'id' => (string) $order->course_id,
                    'name' => mb_substr($order->course->title, 0, 50),
                    'price' => (int) $order->amount,
                    'quantity' => 1,
                ]],
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
            ]);

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
        $response = Http::withBasicAuth(config('midtrans.server_key'), '')
            ->acceptJson()
            ->get($this->statusUrl($orderCode));

        if ($response->failed()) {
            Log::warning('Midtrans status gagal diambil', [
                'order_code' => $orderCode,
                'status' => $response->status(),
            ]);

            return null;
        }

        return $response->json();
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
        $response = Http::withBasicAuth(config('midtrans.server_key'), '')
            ->acceptJson()
            ->post($this->cancelUrl($orderCode));

        if ($response->failed()) {
            Log::info('Midtrans cancel tidak berhasil (mungkin belum ada / sudah selesai)', [
                'order_code' => $orderCode,
                'status' => $response->status(),
            ]);

            return false;
        }

        return true;
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
            . ($payload['status_code'] ?? '')
            . ($payload['gross_amount'] ?? '')
            . config('midtrans.server_key')
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

        return $base . '/v2/' . urlencode($orderCode) . '/status';
    }

    private function cancelUrl(string $orderCode): string
    {
        $base = config('midtrans.is_production')
            ? 'https://api.midtrans.com'
            : 'https://api.sandbox.midtrans.com';

        return $base . '/v2/' . urlencode($orderCode) . '/cancel';
    }
}
