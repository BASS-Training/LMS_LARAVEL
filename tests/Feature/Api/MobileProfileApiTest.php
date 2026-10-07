<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MobileProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_can_update_avatar_and_sanitized_bio(): void
    {
        Storage::fake('public');
        Permission::findOrCreate('manage own courses');
        Role::findOrCreate('instructor');

        $user = User::factory()->create(['api_token' => 'profile-api-token']);
        $user->assignRole('instructor');
        $user->givePermissionTo('manage own courses');

        $response = $this->withToken('profile-api-token')->post('/api/mobile/profile', [
            'name' => 'Mobile Instructor',
            'date_of_birth' => '1995-06-15',
            'gender' => 'female',
            'institution_name' => 'BASS Academy',
            'occupation' => 'Karyawan Swasta',
            'avatar' => UploadedFile::fake()->image('mobile.webp', 600, 600),
            'instructor_bio' => '<p>Ahli <em>fingerstyle</em>.</p><img src=x onerror=alert(1)><script>alert(1)</script>',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.name', 'Mobile Instructor')
            ->assertJsonPath('data.user.instructor_bio', '<p>Ahli <em>fingerstyle</em>.</p>');

        $user->refresh();
        Storage::disk('public')->assertExists($user->avatar);
        $this->assertStringNotContainsString('<script', $user->instructor_bio);
        $this->assertStringNotContainsString('<img', $user->instructor_bio);
    }
}
