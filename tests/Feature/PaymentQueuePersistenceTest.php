<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use App\Services\Payment\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentQueuePersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_and_payment_job_commit_together(): void
    {
        config()->set('payment_queue.enabled', true);
        config()->set('midtrans.server_key', 'test-server-key');
        $buyer = User::factory()->create();
        $course = Course::factory()->create(['status' => 'published', 'visibility' => 'catalog', 'price' => 99000]);

        $order = app(OrderService::class)->checkout($course, $buyer, 'bank_transfer');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'snap_status' => 'queued']);
        $this->assertDatabaseHas('jobs', ['queue' => 'payments']);

        $anotherCourse = Course::factory()->create(['status' => 'published', 'visibility' => 'catalog', 'price' => 99000]);
        try {
            DB::transaction(function () use ($buyer, $anotherCourse) {
                app(OrderService::class)->checkout($anotherCourse, $buyer, 'bank_transfer');
                throw new \RuntimeException('rollback');
            });
        } catch (\RuntimeException $exception) {
            $this->assertSame('rollback', $exception->getMessage());
        }

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('jobs', 1);
    }

    public function test_webhook_receipt_and_worker_job_are_saved_together(): void
    {
        config()->set('payment_queue.enabled', true);
        config()->set('midtrans.server_key', 'test-server-key');
        $buyer = User::factory()->create();
        $course = Course::factory()->create(['status' => 'published', 'visibility' => 'catalog', 'price' => 99000]);
        $order = app(OrderService::class)->checkout($course, $buyer, 'bank_transfer');
        $payload = [
            'order_id' => $order->order_code,
            'status_code' => '200',
            'gross_amount' => (string) $order->amount.'.00',
            'transaction_status' => 'settlement',
        ];
        $payload['signature_key'] = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].config('midtrans.server_key'));

        $this->postJson(route('checkout.notification'), $payload)->assertOk();

        $this->assertDatabaseCount('payment_webhook_receipts', 1);
        $this->assertDatabaseCount('jobs', 2);
        $this->assertSame('pending', $order->fresh()->status);
    }
}
