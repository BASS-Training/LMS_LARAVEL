<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use App\Notifications\AvpnVerificationStatusNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminParticipantAvpnToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_force_stop_avpn_access_for_participant(): void
    {
        Permission::findOrCreate('manage users');
        Role::findOrCreate('super-admin');

        $admin = User::factory()->create();
        $admin->givePermissionTo('manage users');
        $admin->assignRole('super-admin');

        $participant = User::factory()->create([
            'registration_program' => 'avpn_ai',
            'avpn_verification_status' => 'approved',
            'avpn_verified_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($admin)
            ->from('/admin/participants')
            ->post(route('admin.participants.access.force', $participant), [
                'access_mode' => 'avpn_blocked',
                'reason' => 'Akses AVPN dihentikan admin untuk pengujian.',
            ]);

        $response->assertRedirect('/admin/participants');

        $participant->refresh();

        $this->assertSame('rejected', $participant->avpn_verification_status);
        $this->assertSame('Akses AVPN dihentikan admin untuk pengujian.', $participant->avpn_rejection_reason);
    }

    public function test_admin_can_sync_legacy_avpn_users_from_existing_enrollment_history(): void
    {
        Permission::findOrCreate('manage users');
        Role::findOrCreate('super-admin');

        $admin = User::factory()->create();
        $admin->givePermissionTo('manage users');
        $admin->assignRole('super-admin');

        $legacyAvpnUser = User::factory()->create([
            'registration_program' => 'regular',
            'avpn_verification_status' => 'not_required',
        ]);

        $legacyRegularUser = User::factory()->create([
            'registration_program' => 'regular',
            'avpn_verification_status' => 'not_required',
        ]);

        $regularCourse = Course::factory()->create([
            'program_type' => 'regular',
        ]);

        $avpnCourse = Course::factory()->create([
            'program_type' => 'avpn_ai',
        ]);

        $regularCourse->participants()->attach([$legacyAvpnUser->id, $legacyRegularUser->id]);
        $avpnCourse->participants()->attach($legacyAvpnUser->id);

        $response = $this->actingAs($admin)
            ->from('/admin/participants')
            ->post(route('admin.participants.avpn.sync-legacy'));

        $response->assertRedirect('/admin/participants');

        $legacyAvpnUser->refresh();
        $legacyRegularUser->refresh();

        $this->assertSame('avpn_ai', $legacyAvpnUser->registration_program);
        $this->assertSame('approved', $legacyAvpnUser->avpn_verification_status);
        $this->assertNotNull($legacyAvpnUser->avpn_verified_at);
        $this->assertSame($admin->id, $legacyAvpnUser->avpn_verified_by);

        $this->assertSame('regular', $legacyRegularUser->registration_program);
        $this->assertSame('not_required', $legacyRegularUser->avpn_verification_status);
    }

    public function test_admin_avpn_actions_email_only_participants_transitioned_from_pending(): void
    {
        Notification::fake();
        Permission::findOrCreate('manage users');
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->givePermissionTo('manage users');
        $admin->assignRole('super-admin');
        $pending = User::factory()->create(['avpn_verification_status' => 'pending']);
        $alreadyApproved = User::factory()->create(['avpn_verification_status' => 'approved']);

        $this->actingAs($admin)
            ->post(route('admin.participants.avpn.batch-reject'), [
                'participant_ids' => [$pending->id, $alreadyApproved->id, $pending->id],
                'reason' => 'Dokumen perlu diperbarui.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', fn ($message) => str_contains($message, '1 peserta dilewati'));

        $this->assertSame('rejected', $pending->fresh()->avpn_verification_status);
        $this->assertSame('Dokumen perlu diperbarui.', $pending->fresh()->avpn_rejection_reason);
        Notification::assertSentToTimes($pending, AvpnVerificationStatusNotification::class, 1);
        Notification::assertNotSentTo($alreadyApproved, AvpnVerificationStatusNotification::class);

        $this->actingAs($admin)
            ->post(route('admin.participants.avpn.reject', $pending), [
                'reason' => 'Percobaan kedua.',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        Notification::assertSentToTimes($pending, AvpnVerificationStatusNotification::class, 1);
    }
}
