<?php

namespace Tests\Feature\Api;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MobileAccountDeletionApiTest extends TestCase
{
    use RefreshDatabase;

    private function mobileUser(): User
    {
        $user = User::factory()->create(['password' => 'correct-password']);
        $user->forceFill(['api_token' => 'delete-account-token'])->save();

        return $user;
    }

    public function test_deletion_requires_a_valid_bearer_token(): void
    {
        $this->deleteJson('/api/mobile/profile', ['password' => 'correct-password'])->assertUnauthorized();
        $this->withToken('invalid')->deleteJson('/api/mobile/profile', ['password' => 'correct-password'])
            ->assertUnauthorized();
    }

    public function test_missing_or_wrong_password_does_not_delete_the_account(): void
    {
        $user = $this->mobileUser();

        $this->withToken('delete-account-token')->deleteJson('/api/mobile/profile')
            ->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->deleteJson('/api/mobile/profile', ['password' => 'wrong-password'])
            ->assertUnprocessable()->assertJsonValidationErrors('password');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_deletion_removes_only_the_token_owner_and_revokes_access(): void
    {
        $user = $this->mobileUser();
        $other = User::factory()->create();
        $course = Course::factory()->create();
        $user->courses()->attach($course);
        $user->savedCourses()->attach($course);
        DB::table('sessions')->insert([
            'id' => 'delete-user-session', 'user_id' => $user->id,
            'payload' => '', 'last_activity' => time(),
        ]);
        DB::table('sessions')->insert([
            'id' => 'other-session', 'user_id' => $other->id,
            'payload' => '', 'last_activity' => time(),
        ]);
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email, 'token' => 'old-reset-token', 'created_at' => now(),
        ]);

        $this->withToken('delete-account-token')->deleteJson('/api/mobile/profile', [
            'password' => 'correct-password', 'user_id' => $other->id,
        ])->assertOk()->assertJsonPath('message', 'Akun Anda berhasil dihapus.');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('course_user', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('saved_courses', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('sessions', ['id' => 'delete-user-session']);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $this->assertDatabaseHas('users', ['id' => $other->id]);
        $this->assertDatabaseHas('sessions', ['id' => 'other-session']);
        $this->assertDatabaseHas('courses', ['id' => $course->id]);

        $this->getJson('/api/mobile/auth/me')->assertUnauthorized();
    }

    public function test_deletion_attempts_are_rate_limited(): void
    {
        $user = $this->mobileUser();

        for ($i = 0; $i < 5; $i++) {
            $this->withToken('delete-account-token')->deleteJson('/api/mobile/profile', ['password' => 'wrong'])
                ->assertUnprocessable();
        }

        $this->deleteJson('/api/mobile/profile', ['password' => 'correct-password'])->assertTooManyRequests();
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
}
