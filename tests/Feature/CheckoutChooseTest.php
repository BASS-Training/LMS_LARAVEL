<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutChooseTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_can_open_payment_method_page_for_paid_catalog_course(): void
    {
        config(['midtrans.server_key' => 'test-server-key']);

        $participant = User::factory()->create([
            'email_verified_at' => now(),
            'email_verification_optional' => true,
        ]);
        $course = Course::factory()->create([
            'title' => 'Kursus Checkout',
            'status' => 'published',
            'visibility' => 'catalog',
            'price' => 99000,
            'program_type' => 'regular',
        ]);

        $response = $this->actingAs($participant)
            ->get(route('checkout.choose', $course));

        $response
            ->assertOk()
            ->assertSee('Metode pembayaran')
            ->assertSee('QRIS')
            ->assertSee('Transfer Bank')
            ->assertSee('Kursus Checkout')
            ->assertDontSee('options: {"qris"', false);
    }
}
