<?php

namespace Tests\Feature;

use App\Models\Bundle;
use App\Models\Course;
use App\Models\FeatureSetting;
use App\Models\Order;
use App\Models\RefundSetting;
use App\Models\User;
use App\Services\Payment\OrderService;
use App\Services\Payment\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RefundPolicyConsentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('payment_queue.enabled', true);
        config()->set('midtrans.server_key', 'test-server-key');
        Queue::fake();
    }

    public function test_course_and_bundle_checkout_require_consent_even_from_direct_url(): void
    {
        FeatureSetting::current()->update(['bundles_enabled' => true]);
        $buyer = User::factory()->create();
        $course = Course::factory()->create(['status' => 'published', 'visibility' => 'catalog', 'price' => 99000]);
        $bundle = Bundle::factory()->create(['price' => 90000, 'is_active' => true]);
        $bundle->courses()->attach($course->id, ['sort_order' => 0]);

        $this->actingAs($buyer)->get(route('shop.show', $course))
            ->assertOk()->assertSee('refund_consent')
            ->assertSee('Saya memahami bahwa pembayaran yang telah berhasil tidak dapat dibatalkan atau dikembalikan (non-refundable), kecuali terjadi kendala dari pihak kami.')
            ->assertSee('Lihat Kebijakan Refund');
        $this->get(route('bundles.show', $bundle))
            ->assertOk()->assertSee('refund_consent');
        $this->get(route('shop.refund-policy'))->assertOk()
            ->assertSee('non-refundable')
            ->assertSee('Cara mengajukan refund')
            ->assertSee('100% nilai pesanan')
            ->assertSee('7 hari kalender sejak pembayaran berhasil')
            ->assertSee('admin@basstrainingacademy.com')
            ->assertSee('WhatsApp +62 821-1279-8728');

        $this->post(route('checkout.store', $course), ['method' => 'bank_transfer'])
            ->assertSessionHasErrors(['refund_consent', 'refund_policy_mode']);
        $this->post(route('checkout.bundle.store', $bundle), ['method' => 'bank_transfer'])
            ->assertSessionHasErrors(['refund_consent', 'refund_policy_mode']);
        $this->assertDatabaseCount('orders', 0);

        $this->post(route('checkout.store', $course), [
            'method' => 'bank_transfer', 'refund_consent' => '1', 'refund_policy_mode' => 'company_issue',
        ])->assertRedirect();
        $order = Order::firstOrFail();
        $this->assertSame('company_issue', $order->refund_policy_mode);
        $this->assertNotNull($order->refund_policy_accepted_at);

        $this->post(route('checkout.bundle.store', $bundle), [
            'method' => 'bank_transfer', 'refund_consent' => '1', 'refund_policy_mode' => 'company_issue',
        ])->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'bundle_id' => $bundle->id, 'refund_policy_mode' => 'company_issue',
        ]);
    }

    public function test_policy_switch_affects_new_orders_but_not_existing_orders(): void
    {
        $buyer = User::factory()->create();
        $first = Course::factory()->create(['status' => 'published', 'visibility' => 'catalog', 'price' => 99000]);
        $second = Course::factory()->create(['status' => 'published', 'visibility' => 'catalog', 'price' => 99000]);
        $firstOrder = app(OrderService::class)->checkout($first, $buyer, 'bank_transfer');

        RefundSetting::current()->update(['policy_mode' => 'seven_day', 'request_window_days' => 10, 'max_progress_percentage' => 20]);
        $secondOrder = app(OrderService::class)->checkout($second, $buyer, 'bank_transfer');

        $this->assertSame('company_issue', $firstOrder->fresh()->refund_policy_mode);
        $this->assertSame('seven_day', $secondOrder->refund_policy_mode);
        $this->assertSame(10, $secondOrder->refund_window_days);
        $this->assertSame(20, $secondOrder->refund_max_progress);
        $firstOrder->update(['status' => Order::STATUS_PAID, 'paid_at' => now()->subDays(5), 'payment_confirmed_at' => now()->subDays(5)]);
        $secondOrder->update(['status' => Order::STATUS_PAID, 'paid_at' => now()->subDays(20), 'payment_confirmed_at' => now()->subDays(20)]);
        $this->assertTrue(app(RefundService::class)->eligibility($firstOrder->fresh(), $buyer)['eligible']);
        $this->assertFalse(app(RefundService::class)->eligibility($secondOrder->fresh(), $buyer)['eligible']);
        $this->actingAs($buyer)->get(route('shop.refund-policy'))
            ->assertOk()->assertSee('10 hari')->assertSee('20%');
        $this->get(route('shop.show', $second))
            ->assertOk()
            ->assertSee('Saya memahami bahwa pembayaran yang telah berhasil tidak dapat dibatalkan atau dikembalikan (non-refundable), kecuali terjadi kendala dari pihak kami.');

        $this->post(route('checkout.store', $second), [
            'method' => 'bank_transfer', 'refund_consent' => '1', 'refund_policy_mode' => 'company_issue',
        ])->assertSessionHasErrors('refund_policy_mode');
    }

    public function test_admin_can_switch_policy_mode(): void
    {
        $admin = User::factory()->create();
        \Spatie\Permission\Models\Role::findOrCreate('super-admin', 'web');
        $admin->assignRole('super-admin');

        $this->actingAs($admin)->patch(route('admin.refunds.settings.update'), [
            'policy_mode' => 'seven_day',
            'request_window_days' => 7,
            'max_progress_percentage' => 30,
            'requests_enabled' => '1',
        ])->assertRedirect();

        $this->assertSame('seven_day', RefundSetting::current()->fresh()->policy_mode);
    }

    public function test_company_issue_order_uses_seven_calendar_days_from_payment_confirmation(): void
    {
        $buyer = User::factory()->create();
        $course = Course::factory()->create(['status' => 'published', 'visibility' => 'catalog', 'price' => 99000]);
        $base = [
            'user_id' => $buyer->id, 'course_id' => $course->id,
            'base_amount' => 99000, 'fee_amount' => 4000, 'amount' => 103000,
            'status' => Order::STATUS_PAID, 'paid_at' => now()->subDays(5),
            'payment_confirmed_at' => now()->subDays(5),
        ];
        $company = Order::create($base + ['order_code' => 'POLICY-COMPANY', 'refund_policy_mode' => 'company_issue']);
        $legacy = Order::create($base + ['order_code' => 'POLICY-LEGACY']);

        $service = app(RefundService::class);
        $this->assertTrue($service->eligibility($company, $buyer)['eligible']);
        $this->assertTrue($service->eligibility($legacy, $buyer)['eligible']);

        $this->actingAs($buyer)->get(route('checkout.finish', $company))
            ->assertOk()->assertSee('Laporkan kendala layanan')->assertDontSee('Batas pesanan ini');

        $this->post(route('refunds.store', $company), [
            'reason_type' => 'accidental_purchase', 'reason_other' => 'Kendala layanan yang dialami.',
        ])->assertSessionHasErrors('reason_type');
        $this->post(route('refunds.store', $company), [
            'reason_type' => 'technical_issue', 'reason_other' => 'Kendala layanan yang dialami.',
        ])->assertRedirect(route('checkout.finish', $company));
        $this->assertDatabaseHas('refunds', ['order_id' => $company->id, 'status' => 'requested']);

        $expired = Order::create($base + [
            'order_code' => 'POLICY-EXPIRED',
            'refund_policy_mode' => 'company_issue',
        ]);
        $expired->update(['payment_confirmed_at' => now()->subDays(8)]);
        $this->assertFalse($service->eligibility($expired, $buyer)['eligible']);
    }
}
