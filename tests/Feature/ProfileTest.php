<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
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

    public function test_instructor_can_update_public_bio_and_avatar_safely(): void
    {
        Storage::fake('public');
        Permission::findOrCreate('manage own courses');
        Role::findOrCreate('instructor');

        $user = User::factory()->create();
        $user->assignRole('instructor');
        $user->givePermissionTo('manage own courses');

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'date_of_birth' => '2000-01-01',
            'gender' => 'male',
            'institution_name' => 'BASS Institute',
            'occupation' => 'Karyawan Swasta',
            'avatar' => UploadedFile::fake()->image('instructor.jpg', 600, 600),
            'instructor_bio' => '<div><span>Pengajar <strong>bass</strong></span></div><script>alert(1)</script><a href="javascript:alert(1)">Profil</a>',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect('/profile');

        $user->refresh();
        Storage::disk('public')->assertExists($user->avatar);
        $this->assertStringContainsString('<strong>bass</strong>', $user->instructor_bio);
        $this->assertStringNotContainsString('<script', $user->instructor_bio);
        $this->assertStringNotContainsString('javascript:', $user->instructor_bio);
    }

    public function test_replacing_and_removing_avatar_cleans_up_old_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/old.jpg', 'old-avatar');

        $user = User::factory()->create(['avatar' => 'avatars/old.jpg']);
        $profile = [
            'name' => $user->name,
            'email' => $user->email,
            'date_of_birth' => '2000-01-01',
            'gender' => 'female',
            'institution_name' => 'BASS Institute',
            'occupation' => 'Wiraswasta',
        ];

        $this->actingAs($user)->patch('/profile', $profile + [
            'avatar' => UploadedFile::fake()->image('new.png', 500, 500),
        ])->assertSessionHasNoErrors();

        $newAvatar = $user->refresh()->avatar;
        Storage::disk('public')->assertMissing('avatars/old.jpg');
        Storage::disk('public')->assertExists($newAvatar);

        $this->actingAs($user)->patch('/profile', $profile + ['remove_avatar' => true])
            ->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing($newAvatar);
        $this->assertNull($user->refresh()->avatar);
    }

    public function test_participant_cannot_set_an_instructor_bio(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'date_of_birth' => '2000-01-01',
            'gender' => 'male',
            'institution_name' => 'BASS Institute',
            'occupation' => 'Pelajar/Mahasiswa',
            'instructor_bio' => '<p>Tidak boleh tampil</p>',
        ])->assertSessionHasErrors('instructor_bio');

        $this->assertNull($user->refresh()->instructor_bio);
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
