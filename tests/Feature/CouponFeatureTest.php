<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\CouponSetting;
use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use App\Services\Payment\CouponService;
use App\Services\Payment\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CouponFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_coupon_checkout_is_disabled_by_default_and_rejects_direct_use(): void
    {
        [$user, $course] = $this->buyerAndCourse();
        $coupon = Coupon::factory()->create(['code' => 'OFF10']);

        $this->actingAs($user)
            ->get(route('checkout.choose', $course))
            ->assertOk()
            ->assertDontSee('Punya kode kupon?');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Fitur kupon sedang tidak tersedia');

        app(CouponService::class)->quote($coupon->code, (int) $course->price, $course, $user);
    }

    public function test_admin_toggle_requires_permission_and_server_kill_switch(): void
    {
        config(['shop.coupons_feature_enabled' => false]);
        Permission::findOrCreate('manage coupons', 'web');
        $admin = User::factory()->create();
        $admin->givePermissionTo('manage coupons');
        $regular = User::factory()->create();

        $this->actingAs($regular)
            ->patch(route('admin.coupons.checkout.update'), ['checkout_enabled' => 1])
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch(route('admin.coupons.checkout.update'), ['checkout_enabled' => 1])
            ->assertSessionHasErrors('coupon');

        $this->assertFalse(CouponSetting::current()->checkout_enabled);

        config(['shop.coupons_feature_enabled' => true]);
        $this->actingAs($admin)
            ->patch(route('admin.coupons.checkout.update'), ['checkout_enabled' => 1])
            ->assertSessionHasNoErrors();

        $this->assertTrue(CouponSetting::current()->fresh()->checkout_enabled);
    }

    public function test_percentage_coupon_is_normalized_reserved_and_fee_uses_discounted_base(): void
    {
        $this->enableCoupons();
        config([
            'midtrans.fee.enabled' => true,
            'midtrans.fee.percent' => 10,
            'midtrans.fee.fixed' => 0,
            'midtrans.fee.rounding' => 1,
        ]);
        [$user, $course] = $this->buyerAndCourse(100000);
        $coupon = Coupon::factory()->create([
            'code' => 'SAVE10',
            'discount_type' => Coupon::TYPE_PERCENTAGE,
            'discount_value' => 10,
        ]);

        $order = app(OrderService::class)->checkout($course, $user, null, ' save10 ');

        $this->assertSame('SAVE10', $order->coupon_code);
        $this->assertSame(10000, $order->discount_amount);
        $this->assertSame(90000, $order->base_amount);
        $this->assertSame(9000, $order->fee_amount);
        $this->assertSame(99000, $order->amount);
        $this->assertDatabaseHas('coupon_redemptions', [
            'coupon_id' => $coupon->id,
            'order_id' => $order->id,
            'user_id' => $user->id,
            'discount_amount' => 10000,
        ]);

        Http::assertSent(fn ($request) => (int) $request['transaction_details']['gross_amount'] === 99000
            && collect($request['item_details'])->sum(fn ($item) => $item['price'] * $item['quantity']) === 99000);
    }

    public function test_coupon_can_be_limited_to_selected_courses(): void
    {
        $this->enableCoupons();
        [$user, $allowed] = $this->buyerAndCourse();
        $blocked = $this->course(150000);
        $coupon = Coupon::factory()->create([
            'code' => 'COURSEONLY',
            'applies_to_all_courses' => false,
        ]);
        $coupon->courses()->attach($allowed);

        $quote = app(CouponService::class)->quote('courseonly', (int) $allowed->price, $allowed, $user);
        $this->assertGreaterThan(0, $quote['discount']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('tidak berlaku untuk kursus');
        app(CouponService::class)->quote('COURSEONLY', (int) $blocked->price, $blocked, $user);
    }

    public function test_coupon_that_makes_total_zero_is_rejected(): void
    {
        $this->enableCoupons();
        [$user, $course] = $this->buyerAndCourse(100000);
        Coupon::factory()->create([
            'code' => 'FREE',
            'discount_type' => Coupon::TYPE_FIXED,
            'discount_value' => 100000,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('total harus lebih dari Rp0');
        app(CouponService::class)->quote('FREE', 100000, $course, $user);
    }

    public function test_expired_unpaid_order_releases_coupon_reservation(): void
    {
        $this->enableCoupons();
        [$user, $course] = $this->buyerAndCourse();
        Coupon::factory()->create(['code' => 'RELEASE']);
        $order = app(OrderService::class)->checkout($course, $user, null, 'RELEASE');
        $order->update(['expires_at' => now()->subMinute()]);

        $this->assertTrue(app(OrderService::class)->expirePending($order->fresh()));
        $this->assertSame(Order::STATUS_EXPIRED, $order->fresh()->status);
        $this->assertDatabaseMissing('coupon_redemptions', ['order_id' => $order->id]);
    }

    public function test_confirmed_cancellation_releases_coupon_reservation(): void
    {
        $this->enableCoupons(false);
        [$user, $course] = $this->buyerAndCourse();
        Coupon::factory()->create(['code' => 'CANCEL10']);

        $statusChecks = 0;
        $orderCode = null;
        Http::fake(function ($request) use (&$statusChecks, &$orderCode) {
            if (str_contains($request->url(), '/snap/')) {
                return Http::response([
                    'token' => 'snap-test-token',
                    'redirect_url' => 'https://example.test/pay',
                ]);
            }

            if (str_contains($request->url(), '/status')) {
                $statusChecks++;

                return Http::response([
                    'order_id' => $orderCode,
                    'transaction_status' => $statusChecks === 1 ? 'pending' : 'cancel',
                    'status_code' => $statusChecks === 1 ? '201' : '200',
                ], $statusChecks === 1 ? 201 : 200);
            }

            return Http::response(['status_code' => '500'], 500);
        });

        $order = app(OrderService::class)->checkout($course, $user, null, 'CANCEL10');
        $orderCode = $order->order_code;

        $orders = app(OrderService::class);
        $result = $orders->cancelPending($order, $user, 'Tidak jadi membeli.');

        $this->assertSame(Order::STATUS_CANCELLED, $result->status);
        $this->assertDatabaseMissing('coupon_redemptions', ['order_id' => $order->id]);
    }

    public function test_global_and_per_user_limits_include_reserved_orders(): void
    {
        $this->enableCoupons();
        [$firstUser, $course] = $this->buyerAndCourse();
        $secondUser = User::factory()->create();
        Coupon::factory()->create([
            'code' => 'LIMITED',
            'usage_limit' => 1,
            'per_user_limit' => 1,
        ]);
        app(OrderService::class)->checkout($course, $firstUser, null, 'LIMITED');

        try {
            app(CouponService::class)->quote('LIMITED', (int) $course->price, $course, $secondUser);
            $this->fail('Kuota global seharusnya menolak quote kedua.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Kuota penggunaan', $exception->getMessage());
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('batas penggunaan');
        $coupon = Coupon::where('code', 'LIMITED')->firstOrFail();
        $coupon->update(['usage_limit' => null]);
        app(CouponService::class)->quote('LIMITED', (int) $course->price, $course, $firstUser);
    }

    public function test_paid_order_keeps_coupon_redemption_even_after_feature_is_disabled(): void
    {
        $this->enableCoupons();
        [$user, $course] = $this->buyerAndCourse();
        Coupon::factory()->create(['code' => 'PAID10']);
        $order = app(OrderService::class)->checkout($course, $user, null, 'PAID10');

        config(['shop.coupons_feature_enabled' => false]);
        app(OrderService::class)->applyPaymentStatus($order, [
            'transaction_status' => 'settlement',
            'gross_amount' => (string) $order->amount,
            'currency' => 'IDR',
        ]);

        $this->assertSame(Order::STATUS_PAID, $order->fresh()->status);
        $this->assertDatabaseHas('coupon_redemptions', ['order_id' => $order->id]);
    }

    public function test_existing_payable_coupon_order_is_reused_after_coupon_is_deactivated(): void
    {
        $this->enableCoupons();
        [$user, $course] = $this->buyerAndCourse();
        $coupon = Coupon::factory()->create(['code' => 'SNAPSHOT']);
        $first = app(OrderService::class)->checkout($course, $user, null, 'SNAPSHOT');
        $coupon->update(['is_active' => false]);

        $second = app(OrderService::class)->checkout($course, $user, null, 'SNAPSHOT');

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('coupon_redemptions', 1);
    }

    public function test_participant_can_apply_and_remove_coupon_on_checkout(): void
    {
        $this->enableCoupons();
        [$user, $course] = $this->buyerAndCourse();
        Coupon::factory()->create(['code' => 'WEB10']);

        $this->actingAs($user)
            ->post(route('checkout.coupon.apply', $course), ['coupon_code' => 'web10'])
            ->assertRedirect(route('checkout.choose', $course));

        $this->actingAs($user)
            ->get(route('checkout.choose', $course))
            ->assertOk()
            ->assertSee('Kupon WEB10 aktif')
            ->assertSee('Hapus Kupon');

        $this->actingAs($user)
            ->delete(route('checkout.coupon.remove', $course))
            ->assertRedirect(route('checkout.choose', $course));

        $this->actingAs($user)
            ->get(route('checkout.choose', $course))
            ->assertSee('Punya kode kupon?');
    }

    public function test_coupon_manager_can_create_course_specific_coupon_and_used_coupon_cannot_be_deleted(): void
    {
        $this->enableCoupons();
        Permission::findOrCreate('manage coupons', 'web');
        $admin = User::factory()->create();
        $admin->givePermissionTo('manage coupons');
        [$buyer, $course] = $this->buyerAndCourse();

        $this->actingAs($admin)->post(route('admin.coupons.store'), [
            'code' => 'course20',
            'discount_type' => Coupon::TYPE_PERCENTAGE,
            'discount_value' => 20,
            'per_user_limit' => 1,
            'is_active' => 1,
            'applies_to_all_courses' => 0,
            'course_ids' => [$course->id],
        ])->assertRedirect(route('admin.coupons.index'));

        $coupon = Coupon::where('code', 'COURSE20')->firstOrFail();
        $this->assertDatabaseHas('coupon_course', ['coupon_id' => $coupon->id, 'course_id' => $course->id]);

        $order = app(OrderService::class)->checkout($course, $buyer, null, $coupon->code);
        $this->actingAs($admin)
            ->delete(route('admin.coupons.destroy', $coupon))
            ->assertSessionHasErrors('coupon');

        $this->assertDatabaseHas('coupons', ['id' => $coupon->id]);
        $this->assertDatabaseHas('coupon_redemptions', ['order_id' => $order->id]);
    }

    public function test_coupon_manager_pages_render(): void
    {
        config(['shop.coupons_feature_enabled' => true]);
        Permission::findOrCreate('manage coupons', 'web');
        $admin = User::factory()->create();
        $admin->givePermissionTo('manage coupons');
        $coupon = Coupon::factory()->create(['code' => 'RENDER10']);

        $this->actingAs($admin)
            ->get(route('admin.coupons.index'))
            ->assertOk()
            ->assertSee('Manajemen Kupon')
            ->assertSee('RENDER10');

        $this->actingAs($admin)
            ->get(route('admin.coupons.create'))
            ->assertOk()
            ->assertSee('Buat Kupon');

        $this->actingAs($admin)
            ->get(route('admin.coupons.edit', $coupon))
            ->assertOk()
            ->assertSee('Edit Kupon RENDER10');
    }

    private function enableCoupons(bool $fakeGateway = true): void
    {
        config([
            'shop.coupons_feature_enabled' => true,
            'midtrans.server_key' => 'test-server-key',
            'midtrans.methods.enabled' => false,
            'midtrans.fee.enabled' => false,
        ]);
        CouponSetting::current()->update(['checkout_enabled' => true]);
        if ($fakeGateway) {
            Http::fake(fn () => Http::response([
                'token' => 'snap-test-token',
                'redirect_url' => 'https://example.test/pay',
            ]));
        }
    }

    private function buyerAndCourse(int $price = 100000): array
    {
        return [User::factory()->create(), $this->course($price)];
    }

    private function course(int $price = 100000): Course
    {
        return Course::factory()->create([
            'status' => 'published',
            'visibility' => 'catalog',
            'price' => $price,
            'program_type' => 'regular',
        ]);
    }
}
