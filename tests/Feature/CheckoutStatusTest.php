<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_awaiting_verification_page_shows_completed_payment_step(): void
    {
        $participant = User::factory()->create();
        $course = Course::factory()->create([
            'status' => 'published',
            'visibility' => 'catalog',
            'price' => 99000,
            'requires_payment_verification' => true,
        ]);
        $order = Order::create([
            'user_id' => $participant->id,
            'course_id' => $course->id,
            'order_code' => 'BASS-TEST-VERIFY',
            'invoice_number' => 'INV/TEST/0001',
            'base_amount' => 99000,
            'fee_amount' => 4000,
            'amount' => 103000,
            'status' => 'awaiting_verification',
            'payment_method_key' => 'bank_transfer',
            'payment_confirmed_at' => now(),
        ]);

        $response = $this->actingAs($participant)
            ->get(route('checkout.finish', $order));

        $response
            ->assertOk()
            ->assertSee('Pembayaran diterima')
            ->assertSee('menunggu verifikasi')
            ->assertSee('bg-success text-white', false)
            ->assertDontSee('bg-success-soft0', false);
    }
}
