<?php

namespace Tests\Feature;

use App\Enums\RefundReason;
use App\Enums\RefundStatus;
use App\Models\Certificate;
use App\Models\Content;
use App\Models\Course;
use App\Models\FeatureSetting;
use App\Models\Lesson;
use App\Models\Order;
use App\Models\RefundSetting;
use App\Models\User;
use App\Services\Payment\OrderService;
use App\Services\Payment\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RefundFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_can_request_a_full_refund_and_keeps_access_while_waiting(): void
    {
        [$participant, $course, $order] = $this->paidOrder();

        $response = $this->actingAs($participant)->post(route('refunds.store', $order), [
            'reason_type' => RefundReason::ContentMismatch->value,
        ]);

        $response->assertRedirect(route('checkout.finish', $order));
        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'amount' => 104000,
            'status' => RefundStatus::Requested->value,
            'reason' => RefundReason::ContentMismatch->label(),
        ]);
        $this->assertDatabaseHas('course_user', [
            'course_id' => $course->id,
            'user_id' => $participant->id,
            'order_id' => $order->id,
        ]);
    }

    public function test_super_admin_can_update_global_refund_policy(): void
    {
        $admin = User::factory()->create();
        Role::findOrCreate('super-admin', 'web');
        $admin->assignRole('super-admin');

        $response = $this->actingAs($admin)->patch(route('admin.refunds.settings.update'), [
            'request_window_days' => 14,
            'max_progress_percentage' => 30,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('refund_settings', [
            'id' => 1,
            'request_window_days' => 14,
            'max_progress_percentage' => 30,
        ]);
    }

    public function test_disabled_refund_requests_are_hidden_and_blocked_but_admin_history_remains_available(): void
    {
        [$participant, , $order] = $this->paidOrder();
        FeatureSetting::current()->update(['refund_requests_enabled' => false]);

        $this->actingAs($participant)
            ->get(route('checkout.finish', $order))
            ->assertOk()
            ->assertDontSeeText('Ajukan refund penuh');
        $this->post(route('refunds.store', $order), [
            'reason_type' => RefundReason::ContentMismatch->value,
        ])->assertNotFound();
        $this->assertDatabaseCount('refunds', 0);

        $admin = User::factory()->create();
        Role::findOrCreate('super-admin', 'web');
        $admin->assignRole('super-admin');
        $this->actingAs($admin)
            ->get(route('admin.refunds.index'))
            ->assertOk()
            ->assertSeeText('Manajemen Refund');
    }

    public function test_refund_service_rejects_direct_requests_while_feature_is_disabled(): void
    {
        [$participant, , $order] = $this->paidOrder();
        FeatureSetting::current()->update(['refund_requests_enabled' => false]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Pengajuan refund baru sedang dinonaktifkan.');

        app(RefundService::class)->request($order, $participant, 'Alasan pengajuan refund yang valid.');
    }

    public function test_super_admin_can_view_refund_management_pages(): void
    {
        [$participant, $course, $order] = $this->paidOrder();
        $refund = app(RefundService::class)->request(
            $order,
            $participant,
            'Materi kursus tidak sesuai kebutuhan.'
        );
        $admin = User::factory()->create();
        Role::findOrCreate('super-admin', 'web');
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->get(route('admin.refunds.index'))
            ->assertOk()
            ->assertSee('Manajemen Refund')
            ->assertSee($course->title);

        $this->actingAs($admin)
            ->get(route('admin.refunds.show', $refund))
            ->assertOk()
            ->assertSee('Keputusan Admin')
            ->assertSee($order->order_code);
    }

    public function test_regular_user_cannot_update_global_refund_policy(): void
    {
        $response = $this->actingAs(User::factory()->create())->patch(route('admin.refunds.settings.update'), [
            'request_window_days' => 14,
            'max_progress_percentage' => 40,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('refund_settings', [
            'request_window_days' => 7,
            'max_progress_percentage' => 30,
        ]);
    }

    public function test_refund_is_rejected_after_global_request_window(): void
    {
        RefundSetting::current()->update(['request_window_days' => 7]);
        [$participant, , $order] = $this->paidOrder();
        $order->update(['paid_at' => now()->subDays(8)]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Batas pengajuan refund');

        app(RefundService::class)->request($order->fresh(), $participant, 'Alasan pengajuan refund yang valid.');
    }

    public function test_refund_is_rejected_when_progress_exceeds_global_limit(): void
    {
        RefundSetting::current()->update(['max_progress_percentage' => 30]);
        [$participant, $course, $order] = $this->paidOrder();
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);
        $contents = Content::factory()->count(2)->create(['lesson_id' => $lesson->id]);
        $participant->completedContents()->attach($contents->first()->id, [
            'completed' => true,
            'completed_at' => now(),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('telah melebihi batas refund 30%');

        app(RefundService::class)->request($order, $participant, 'Alasan pengajuan refund yang valid.');
    }

    public function test_refund_is_allowed_at_exactly_the_global_progress_limit(): void
    {
        RefundSetting::current()->update(['max_progress_percentage' => 30]);
        [$participant, $course, $order] = $this->paidOrder();
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);
        $contents = Content::factory()->count(10)->create(['lesson_id' => $lesson->id]);

        foreach ($contents->take(3) as $content) {
            $participant->completedContents()->attach($content->id, [
                'completed' => true,
                'completed_at' => now(),
            ]);
        }

        $refund = app(RefundService::class)->request(
            $order,
            $participant,
            'Alasan pengajuan refund yang valid.'
        );

        $this->assertSame(RefundStatus::Requested, $refund->status);
    }

    public function test_issued_certificate_blocks_refund_request(): void
    {
        [$participant, $course, $order] = $this->paidOrder();
        $this->issueCertificate($participant, $course);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('sertifikat kursus sudah diterbitkan');

        app(RefundService::class)->request($order, $participant, 'Alasan pengajuan refund yang valid.');
    }

    public function test_certificate_issued_after_request_blocks_admin_approval(): void
    {
        [$participant, $course, $order] = $this->paidOrder();
        $refunds = app(RefundService::class);
        $refund = $refunds->request($order, $participant, 'Alasan pengajuan refund yang valid.');
        $this->issueCertificate($participant, $course);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('sertifikat kursus sudah diterbitkan');

        $refunds->approve($refund, User::factory()->create());
    }

    public function test_other_refund_reason_requires_detail_and_is_persisted(): void
    {
        [$participant, , $order] = $this->paidOrder();

        $this->actingAs($participant)->post(route('refunds.store', $order), [
            'reason_type' => RefundReason::Other->value,
        ])->assertSessionHasErrors('reason_other');

        $detail = 'Saya memiliki kondisi khusus yang perlu ditinjau oleh admin.';
        $this->actingAs($participant)->post(route('refunds.store', $order), [
            'reason_type' => RefundReason::Other->value,
            'reason_other' => $detail,
        ])->assertRedirect(route('checkout.finish', $order));

        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'reason' => 'Lainnya: '.$detail,
        ]);
    }

    public function test_approved_refund_returns_full_amount_and_revokes_only_purchase_access(): void
    {
        [$participant, $course, $order] = $this->paidOrder();
        $admin = User::factory()->create();
        $refunds = app(RefundService::class);
        $refund = $refunds->request($order, $participant, 'Saya ingin membatalkan pembelian kursus ini.');

        Http::fake([
            '*/v2/*/refund' => Http::response([
                'status_code' => '200',
                'transaction_status' => 'refund',
                'refund_chargeback_id' => 'refund-test-001',
                'refund_key' => $refund->idempotency_key,
                'refund_amount' => '104000.00',
            ]),
        ]);

        $result = $refunds->approve($refund, $admin, 'Disetujui admin.');

        $this->assertSame(RefundStatus::Processing, $result->status);
        $this->assertDatabaseHas('course_user', [
            'course_id' => $course->id,
            'user_id' => $participant->id,
            'order_id' => $order->id,
        ]);

        $result = $refunds->applyProviderNotification($order, [
            'transaction_status' => 'refund',
            'gross_amount' => '104000.00',
            'refunds' => [[
                'refund_chargeback_id' => 'refund-test-001',
                'refund_key' => $refund->idempotency_key,
                'refund_amount' => '104000.00',
                'bank_confirmed_at' => now()->toDateTimeString(),
            ]],
        ]);

        $this->assertSame(RefundStatus::Refunded, $result->status);
        $this->assertSame(104000, $result->amount);
        $this->assertSame('refund-test-001', $result->provider_refund_id);
        $this->assertSame(Order::STATUS_REFUNDED, $order->fresh()->status);
        $this->assertDatabaseMissing('course_user', [
            'course_id' => $course->id,
            'user_id' => $participant->id,
            'order_id' => $order->id,
        ]);

        Http::assertSent(fn ($request) => $request['amount'] === 104000
            && $request['refund_key'] === $refund->idempotency_key);
    }

    public function test_failed_gateway_refund_keeps_course_access_for_retry(): void
    {
        [$participant, $course, $order] = $this->paidOrder();
        $admin = User::factory()->create();
        $refunds = app(RefundService::class);
        $refund = $refunds->request($order, $participant, 'Saya ingin membatalkan pembelian kursus ini.');

        Http::fake([
            '*/v2/*/refund' => Http::response(['status_message' => 'Refund unavailable'], 500),
        ]);

        $result = $refunds->approve($refund, $admin);

        $this->assertSame(RefundStatus::Failed, $result->status);
        $this->assertSame(Order::STATUS_PAID, $order->fresh()->status);
        $this->assertDatabaseHas('course_user', [
            'course_id' => $course->id,
            'user_id' => $participant->id,
            'order_id' => $order->id,
        ]);
    }

    public function test_participant_can_cancel_pending_order_after_gateway_cancellation(): void
    {
        config(['midtrans.server_key' => 'test-server-key']);
        $participant = User::factory()->create();
        $course = $this->course();
        $order = Order::create([
            'user_id' => $participant->id,
            'course_id' => $course->id,
            'order_code' => 'BASS-PENDING-001',
            'base_amount' => 100000,
            'fee_amount' => 4000,
            'amount' => 104000,
            'status' => Order::STATUS_PENDING,
            'snap_redirect_url' => 'https://example.test/pay',
            'expires_at' => now()->addHour(),
        ]);

        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '/cancel')) {
                return Http::response(['status_code' => '200']);
            }

            return Http::response([
                'status_code' => '200',
                'transaction_status' => 'pending',
                'gross_amount' => '104000.00',
                'currency' => 'IDR',
            ]);
        });

        $response = $this->actingAs($participant)->post(route('checkout.cancel', $order));

        $response->assertRedirect(route('checkout.finish', $order));
        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
        $this->assertSame($participant->id, $order->fresh()->cancelled_by);
        $this->assertNotNull($order->fresh()->cancelled_at);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/cancel')
            && $request->method() === 'POST'
            && $request->body() === '');
    }

    public function test_cancel_succeeds_when_failed_api_response_reconciles_as_cancelled(): void
    {
        config(['midtrans.server_key' => 'test-server-key']);
        $participant = User::factory()->create();
        $course = $this->course();
        $order = Order::create([
            'user_id' => $participant->id,
            'course_id' => $course->id,
            'order_code' => 'BASS-PENDING-RECONCILE',
            'base_amount' => 100000,
            'fee_amount' => 4000,
            'amount' => 104000,
            'status' => Order::STATUS_PENDING,
            'snap_redirect_url' => 'https://example.test/pay',
            'expires_at' => now()->addHour(),
        ]);
        $statusChecks = 0;

        Http::fake(function ($request) use (&$statusChecks) {
            if (str_ends_with($request->url(), '/cancel')) {
                return Http::response([
                    'status_code' => '500',
                    'status_message' => 'Temporary provider error',
                ], 500);
            }

            $statusChecks++;

            return Http::response([
                'status_code' => $statusChecks === 1 ? '201' : '202',
                'order_id' => 'BASS-PENDING-RECONCILE',
                'transaction_status' => $statusChecks === 1 ? 'pending' : 'cancel',
                'transaction_id' => 'midtrans-transaction-001',
                'gross_amount' => '104000.00',
                'currency' => 'IDR',
            ]);
        });

        $response = $this->actingAs($participant)->post(route('checkout.cancel', $order));

        $response
            ->assertRedirect(route('checkout.finish', $order))
            ->assertSessionHas('success')
            ->assertSessionHasNoErrors();
        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
        $this->assertSame($participant->id, $order->fresh()->cancelled_by);
        Http::assertSent(fn ($request) => str_ends_with(
            $request->url(),
            '/v2/midtrans-transaction-001/cancel'
        ));
    }

    public function test_pending_order_waits_for_confirmation_when_midtrans_cancel_stays_unavailable(): void
    {
        config(['midtrans.server_key' => 'test-server-key']);
        $participant = User::factory()->create();
        $course = $this->course();
        $order = Order::create([
            'user_id' => $participant->id,
            'course_id' => $course->id,
            'order_code' => 'BASS-PENDING-LOCAL-CANCEL',
            'base_amount' => 100000,
            'fee_amount' => 4000,
            'amount' => 104000,
            'status' => Order::STATUS_PENDING,
            'snap_redirect_url' => 'https://example.test/pay',
            'expires_at' => now()->addHour(),
        ]);

        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '/cancel')) {
                return Http::response([
                    'status_code' => '500',
                    'status_message' => 'Gateway temporarily unavailable',
                ], 500);
            }

            return Http::response([
                'status_code' => '201',
                'order_id' => 'BASS-PENDING-LOCAL-CANCEL',
                'transaction_status' => 'pending',
                'transaction_id' => 'midtrans-transaction-local-cancel',
                'gross_amount' => '104000.00',
                'currency' => 'IDR',
            ]);
        });

        $response = $this->actingAs($participant)->post(route('checkout.cancel', $order));

        $response
            ->assertRedirect(route('checkout.finish', $order))
            ->assertSessionHas('success')
            ->assertSessionHasNoErrors();
        $this->assertSame(Order::STATUS_CANCELLATION_PENDING, $order->fresh()->status);
        $this->assertSame('midtrans-transaction-local-cancel', $order->fresh()->transaction_id);
        $this->assertNull($order->fresh()->cancelled_at);
    }

    public function test_cancellation_reconciliation_retries_and_confirms_pending_request(): void
    {
        config(['midtrans.server_key' => 'test-server-key']);
        [, , $order] = $this->paidOrder(
            status: Order::STATUS_CANCELLATION_PENDING,
            confirmed: false,
        );
        $order->update([
            'transaction_id' => 'midtrans-retry-001',
            'raw_response' => ['transaction_status' => 'pending'],
        ]);

        Http::fake(function ($request) use ($order) {
            if (str_ends_with($request->url(), '/cancel')) {
                return Http::response(['status_code' => '200']);
            }

            return Http::response([
                'status_code' => '201',
                'order_id' => $order->order_code,
                'transaction_status' => 'pending',
                'transaction_id' => 'midtrans-retry-001',
                'gross_amount' => '104000.00',
                'currency' => 'IDR',
            ]);
        });

        $this->artisan('orders:reconcile-cancellations')->assertSuccessful();

        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->cancelled_at);
        Http::assertSent(fn ($request) => str_ends_with(
            $request->url(),
            '/v2/midtrans-retry-001/cancel'
        ) && $request->body() === '');
    }

    public function test_cancellation_reconciliation_updates_legacy_pending_snapshot(): void
    {
        config(['midtrans.server_key' => 'test-server-key']);
        [, , $order] = $this->paidOrder(status: Order::STATUS_CANCELLED, confirmed: false);
        $order->update([
            'transaction_id' => 'midtrans-legacy-001',
            'raw_response' => ['transaction_status' => 'pending'],
            'cancelled_at' => now()->subMinute(),
        ]);

        Http::fake(fn () => Http::response([
            'status_code' => '200',
            'order_id' => $order->order_code,
            'transaction_status' => 'cancel',
            'transaction_id' => 'midtrans-legacy-001',
            'gross_amount' => '104000.00',
            'currency' => 'IDR',
        ]));

        $this->artisan('orders:reconcile-cancellations')->assertSuccessful();

        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
        $this->assertSame('cancel', $order->fresh()->raw_response['transaction_status']);
        Http::assertSentCount(1);
    }

    public function test_payment_status_rejects_an_amount_mismatch(): void
    {
        [, , $order] = $this->paidOrder(status: Order::STATUS_PENDING, confirmed: false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Nominal pembayaran');

        app(OrderService::class)->applyPaymentStatus($order, [
            'transaction_status' => 'settlement',
            'gross_amount' => '1000.00',
            'currency' => 'IDR',
        ]);
    }

    public function test_rejecting_payment_verification_automatically_processes_full_refund(): void
    {
        [$participant, , $order] = $this->paidOrder(
            status: Order::STATUS_AWAITING_VERIFICATION,
            confirmed: true,
        );
        $admin = User::factory()->create();
        Role::findOrCreate('super-admin', 'web');
        $admin->assignRole('super-admin');

        Http::fake(fn ($request) => Http::response([
            'status_code' => '200',
            'transaction_status' => 'refund',
            'refund_chargeback_id' => 'refund-auto-001',
            'refund_key' => $request['refund_key'],
            'refund_amount' => '104000.00',
        ]));

        $response = $this->actingAs($admin)->post(
            route('admin.payment-verifications.reject', $order),
            ['reason' => 'Dana tidak dapat direkonsiliasi.']
        );

        $response->assertRedirect(route('admin.payment-verifications.index'));
        $refund = $order->fresh()->refund;
        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'amount' => 104000,
            'status' => RefundStatus::Processing->value,
            'reviewed_by' => $admin->id,
        ]);

        $refunds = app(RefundService::class);
        $refunds->applyProviderNotification($order->fresh(), [
            'transaction_status' => 'refund',
            'gross_amount' => '104000.00',
            'refunds' => [[
                'refund_chargeback_id' => 'refund-auto-001',
                'refund_key' => $refund->idempotency_key,
                'refund_amount' => '104000.00',
                'bank_confirmed_at' => now()->toDateTimeString(),
            ]],
        ]);

        $this->assertSame(Order::STATUS_REFUNDED, $order->fresh()->status);
        $this->assertDatabaseMissing('course_user', [
            'user_id' => $participant->id,
            'course_id' => $order->course_id,
        ]);
    }

    public function test_unsupported_payment_method_requires_manual_completion(): void
    {
        [$participant, $course, $order] = $this->paidOrder();
        $order->update(['payment_type' => 'bank_transfer']);
        $admin = User::factory()->create();
        $refunds = app(RefundService::class);
        $refund = $refunds->request($order, $participant, 'Saya ingin membatalkan pembelian kursus ini.');
        Http::fake();

        $result = $refunds->approve($refund, $admin);

        $this->assertSame(RefundStatus::ManualRequired, $result->status);
        Http::assertNothingSent();
        $this->assertDatabaseHas('course_user', ['order_id' => $order->id]);

        $result = $refunds->completeManually($result, $admin, 'TRANSFER-001');

        $this->assertSame(RefundStatus::Refunded, $result->status);
        $this->assertSame('TRANSFER-001', $result->provider_refund_id);
        $this->assertDatabaseMissing('course_user', [
            'course_id' => $course->id,
            'user_id' => $participant->id,
            'order_id' => $order->id,
        ]);
    }

    public function test_http_200_with_midtrans_error_body_does_not_complete_refund(): void
    {
        [$participant, , $order] = $this->paidOrder();
        $admin = User::factory()->create();
        $refunds = app(RefundService::class);
        $refund = $refunds->request($order, $participant, 'Saya ingin membatalkan pembelian kursus ini.');

        Http::fake([
            '*/v2/*/refund' => Http::response([
                'status_code' => '412',
                'status_message' => 'Merchant cannot modify transaction',
            ]),
        ]);

        $result = $refunds->approve($refund, $admin);

        $this->assertSame(RefundStatus::Failed, $result->status);
        $this->assertSame(Order::STATUS_PAID, $order->fresh()->status);
        $this->assertDatabaseHas('course_user', ['order_id' => $order->id]);
    }

    public function test_refund_preserves_access_that_existed_before_payment_confirmation(): void
    {
        [$participant, $course, $order] = $this->paidOrder(
            status: Order::STATUS_PENDING,
            confirmed: false,
        );
        $course->enrolledUsers()->attach($participant->id);

        app(OrderService::class)->applyPaymentStatus($order, [
            'transaction_status' => 'settlement',
            'transaction_id' => 'transaction-legacy-access',
            'payment_type' => 'credit_card',
            'gross_amount' => '104000.00',
            'currency' => 'IDR',
        ]);

        $this->assertDatabaseHas('course_user', [
            'course_id' => $course->id,
            'user_id' => $participant->id,
            'order_id' => null,
        ]);

        $refunds = app(RefundService::class);
        $admin = User::factory()->create();
        $refund = $refunds->request($order->fresh(), $participant, 'Saya ingin membatalkan pembelian kursus ini.');
        Http::fake(fn ($request) => Http::response([
            'status_code' => '200',
            'transaction_status' => 'refund',
            'refund_chargeback_id' => 'refund-independent-access',
            'refund_key' => $request['refund_key'],
            'refund_amount' => '104000.00',
        ]));
        $refund = $refunds->approve($refund, $admin);

        $refunds->applyProviderNotification($order->fresh(), [
            'transaction_status' => 'refund',
            'gross_amount' => '104000.00',
            'refunds' => [[
                'refund_chargeback_id' => 'refund-independent-access',
                'refund_key' => $refund->idempotency_key,
                'refund_amount' => '104000.00',
                'bank_confirmed_at' => now()->toDateTimeString(),
            ]],
        ]);

        $this->assertDatabaseHas('course_user', [
            'course_id' => $course->id,
            'user_id' => $participant->id,
            'order_id' => null,
        ]);
    }

    public function test_refund_preserves_independent_access_granted_after_purchase(): void
    {
        [$participant, $course, $order] = $this->paidOrder();
        $course->enrolledUsers()->syncWithoutDetaching([
            $participant->id => ['has_independent_access' => true],
        ]);

        $refunds = app(RefundService::class);
        $admin = User::factory()->create();
        $refund = $refunds->request($order, $participant, 'Saya ingin membatalkan pembelian kursus ini.');
        Http::fake(fn ($request) => Http::response([
            'status_code' => '200',
            'transaction_status' => 'refund',
            'refund_chargeback_id' => 'refund-independent-after',
            'refund_key' => $request['refund_key'],
            'refund_amount' => '104000.00',
        ]));
        $refund = $refunds->approve($refund, $admin);

        $refunds->applyProviderNotification($order, [
            'transaction_status' => 'refund',
            'gross_amount' => '104000.00',
            'refunds' => [[
                'refund_chargeback_id' => 'refund-independent-after',
                'refund_key' => $refund->idempotency_key,
                'refund_amount' => '104000.00',
                'bank_confirmed_at' => now()->toDateTimeString(),
            ]],
        ]);

        $this->assertDatabaseHas('course_user', [
            'course_id' => $course->id,
            'user_id' => $participant->id,
            'order_id' => null,
            'has_independent_access' => true,
        ]);
    }

    public function test_stale_pending_notification_does_not_reopen_cancellation_pending_order(): void
    {
        [, , $order] = $this->paidOrder(status: Order::STATUS_CANCELLATION_PENDING, confirmed: false);

        app(OrderService::class)->applyPaymentStatus($order, [
            'transaction_status' => 'pending',
            'gross_amount' => '104000.00',
            'currency' => 'IDR',
        ]);

        $this->assertSame(Order::STATUS_CANCELLATION_PENDING, $order->fresh()->status);
    }

    public function test_late_settlement_while_cancellation_is_pending_is_still_fulfilled(): void
    {
        [$participant, $course, $order] = $this->paidOrder(
            status: Order::STATUS_CANCELLATION_PENDING,
            confirmed: false,
        );

        app(OrderService::class)->applyPaymentStatus($order, [
            'transaction_status' => 'settlement',
            'transaction_id' => 'late-settlement-001',
            'payment_type' => 'qris',
            'gross_amount' => '104000.00',
            'currency' => 'IDR',
        ]);

        $this->assertSame(Order::STATUS_PAID, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->payment_confirmed_at);
        $this->assertDatabaseHas('course_user', [
            'course_id' => $course->id,
            'user_id' => $participant->id,
            'order_id' => $order->id,
        ]);
    }

    public function test_expired_pending_orders_are_closed_automatically(): void
    {
        config(['midtrans.server_key' => null]);
        [, , $order] = $this->paidOrder(status: Order::STATUS_PENDING, confirmed: false);
        $order->update(['expires_at' => now()->subMinute()]);

        $this->artisan('orders:expire-pending')->assertSuccessful();

        $this->assertSame(Order::STATUS_EXPIRED, $order->fresh()->status);
    }

    private function paidOrder(string $status = Order::STATUS_PAID, bool $confirmed = true): array
    {
        $participant = User::factory()->create();
        $course = $this->course();
        $order = Order::create([
            'user_id' => $participant->id,
            'course_id' => $course->id,
            'order_code' => 'BASS-REFUND-'.fake()->unique()->numerify('######'),
            'invoice_number' => $confirmed ? 'INV/TEST/'.fake()->unique()->numerify('####') : null,
            'base_amount' => 100000,
            'fee_amount' => 4000,
            'amount' => 104000,
            'status' => $status,
            'payment_type' => 'credit_card',
            'raw_response' => ['transaction_status' => 'settlement'],
            'payment_confirmed_at' => $confirmed ? now() : null,
            'paid_at' => $confirmed ? now() : null,
        ]);

        if ($confirmed && $status === Order::STATUS_PAID) {
            $course->enrolledUsers()->attach($participant->id, [
                'order_id' => $order->id,
                'has_independent_access' => false,
            ]);
        }

        return [$participant, $course, $order];
    }

    private function course(): Course
    {
        return Course::factory()->create([
            'status' => 'published',
            'visibility' => 'catalog',
            'price' => 100000,
            'program_type' => 'regular',
        ]);
    }

    private function issueCertificate(User $user, Course $course): Certificate
    {
        $templateId = DB::table('certificate_templates')->insertGetId([
            'name' => 'Template Test',
            'layout_data' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Certificate::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'certificate_template_id' => $templateId,
            'certificate_code' => 'CERT-'.fake()->unique()->numerify('######'),
            'issued_at' => now(),
        ]);
    }
}
