<?php

namespace Tests\Feature;

use App\Enums\RefundStatus;
use App\Models\Bundle;
use App\Models\Certificate;
use App\Models\Coupon;
use App\Models\CouponSetting;
use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use App\Services\Payment\CouponService;
use App\Services\Payment\OrderService;
use App\Services\Payment\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BundleFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_bundle_catalog_and_checkout_show_personal_ownership_discount(): void
    {
        [$bundle, $courses] = $this->bundle(price: 150000);
        $buyer = User::factory()->create();
        $courses[0]->enrolledUsers()->attach($buyer->id);
        $this->configureGateway();

        $this->get(route('bundles.show', $bundle))
            ->assertOk()
            ->assertSee($bundle->title)
            ->assertSee($courses[0]->title)
            ->assertSee($courses[1]->title);

        $this->actingAs($buyer)
            ->get(route('checkout.bundle.choose', $bundle))
            ->assertOk()
            ->assertSee('Potongan kursus dimiliki')
            ->assertSee('Rp 50.000');
    }

    public function test_bundle_checkout_snapshots_membership_price_and_global_coupon(): void
    {
        [$bundle, $courses] = $this->bundle(price: 150000);
        $buyer = User::factory()->create();
        $courses[0]->enrolledUsers()->attach($buyer->id);
        $this->enableCoupons();
        $coupon = Coupon::factory()->create([
            'code' => 'PAKET10',
            'discount_type' => Coupon::TYPE_PERCENTAGE,
            'discount_value' => 10,
            'applies_to_all_courses' => true,
        ]);

        $order = app(OrderService::class)->checkoutBundle($bundle, $buyer, null, $coupon->code);

        $this->assertNull($order->course_id);
        $this->assertSame($bundle->id, $order->bundle_id);
        $this->assertSame(100000, $order->bundle_discount_amount);
        $this->assertSame(5000, $order->discount_amount);
        $this->assertSame(45000, $order->base_amount);
        $this->assertSame(45000, $order->amount);
        $this->assertSame($bundle->title, $order->product_title);
        $this->assertCount(2, $order->items);

        $bundle->courses()->detach($courses[1]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'course_id' => $courses[1]->id,
            'course_title' => $courses[1]->title,
        ]);
    }

    public function test_course_specific_coupon_is_rejected_for_bundle(): void
    {
        [$bundle, $courses] = $this->bundle();
        $buyer = User::factory()->create();
        $this->enableCoupons();
        $coupon = Coupon::factory()->create([
            'code' => 'ONLYONE',
            'applies_to_all_courses' => false,
        ]);
        $coupon->courses()->attach($courses[0]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Kupon khusus kursus tidak dapat digunakan untuk paket kursus');

        app(CouponService::class)->quoteForBundle($coupon->code, $bundle->price, $bundle, $buyer);
    }

    public function test_bundle_settlement_enrolls_every_snapshotted_course_idempotently(): void
    {
        [$bundle, $courses] = $this->bundle();
        $buyer = User::factory()->create();
        $this->configureGateway();
        $service = app(OrderService::class);
        $order = $service->checkoutBundle($bundle, $buyer);
        $payload = [
            'transaction_status' => 'settlement',
            'gross_amount' => (string) $order->amount,
            'currency' => 'IDR',
            'transaction_id' => 'bundle-settlement-1',
            'payment_type' => 'bank_transfer',
        ];

        $service->applyPaymentStatus($order, $payload);
        $service->applyPaymentStatus($order->fresh(), $payload);

        $this->assertSame(Order::STATUS_PAID, $order->fresh()->status);
        foreach ($courses as $course) {
            $this->assertDatabaseHas('course_user', [
                'course_id' => $course->id,
                'user_id' => $buyer->id,
                'order_id' => $order->id,
            ]);
        }
        $this->assertSame(2, $buyer->courses()->whereIn('courses.id', $courses->pluck('id'))->count());
    }

    public function test_bundle_manual_verification_holds_then_grants_all_courses(): void
    {
        [$bundle, $courses] = $this->bundle();
        $bundle->update(['requires_payment_verification' => true]);
        $buyer = User::factory()->create();
        $admin = User::factory()->create();
        $this->configureGateway();
        $service = app(OrderService::class);
        $order = $service->checkoutBundle($bundle->fresh('courses'), $buyer);

        $order = $service->applyPaymentStatus($order, [
            'transaction_status' => 'settlement',
            'gross_amount' => (string) $order->amount,
            'currency' => 'IDR',
            'transaction_id' => 'bundle-manual-1',
            'payment_type' => 'bank_transfer',
        ]);

        $this->assertSame(Order::STATUS_AWAITING_VERIFICATION, $order->status);
        $this->assertSame(0, $buyer->courses()->whereIn('courses.id', $courses->pluck('id'))->count());

        $service->approve($order, $admin);
        $this->assertSame(2, $buyer->courses()->whereIn('courses.id', $courses->pluck('id'))->count());
    }

    public function test_certificate_from_any_bundle_course_blocks_refund(): void
    {
        [$bundle, $courses] = $this->bundle();
        $buyer = User::factory()->create();
        $this->configureGateway();
        $service = app(OrderService::class);
        $order = $service->checkoutBundle($bundle, $buyer);
        $order = $service->applyPaymentStatus($order, [
            'transaction_status' => 'settlement',
            'gross_amount' => (string) $order->amount,
            'currency' => 'IDR',
            'transaction_id' => 'bundle-certificate-1',
            'payment_type' => 'credit_card',
        ]);
        $templateId = DB::table('certificate_templates')->insertGetId([
            'name' => 'Template Bundle',
            'layout_data' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Certificate::create([
            'user_id' => $buyer->id,
            'course_id' => $courses[1]->id,
            'certificate_template_id' => $templateId,
            'certificate_code' => 'BUNDLE-CERT-1',
            'issued_at' => now(),
        ]);

        $eligibility = app(RefundService::class)->eligibility($order, $buyer);

        $this->assertFalse($eligibility['eligible']);
        $this->assertStringContainsString('sertifikat kursus sudah diterbitkan', $eligibility['message']);
        $this->assertStringContainsString($courses[1]->title, $eligibility['message']);
    }

    public function test_bundle_refund_revokes_order_access_and_preserves_independent_access(): void
    {
        [$bundle, $courses] = $this->bundle();
        $buyer = User::factory()->create();
        $admin = User::factory()->create();
        $this->configureGateway();
        $orders = app(OrderService::class);
        $order = $orders->checkoutBundle($bundle, $buyer);
        $orders->applyPaymentStatus($order, [
            'transaction_status' => 'settlement',
            'gross_amount' => (string) $order->amount,
            'currency' => 'IDR',
            'transaction_id' => 'bundle-refund-1',
            'payment_type' => 'credit_card',
        ]);
        $buyer->courses()->updateExistingPivot($courses[0]->id, ['has_independent_access' => true]);

        $refunds = app(RefundService::class);
        $refund = $refunds->request($order->fresh(), $buyer, 'Materi tidak sesuai kebutuhan.');
        Http::fake([
            '*/v2/*/refund' => Http::response([
                'status_code' => '200',
                'transaction_status' => 'refund',
                'refund_chargeback_id' => 'refund-bundle-1',
                'refund_key' => $refund->idempotency_key,
                'refund_amount' => number_format($order->amount, 2, '.', ''),
            ]),
        ]);
        $refund = $refunds->approve($refund, $admin);
        $this->assertSame(RefundStatus::Processing, $refund->status, $refund->failure_message ?? 'Refund tidak processing.');
        $refunds->applyProviderNotification($order->fresh(), [
            'transaction_status' => 'refund',
            'gross_amount' => (string) $order->amount,
            'refunds' => [[
                'refund_key' => $refund->idempotency_key,
                'refund_amount' => (string) $order->amount,
                'refund_chargeback_id' => 'refund-bundle-1',
                'bank_confirmed_at' => now()->toISOString(),
            ]],
        ]);

        $this->assertDatabaseHas('course_user', [
            'course_id' => $courses[0]->id,
            'user_id' => $buyer->id,
            'order_id' => null,
            'has_independent_access' => true,
        ]);
        $this->assertDatabaseMissing('course_user', [
            'course_id' => $courses[1]->id,
            'user_id' => $buyer->id,
        ]);
        $this->assertSame(Order::STATUS_REFUNDED, $order->fresh()->status);
    }

    public function test_bundle_admin_routes_require_permission_and_create_valid_bundle(): void
    {
        $courses = collect([$this->course(), $this->course()]);
        Permission::findOrCreate('manage bundles', 'web');
        $manager = User::factory()->create();
        $manager->givePermissionTo('manage bundles');

        $this->actingAs(User::factory()->create())
            ->get(route('admin.bundles.index'))
            ->assertForbidden();

        $this->getJson(route('admin.bundles.course-options'))
            ->assertForbidden();

        $this->actingAs($manager)->post(route('admin.bundles.store'), [
            'title' => 'Paket Profesional',
            'slug' => 'paket-profesional',
            'description' => 'Dua course pilihan.',
            'price' => 150000,
            'is_active' => 1,
            'requires_payment_verification' => 0,
            'course_ids' => $courses->pluck('id')->reverse()->values()->all(),
        ])->assertRedirect(route('admin.bundles.index'));

        $bundle = Bundle::where('slug', 'paket-profesional')->firstOrFail();
        $this->assertCount(2, $bundle->courses);
        $this->assertDatabaseHas('bundle_course', [
            'bundle_id' => $bundle->id,
            'course_id' => $courses[1]->id,
            'sort_order' => 0,
        ]);
        $this->assertDatabaseHas('bundle_course', [
            'bundle_id' => $bundle->id,
            'course_id' => $courses[0]->id,
            'sort_order' => 1,
        ]);
    }

    public function test_bundle_course_picker_searches_and_paginates_only_eligible_courses(): void
    {
        Permission::findOrCreate('manage bundles', 'web');
        $manager = User::factory()->create();
        $manager->givePermissionTo('manage bundles');

        foreach (range(1, 13) as $number) {
            $this->course()->update(['title' => sprintf('Course Bundle %02d', $number)]);
        }
        $this->course()->update(['title' => 'Course Tersembunyi', 'visibility' => 'private']);
        $this->course()->update(['title' => 'Course Gratis', 'price' => 0]);
        $this->course()->update(['title' => 'Course Draft', 'status' => 'draft']);

        $this->actingAs($manager)
            ->getJson(route('admin.bundles.course-options'))
            ->assertOk()
            ->assertJsonCount(12, 'data')
            ->assertJsonPath('meta.currentPage', 1)
            ->assertJsonPath('meta.lastPage', 2)
            ->assertJsonPath('meta.total', 13);

        $this->getJson(route('admin.bundles.course-options', ['q' => 'Bundle 13']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Course Bundle 13')
            ->assertJsonPath('meta.total', 1);
    }

    public function test_mobile_bundle_api_is_public_and_hides_inactive_bundles(): void
    {
        [$bundle] = $this->bundle();
        Bundle::factory()->create(['is_active' => false]);

        $this->getJson('/api/mobile/bundles')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.0.slug', $bundle->slug)
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/mobile/bundles/'.$bundle->slug)
            ->assertOk()
            ->assertJsonCount(2, 'data.courses');
    }

    private function bundle(int $price = 150000): array
    {
        $courses = collect([$this->course(), $this->course()]);
        $bundle = Bundle::factory()->create([
            'price' => $price,
            'is_active' => true,
        ]);
        $bundle->courses()->attach([
            $courses[0]->id => ['sort_order' => 0],
            $courses[1]->id => ['sort_order' => 1],
        ]);

        return [$bundle->fresh('courses'), $courses];
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

    private function configureGateway(): void
    {
        config([
            'midtrans.server_key' => 'test-server-key',
            'midtrans.methods.enabled' => false,
            'midtrans.fee.enabled' => false,
        ]);
        Http::fake([
            'https://app.sandbox.midtrans.com/snap/*' => Http::response([
                'token' => 'bundle-snap-token',
                'redirect_url' => 'https://example.test/pay-bundle',
            ]),
        ]);
    }

    private function enableCoupons(): void
    {
        $this->configureGateway();
        config(['shop.coupons_feature_enabled' => true]);
        CouponSetting::current()->update(['checkout_enabled' => true]);
    }
}
