<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'date_of_birth' => '2000-01-01',
                'gender' => 'male',
                'institution_name' => 'Test Institute',
                'occupation' => 'Pelajar/Mahasiswa',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
                'date_of_birth' => '2000-01-01',
                'gender' => 'male',
                'institution_name' => 'Test Institute',
                'occupation' => 'Pelajar/Mahasiswa',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_regular_user_can_request_avpn_verification(): void
    {
        $user = User::factory()->create([
            'registration_program' => 'regular',
            'avpn_verification_status' => 'not_required',
        ]);

        $response = $this
            ->actingAs($user)
            ->from('/dashboard')
            ->post('/profile/avpn-verification/request');

        $response->assertRedirect('/dashboard');

        $user->refresh();
        $this->assertSame('pending', $user->avpn_verification_status);
        $this->assertNotNull($user->avpn_google_form_submitted_at);
    }

    public function test_pending_user_cannot_submit_duplicate_avpn_verification_request(): void
    {
        $user = User::factory()->create([
            'registration_program' => 'regular',
            'avpn_verification_status' => 'pending',
            'avpn_google_form_submitted_at' => now()->subHour(),
        ]);

        $response = $this
            ->actingAs($user)
            ->from('/dashboard')
            ->post('/profile/avpn-verification/request');

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('info');

        $user->refresh();
        $this->assertSame('pending', $user->avpn_verification_status);
    }
}
