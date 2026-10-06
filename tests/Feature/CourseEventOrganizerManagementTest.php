<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CourseEventOrganizerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('manage all courses');
        Permission::findOrCreate('manage own courses');
        Permission::findOrCreate('assign event organizers');
        Permission::findOrCreate('view progress reports');
        Role::findOrCreate('event-organizer');
    }

    public function test_only_event_organizers_are_listed_as_available_candidates(): void
    {
        $admin = $this->createCourseManager();
        $organizer = User::factory()->create();
        $organizer->assignRole('event-organizer');
        $nonOrganizer = User::factory()->create();
        $nonOrganizer->givePermissionTo('view progress reports');
        $course = Course::factory()->create();

        $response = $this->actingAs($admin)->get(route('courses.show', $course));

        $response->assertOk();
        $response->assertSee($organizer->name);
        $response->assertDontSee($nonOrganizer->name);
    }

    public function test_event_organizer_can_be_added_to_course(): void
    {
        $admin = $this->createCourseManager();
        $organizer = User::factory()->create();
        $organizer->assignRole('event-organizer');
        $course = Course::factory()->create();

        $response = $this->actingAs($admin)
            ->from(route('courses.show', $course))
            ->post(route('courses.addEo', $course), [
                'user_ids' => [$organizer->id],
            ]);

        $response->assertRedirect(route('courses.show', $course));
        $response->assertSessionHas('success', 'Event Organizer berhasil ditambahkan.');
        $this->assertDatabaseHas('course_event_organizer', [
            'course_id' => $course->id,
            'user_id' => $organizer->id,
        ]);
    }

    public function test_user_without_event_organizer_role_cannot_be_added(): void
    {
        $admin = $this->createCourseManager();
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $response = $this->actingAs($admin)
            ->from(route('courses.show', $course))
            ->post(route('courses.addEo', $course), [
                'user_ids' => [$user->id],
            ]);

        $response->assertRedirect(route('courses.show', $course));
        $response->assertSessionHasErrors('user_ids');
        $response->assertSessionMissing('success');
        $this->assertDatabaseMissing('course_event_organizer', [
            'course_id' => $course->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_assigned_event_organizer_can_be_removed_from_course(): void
    {
        $admin = $this->createCourseManager();
        $organizer = User::factory()->create();
        $organizer->assignRole('event-organizer');
        $course = Course::factory()->create();
        $course->eventOrganizers()->attach($organizer);

        $response = $this->actingAs($admin)
            ->from(route('courses.show', $course))
            ->delete(route('courses.removeEo', $course), [
                'user_ids' => [$organizer->id],
            ]);

        $response->assertRedirect(route('courses.show', $course));
        $response->assertSessionHas('success', 'Event Organizer berhasil dihapus.');
        $this->assertDatabaseMissing('course_event_organizer', [
            'course_id' => $course->id,
            'user_id' => $organizer->id,
        ]);
    }

    public function test_seeded_event_organizer_has_the_correct_role(): void
    {
        $this->seed([
            \Database\Seeders\RolesAndPermissionsSeeder::class,
            \Database\Seeders\UserSeeder::class,
        ]);

        $organizer = User::where('email', 'eo@example.com')->firstOrFail();

        $this->assertTrue($organizer->hasRole('event-organizer'));
    }

    private function createCourseManager(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['manage all courses', 'assign event organizers']);

        return $user;
    }
}
