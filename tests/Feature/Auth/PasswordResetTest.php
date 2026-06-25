<?php

namespace Tests\Feature\Auth;

use App\Mail\OtpMail;
use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_otp_can_be_requested(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $response = $this->post('/forgot-password', ['email' => $user->email]);

        $response->assertRedirect(route('password.otp'));
        Mail::assertSent(OtpMail::class);
        $this->assertDatabaseHas('email_otps', [
            'email' => strtolower($user->email),
            'purpose' => EmailOtp::PURPOSE_PASSWORD_RESET,
        ]);
    }

    public function test_reset_password_otp_screen_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $response = $this->withSession(['reset_email' => $user->email])
            ->get('/reset-password-otp');

        $response->assertStatus(200);
    }

    public function test_password_can_be_reset_with_valid_otp(): void
    {
        $user = User::factory()->create();

        EmailOtp::create([
            'email' => strtolower($user->email),
            'purpose' => EmailOtp::PURPOSE_PASSWORD_RESET,
            'code' => Hash::make('123456'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->post('/reset-password-otp', [
            'email' => $user->email,
            'code' => '123456',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    public function test_password_is_not_reset_with_invalid_otp(): void
    {
        $user = User::factory()->create();
        $original = $user->password;

        EmailOtp::create([
            'email' => strtolower($user->email),
            'purpose' => EmailOtp::PURPOSE_PASSWORD_RESET,
            'code' => Hash::make('123456'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->post('/reset-password-otp', [
            'email' => $user->email,
            'code' => '000000',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertSame($original, $user->fresh()->password);
    }
}
