<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminManualEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('manage users');
        Role::findOrCreate('super-admin');
    }

    public function test_super_admin_can_manually_verify_an_unverified_email(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($admin)->post(route('admin.users.verify-email', $user));

        $response->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');
        $this->assertNotNull($user->fresh()->email_verified_at);

        $log = ActivityLog::query()
            ->where('action', 'user_email_verified_manually')
            ->where('user_id', $admin->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($user->id, $log->metadata['user_id']);
        $this->assertSame($user->email, $log->metadata['user_email']);
        $this->assertSame($admin->id, $log->metadata['verified_by']);
    }

    public function test_verifying_an_already_verified_email_does_not_change_its_timestamp(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $verifiedAt = now()->subDay()->startOfSecond();
        $user = User::factory()->create(['email_verified_at' => $verifiedAt]);

        $response = $this->actingAs($admin)->post(route('admin.users.verify-email', $user));

        $response->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('info');
        $this->assertTrue($user->fresh()->email_verified_at->equalTo($verifiedAt));
        $this->assertDatabaseMissing('activity_logs', [
            'action' => 'user_email_verified_manually',
            'user_id' => $admin->id,
        ]);
    }

    public function test_user_manager_who_is_not_super_admin_cannot_verify_email_manually(): void
    {
        $manager = User::factory()->create();
        $manager->givePermissionTo('manage users');
        $user = User::factory()->unverified()->create();

        $this->actingAs($manager)
            ->post(route('admin.users.verify-email', $user))
            ->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_verification_button_is_only_shown_to_super_admin_for_unverified_users(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $unverified = User::factory()->unverified()->create();
        User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Belum terverifikasi')
            ->assertSee('Terverifikasi')
            ->assertSee(route('admin.users.verify-email', $unverified), false);

        $manager = User::factory()->create();
        $manager->givePermissionTo('manage users');

        $this->actingAs($manager)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertDontSee(route('admin.users.verify-email', $unverified), false);
    }
}
