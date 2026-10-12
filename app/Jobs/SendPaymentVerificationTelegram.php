<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SendPaymentVerificationTelegram implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public int $timeout = 15;

    public array $backoff = [10, 30, 60];

    public function __construct(public int $orderId) {}

    public function handle(): void
    {
        if (! config('services.telegram.payment_alerts_enabled')) {
            return;
        }

        $token = (string) config('services.telegram.bot_token');
        $chatId = (string) config('services.telegram.payment_chat_id');

        if ($token === '' || $chatId === '') {
            Log::warning('Notifikasi Telegram pembayaran aktif tetapi konfigurasinya belum lengkap.');

            return;
        }

        $order = Order::query()->with(['course', 'bundle'])->find($this->orderId);

        if (! $order?->isAwaitingVerification()) {
            return;
        }

        $response = Http::connectTimeout(5)
            ->timeout(10)
            ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $this->message($order),
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);

        if ($response->successful() && $response->json('ok') === true) {
            return;
        }

        if ($response->status() === 429) {
            $this->release(max(10, (int) $response->json('parameters.retry_after', 10)));

            return;
        }

        if ($response->serverError() || $response->successful()) {
            throw new RuntimeException('Telegram gagal menerima notifikasi pembayaran.');
        }

        $this->logRejectedRequest($order, $response);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Notifikasi Telegram pembayaran gagal setelah retry.', [
            'order_id' => $this->orderId,
            'error' => $exception->getMessage(),
        ]);
    }

    private function message(Order $order): string
    {
        $confirmedAt = $order->payment_confirmed_at?->timezone(config('app.timezone'));

        return implode("\n", [
            '<b>Pembayaran menunggu verifikasi</b>',
            '',
            '<b>Kode:</b> <code>'.$this->escape($order->order_code).'</code>',
            '<b>Produk:</b> '.$this->escape($order->order_title),
            '<b>Nominal:</b> '.$this->escape($order->amount_label),
            '<b>Metode:</b> '.$this->escape($order->payment_method_label),
            '<b>Waktu:</b> '.$this->escape($confirmedAt?->format('d-m-Y H:i').' WIB'),
            '',
            '<a href="'.$this->escape(route('admin.payment-verifications.show', $order)).'">Buka halaman review</a>',
        ]);
    }

    private function escape(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function logRejectedRequest(Order $order, Response $response): void
    {
        Log::error('Telegram menolak notifikasi pembayaran.', [
            'order_code' => $order->order_code,
            'status' => $response->status(),
            'description' => $response->json('description'),
        ]);
    }
}
