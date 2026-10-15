<?php

namespace Tests\Feature\Auth;

use App\Jobs\LogActivityJob;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LoginActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private function participant(): User
    {
        Role::findOrCreate('participant');
        $user = User::factory()->create();
        $user->assignRole('participant');

        return $user;
    }

    public function test_successful_participant_web_login_is_recorded_once_without_credentials(): void
    {
        $user = $this->participant();
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])
            ->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/dashboard');

        $logs = ActivityLog::where('action', 'auth.login')->get();
        $this->assertCount(1, $logs);
        $this->assertSame($user->id, $logs[0]->user_id);
        $this->assertSame('192.0.2.10', $logs[0]->ip_address);
        $this->assertSame(['source' => 'web'], $logs[0]->metadata);
        $this->assertSame('success', $logs[0]->status);
        $this->assertNotNull($logs[0]->created_at);
    }

    public function test_failed_participant_web_login_is_not_recorded(): void
    {
        $user = $this->participant();
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('activity_logs', ['action' => 'auth.login']);
    }

    public function test_successful_mobile_login_records_the_user_without_session_authentication(): void
    {
        $user = $this->participant();
        $this->postJson('/api/mobile/auth/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk();

        $logs = ActivityLog::where('action', 'auth.login')->get();
        $this->assertCount(1, $logs);
        $this->assertSame($user->id, $logs[0]->user_id);
        $this->assertSame(['source' => 'mobile'], $logs[0]->metadata);
        $this->assertStringNotContainsString($user->refresh()->api_token, $logs[0]->toJson());
    }

    public function test_failed_mobile_login_is_not_recorded(): void
    {
        $user = $this->participant();
        $this->postJson('/api/mobile/auth/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertUnprocessable();
        $this->assertDatabaseMissing('activity_logs', ['action' => 'auth.login']);
    }

    public function test_other_participant_activity_is_still_skipped_in_direct_and_queued_logs(): void
    {
        $user = $this->participant();
        $this->actingAs($user);
        $this->assertNull(ActivityLog::log('view', ['description' => 'Course viewed']));
        (new LogActivityJob($user->id, ['participant'], 'http_post', []))->handle();
        $this->assertDatabaseCount('activity_logs', 0);

        (new LogActivityJob($user->id, ['participant'], 'auth.login', [
            'metadata' => ['source' => 'web'],
        ]))->handle();
        $this->assertDatabaseCount('activity_logs', 1);
    }

    public function test_admin_can_filter_participant_logins_on_the_activity_page(): void
    {
        $user = $this->participant();
        ActivityLog::log('auth.login', ['metadata' => ['source' => 'web']], $user);
        $other = $this->participant();
        ActivityLog::log('auth.login', ['metadata' => ['source' => 'mobile']], $other);

        Permission::findOrCreate('view activity logs');
        $admin = User::factory()->create();
        $admin->givePermissionTo('view activity logs');
        $this->actingAs($admin)->get('/activity-logs?category=auth&user_id='.$user->id)
            ->assertOk()
            ->assertViewHas('users', fn ($users) => $users->contains('id', $user->id))
            ->assertViewHas('logs', fn ($logs) => $logs->count() === 1 && $logs->first()->user_id === $user->id);
    }
}
