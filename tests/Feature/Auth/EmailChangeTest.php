<?php

namespace Tests\Feature\Auth;

use App\Mail\OtpMail;
use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_change_email_otp_is_sent_to_the_new_address(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('/email/change/send-otp', ['new_email' => 'baru@example.com']);

        $response->assertRedirect(route('email.change.otp'));
        Mail::assertSent(OtpMail::class);
        $this->assertDatabaseHas('email_otps', [
            'email' => 'baru@example.com',
            'purpose' => EmailOtp::PURPOSE_EMAIL_CHANGE,
        ]);

        // Email akun BELUM berubah sebelum OTP dikonfirmasi.
        $this->assertSame($user->email, $user->fresh()->email);
    }

    public function test_email_is_changed_and_verified_with_valid_otp(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        EmailOtp::create([
            'email' => 'baru@example.com',
            'purpose' => EmailOtp::PURPOSE_EMAIL_CHANGE,
            'code' => Hash::make('123456'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['pending_new_email' => 'baru@example.com'])
            ->post('/email/change', [
                'new_email' => 'baru@example.com',
                'code' => '123456',
            ]);

        $response->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'));

        $fresh = $user->fresh();
        $this->assertSame('baru@example.com', $fresh->email);
        $this->assertNotNull($fresh->email_verified_at);
    }

    public function test_email_is_not_changed_with_invalid_otp(): void
    {
        $user = User::factory()->create();
        $originalEmail = $user->email;

        EmailOtp::create([
            'email' => 'baru@example.com',
            'purpose' => EmailOtp::PURPOSE_EMAIL_CHANGE,
            'code' => Hash::make('123456'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->actingAs($user)
            ->post('/email/change', [
                'new_email' => 'baru@example.com',
                'code' => '000000',
            ]);

        $response->assertSessionHasErrors('code');
        $this->assertSame($originalEmail, $user->fresh()->email);
    }

    public function test_cannot_change_to_an_email_already_in_use(): void
    {
        Mail::fake();

        $taken = User::factory()->create(['email' => 'dipakai@example.com']);
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('/email/change/send-otp', ['new_email' => $taken->email]);

        $response->assertSessionHasErrors('new_email');
        Mail::assertNothingSent();
    }
}
