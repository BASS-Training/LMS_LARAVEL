<?php

namespace Tests\Feature;

use App\Jobs\SendPaymentVerificationTelegram;
use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use App\Services\Payment\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PaymentVerificationTelegramTest extends TestCase
{
    use RefreshDatabase;

    public function test_alert_is_queued_once_after_payment_enters_verification_queue(): void
    {
        Queue::fake();
        Notification::fake();
        $order = $this->makeOrder();
        $orders = app(OrderService::class);

        $orders->applyPaymentStatus($order, $this->paidPayload($order));
        $orders->applyPaymentStatus($order->fresh(), $this->paidPayload($order));

        Queue::assertPushed(SendPaymentVerificationTelegram::class, function ($job) use ($order) {
            return $job->orderId === $order->id && $job->afterCommit === true;
        });
        Queue::assertPushed(SendPaymentVerificationTelegram::class, 1);
        $this->assertSame(Order::STATUS_AWAITING_VERIFICATION, $order->fresh()->status);
    }

    public function test_alert_is_not_queued_for_automatically_approved_payment(): void
    {
        Queue::fake();
        Notification::fake();
        $order = $this->makeOrder(requiresVerification: false);

        app(OrderService::class)->applyPaymentStatus($order, $this->paidPayload($order));

        Queue::assertNotPushed(SendPaymentVerificationTelegram::class);
        $this->assertSame(Order::STATUS_PAID, $order->fresh()->status);
    }

    public function test_job_sends_a_safe_summary_and_admin_review_link(): void
    {
        config()->set('services.telegram', [
            'payment_alerts_enabled' => true,
            'bot_token' => 'test-token',
            'payment_chat_id' => '-1001234567890',
        ]);
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 123]]),
        ]);
        $order = $this->makeOrder(title: 'Bass <Dasar> & Lanjutan');
        $order->update([
            'status' => Order::STATUS_AWAITING_VERIFICATION,
            'payment_type' => 'bank_transfer',
            'payment_confirmed_at' => '2026-10-12 09:30:00',
        ]);

        (new SendPaymentVerificationTelegram($order->id))->handle();

        Http::assertSent(function ($request) use ($order) {
            $text = $request['text'];

            return $request->url() === 'https://api.telegram.org/bottest-token/sendMessage'
                && $request['chat_id'] === '-1001234567890'
                && $request['parse_mode'] === 'HTML'
                && str_contains($text, $order->order_code)
                && str_contains($text, 'Bass &lt;Dasar&gt; &amp; Lanjutan')
                && str_contains($text, 'Rp 104.000')
                && str_contains($text, 'Bank Transfer')
                && str_contains($text, route('admin.payment-verifications.show', $order))
                && ! str_contains($text, $order->user->email);
        });
    }

    public function test_disabled_alert_does_not_call_telegram(): void
    {
        config()->set('services.telegram.payment_alerts_enabled', false);
        Http::fake();
        $order = $this->makeOrder();
        $order->update([
            'status' => Order::STATUS_AWAITING_VERIFICATION,
            'payment_confirmed_at' => now(),
        ]);

        (new SendPaymentVerificationTelegram($order->id))->handle();

        Http::assertNothingSent();
    }

    public function test_telegram_rejection_does_not_change_order_status(): void
    {
        config()->set('services.telegram', [
            'payment_alerts_enabled' => true,
            'bot_token' => 'invalid-token',
            'payment_chat_id' => '-1001234567890',
        ]);
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Unauthorized'], 401),
        ]);
        $order = $this->makeOrder();
        $order->update([
            'status' => Order::STATUS_AWAITING_VERIFICATION,
            'payment_confirmed_at' => now(),
        ]);

        (new SendPaymentVerificationTelegram($order->id))->handle();

        $this->assertSame(Order::STATUS_AWAITING_VERIFICATION, $order->fresh()->status);
    }

    private function makeOrder(bool $requiresVerification = true, string $title = 'Kursus Bass'): Order
    {
        $buyer = User::factory()->create();
        $course = Course::factory()->create([
            'title' => $title,
            'status' => 'published',
            'visibility' => 'catalog',
        ]);

        return Order::create([
            'user_id' => $buyer->id,
            'course_id' => $course->id,
            'product_title' => $title,
            'requires_payment_verification' => $requiresVerification,
            'order_code' => 'BASS-TELEGRAM-'.fake()->unique()->numerify('######'),
            'base_amount' => 100000,
            'fee_amount' => 4000,
            'amount' => 104000,
            'status' => Order::STATUS_PENDING,
        ]);
    }

    private function paidPayload(Order $order): array
    {
        return [
            'transaction_status' => 'settlement',
            'gross_amount' => (string) $order->amount,
            'currency' => 'IDR',
            'payment_type' => 'bank_transfer',
        ];
    }
}
