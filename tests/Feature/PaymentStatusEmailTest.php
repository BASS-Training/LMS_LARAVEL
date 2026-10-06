<?php

namespace Tests\Feature;

use App\Models\Bundle;
use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use App\Notifications\PaymentStatusNotification;
use App\Services\Payment\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PaymentStatusEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_course_email_is_queued_once_with_order_and_invoice_links(): void
    {
        Notification::fake();
        [$buyer, $order] = $this->makeOrder();
        $orders = app(OrderService::class);

        $orders->applyPaymentStatus($order, $this->paidPayload($order));
        $orders->applyPaymentStatus($order->fresh(), $this->paidPayload($order));

        Notification::assertSentToTimes($buyer, PaymentStatusNotification::class, 1);
        Notification::assertSentTo($buyer, PaymentStatusNotification::class, function ($notification) use ($buyer, $order) {
            $mail = $notification->toMail($buyer);

            return $notification->event === PaymentStatusNotification::PAID
                && $notification->afterCommit === true
                && $mail->viewData['orderUrl'] === route('checkout.finish', $order)
                && $mail->viewData['invoiceUrl'] === route('checkout.invoice', $order)
                && $mail->viewData['productTitle'] === $order->course->title
                && str_contains($mail->render(), 'Pembayaran lunas');
        });
        $this->assertSame(Order::STATUS_PAID, $order->fresh()->status);

        $this->actingAs(User::factory()->create())
            ->get(route('checkout.invoice', $order))
            ->assertForbidden();
    }

    public function test_bundle_payment_waits_for_verification_then_sends_approval_email_once(): void
    {
        Notification::fake();
        [$buyer, $order] = $this->makeOrder(verify: true, bundle: true);
        $admin = User::factory()->create();
        $orders = app(OrderService::class);

        $orders->applyPaymentStatus($order, $this->paidPayload($order));
        $orders->applyPaymentStatus($order->fresh(), $this->paidPayload($order));

        Notification::assertSentToTimes($buyer, PaymentStatusNotification::class, 1);
        Notification::assertSentTo($buyer, PaymentStatusNotification::class, fn ($notification) =>
            $notification->event === PaymentStatusNotification::AWAITING_VERIFICATION
            && str_contains($notification->toMail($buyer)->render(), 'Akses kursus akan tersedia setelah disetujui.')
        );
        $this->assertSame(Order::STATUS_AWAITING_VERIFICATION, $order->fresh()->status);

        $orders->approve($order->fresh(), $admin);
        $orders->approve($order->fresh(), $admin);

        Notification::assertSentToTimes($buyer, PaymentStatusNotification::class, 2);
        Notification::assertSentTo($buyer, PaymentStatusNotification::class, fn ($notification) =>
            $notification->event === PaymentStatusNotification::APPROVED
            && $notification->toMail($buyer)->viewData['productTitle'] === $order->bundle->title
        );
        $this->assertSame(Order::STATUS_PAID, $order->fresh()->status);
        $this->assertDatabaseHas('course_user', ['user_id' => $buyer->id, 'course_id' => $order->items->first()->course_id]);
    }

    public function test_rejected_payment_email_contains_reason_without_invoice_link(): void
    {
        Notification::fake();
        [$buyer, $order] = $this->makeOrder(verify: true);
        $admin = User::factory()->create();
        $orders = app(OrderService::class);

        $orders->applyPaymentStatus($order, $this->paidPayload($order));
        $orders->reject($order->fresh(), $admin, 'Dana tidak sesuai.');
        $orders->reject($order->fresh(), $admin, 'Dana tidak sesuai.');

        Notification::assertSentToTimes($buyer, PaymentStatusNotification::class, 2);
        Notification::assertSentTo($buyer, PaymentStatusNotification::class, function ($notification) use ($buyer) {
            $mail = $notification->toMail($buyer);

            return $notification->event === PaymentStatusNotification::REJECTED
                && $mail->viewData['reason'] === 'Dana tidak sesuai.'
                && $mail->viewData['invoiceUrl'] === null
                && str_contains($mail->render(), 'Alasan penolakan');
        });
        $this->assertSame(Order::STATUS_REJECTED, $order->fresh()->status);
    }

    public function test_invalid_payment_amount_does_not_queue_email(): void
    {
        Notification::fake();
        [$buyer, $order] = $this->makeOrder();

        try {
            app(OrderService::class)->applyPaymentStatus($order, [
                'transaction_status' => 'settlement',
                'gross_amount' => '1.00',
                'currency' => 'IDR',
            ]);
            $this->fail('Expected invalid amount to be rejected.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Nominal pembayaran', $exception->getMessage());
        }

        Notification::assertNotSentTo($buyer, PaymentStatusNotification::class);
        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
    }

    public function test_smtp_is_not_called_during_payment_transition(): void
    {
        Queue::fake();
        Mail::fake();
        [, $order] = $this->makeOrder();

        app(OrderService::class)->applyPaymentStatus($order, $this->paidPayload($order));

        $this->assertSame(Order::STATUS_PAID, $order->fresh()->status);
        Mail::assertNothingSent();
        Queue::assertPushed(SendQueuedNotifications::class, fn ($job) =>
            $job->afterCommit === true
            && $job->notification instanceof PaymentStatusNotification
            && $job->notification->event === PaymentStatusNotification::PAID
        );
    }

    private function makeOrder(bool $verify = false, bool $bundle = false): array
    {
        $buyer = User::factory()->create();
        $course = Course::factory()->create(['title' => 'Kursus Bass', 'status' => 'published', 'visibility' => 'catalog']);
        $product = $bundle ? Bundle::factory()->create(['title' => 'Paket Bass']) : null;
        $order = Order::create([
            'user_id' => $buyer->id,
            'course_id' => $bundle ? null : $course->id,
            'bundle_id' => $product?->id,
            'product_title' => $product?->title,
            'requires_payment_verification' => $verify,
            'order_code' => 'BASS-EMAIL-'.fake()->unique()->numerify('######'),
            'base_amount' => 100000,
            'fee_amount' => 4000,
            'amount' => 104000,
            'status' => Order::STATUS_PENDING,
        ]);

        if ($bundle) {
            $order->items()->create(['course_id' => $course->id, 'course_title' => $course->title, 'sort_order' => 0]);
        }

        return [$buyer, $order];
    }

    private function paidPayload(Order $order): array
    {
        return [
            'transaction_status' => 'settlement',
            'gross_amount' => (string) $order->amount,
            'currency' => 'IDR',
        ];
    }
}
