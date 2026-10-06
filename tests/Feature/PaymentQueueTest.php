<?php

namespace Tests\Feature;

use App\Jobs\PrepareSnapTransaction;
use App\Jobs\ProcessPaymentWebhook;
use App\Models\Bundle;
use App\Models\Course;
use App\Models\FeatureSetting;
use App\Models\Order;
use App\Models\PaymentWebhookReceipt;
use App\Models\User;
use App\Notifications\PaymentStatusNotification;
use App\Services\Payment\MidtransGateway;
use App\Services\Payment\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PaymentQueueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('payment_queue.enabled', true);
        config()->set('midtrans.server_key', 'test-server-key');
        Queue::fake();
        Notification::fake();
    }

    public function test_checkout_queues_snap_without_calling_gateway_and_reuses_order(): void
    {
        $buyer = User::factory()->create();
        $course = Course::factory()->create(['status' => 'published', 'visibility' => 'catalog', 'price' => 99000]);
        Http::fake();

        $first = app(OrderService::class)->checkout($course, $buyer, 'bank_transfer');
        $second = app(OrderService::class)->checkout($course, $buyer, 'bank_transfer');

        $this->assertSame($first->id, $second->id);
        $this->assertSame('queued', $first->fresh()->snap_status);
        $this->assertDatabaseCount('orders', 1);
        Queue::assertPushed(PrepareSnapTransaction::class, 1);
        Http::assertNothingSent();

        $this->actingAs(User::factory()->create())
            ->get(route('checkout.snap-status', $first))->assertForbidden();
        $this->actingAs($buyer)->get(route('checkout.finish', $first))
            ->assertOk()->assertSee('Menyiapkan pembayaran');
    }

    public function test_buyer_is_sent_to_waiting_page_after_clicking_pay(): void
    {
        $buyer = User::factory()->create();
        $course = Course::factory()->create(['status' => 'published', 'visibility' => 'catalog', 'price' => 99000]);
        Http::fake();

        $response = $this->actingAs($buyer)->post(route('checkout.store', $course), ['method' => 'bank_transfer']);
        $order = Order::query()->firstOrFail();

        $response->assertRedirect(route('checkout.finish', $order));
        $this->get(route('checkout.finish', $order))->assertOk()->assertSee('Menyiapkan pembayaran');
        Http::assertNothingSent();
    }

    public function test_snap_worker_makes_url_available_to_owner(): void
    {
        $buyer = User::factory()->create();
        $course = Course::factory()->create(['status' => 'published', 'visibility' => 'catalog', 'price' => 99000]);
        $order = app(OrderService::class)->checkout($course, $buyer, 'bank_transfer');
        Http::fake(['*snap/v1/transactions' => Http::response([
            'token' => 'snap-token', 'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/test',
        ])]);

        (new PrepareSnapTransaction($order->id))->handle(app(MidtransGateway::class));

        $this->assertSame('ready', $order->fresh()->snap_status);
        $this->actingAs($buyer)->get(route('checkout.snap-status', $order))
            ->assertOk()->assertJsonPath('redirect_url', 'https://app.sandbox.midtrans.com/snap/v2/vtweb/test');
    }

    public function test_bundle_checkout_queues_one_snap_job(): void
    {
        FeatureSetting::current()->update(['bundles_enabled' => true]);
        $buyer = User::factory()->create();
        $course = Course::factory()->create(['status' => 'published', 'visibility' => 'catalog', 'price' => 99000]);
        $bundle = Bundle::factory()->create(['price' => 90000, 'is_active' => true]);
        $bundle->courses()->attach($course->id, ['sort_order' => 0]);
        Http::fake();

        $order = app(OrderService::class)->checkoutBundle($bundle, $buyer, 'bank_transfer');

        $this->assertSame($bundle->id, $order->bundle_id);
        $this->assertSame('queued', $order->snap_status);
        $this->assertDatabaseCount('order_items', 1);
        Queue::assertPushed(PrepareSnapTransaction::class, 1);
        Http::assertNothingSent();
    }

    public function test_signed_duplicate_webhook_is_queued_then_processed_once(): void
    {
        $buyer = User::factory()->create();
        $course = Course::factory()->create(['status' => 'published', 'visibility' => 'catalog', 'price' => 99000]);
        $order = app(OrderService::class)->checkout($course, $buyer, 'bank_transfer');
        $payload = [
            'order_id' => $order->order_code,
            'status_code' => '200',
            'gross_amount' => (string) $order->amount.'.00',
            'transaction_status' => 'settlement',
            'currency' => 'IDR',
        ];
        $payload['signature_key'] = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].config('midtrans.server_key'));

        $this->postJson(route('checkout.notification'), $payload)->assertOk();
        $this->postJson(route('checkout.notification'), $payload)->assertOk();
        $this->assertDatabaseCount('payment_webhook_receipts', 1);
        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);

        $receipt = PaymentWebhookReceipt::firstOrFail();
        $job = new ProcessPaymentWebhook($receipt->id);
        $job->handle(app(OrderService::class), app(\App\Services\Payment\RefundService::class));
        $job->handle(app(OrderService::class), app(\App\Services\Payment\RefundService::class));

        $this->assertSame(Order::STATUS_PAID, $order->fresh()->status);
        $this->assertNotNull($receipt->fresh()->processed_at);
        $this->assertDatabaseCount('course_user', 1);
        Notification::assertSentToTimes($buyer, PaymentStatusNotification::class, 1);
    }

    public function test_invalid_amount_never_enters_queue(): void
    {
        $buyer = User::factory()->create();
        $course = Course::factory()->create(['status' => 'published', 'visibility' => 'catalog', 'price' => 99000]);
        $order = app(OrderService::class)->checkout($course, $buyer, 'bank_transfer');
        $payload = [
            'order_id' => $order->order_code,
            'status_code' => '200',
            'gross_amount' => '1.00',
            'transaction_status' => 'settlement',
        ];
        $payload['signature_key'] = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].config('midtrans.server_key'));

        $this->postJson(route('checkout.notification'), $payload)->assertStatus(422);
        $this->assertDatabaseCount('payment_webhook_receipts', 0);
        Queue::assertNotPushed(ProcessPaymentWebhook::class);
    }
}
