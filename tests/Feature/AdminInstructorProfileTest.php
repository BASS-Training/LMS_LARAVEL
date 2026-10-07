<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminInstructorProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_manager_can_update_instructor_avatar_and_bio(): void
    {
        Storage::fake('public');
        Permission::findOrCreate('manage users');
        Role::findOrCreate('instructor');

        $manager = User::factory()->create();
        $manager->givePermissionTo('manage users');

        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        $response = $this->actingAs($manager)->put(route('admin.users.update', $instructor), [
            'name' => 'Instruktur Admin',
            'email' => $instructor->email,
            'roles' => ['instructor'],
            'avatar' => UploadedFile::fake()->image('admin.jpg', 800, 800),
            'instructor_bio' => '<p>Pengajar <strong>berpengalaman</strong>.</p><script>alert(1)</script>',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect(route('admin.users.index'));

        $instructor->refresh();
        Storage::disk('public')->assertExists($instructor->avatar);
        $this->assertSame('Instruktur Admin', $instructor->name);
        $this->assertStringContainsString('<strong>berpengalaman</strong>', $instructor->instructor_bio);
        $this->assertStringNotContainsString('<script', $instructor->instructor_bio);
    }
}
