<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CourseTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_can_be_created(): void
    {
        $course = Course::factory()->create();
        $instructor = User::factory()->create();

        $course->instructors()->attach($instructor->id);

        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }

    public function test_user_can_enroll_in_course(): void
    {
        $instructor = User::factory()->create();
        $course = Course::factory()->create(['status' => 'published']);
        $course->instructors()->attach($instructor->id);
        $user = User::factory()->create();

        $course->enrolledUsers()->attach($user->id);

        $this->assertTrue($course->refresh()->enrolledUsers->contains($user));
    }

    public function test_enrolled_user_can_access_course_content(): void
    {
        $instructor = User::factory()->create();
        $user = User::factory()->create();
        $course = Course::factory()->create(['status' => 'published']);
        $course->instructors()->attach($instructor->id);
        $lesson = Lesson::factory()->for($course)->create(['order' => 1]);
        $content = Content::factory()->for($lesson)->create(['order' => 1]);

        $course->enrolledUsers()->attach($user->id);

        $response = $this->actingAs($user)->get(route('contents.show', $content));

        $response->assertOk();
    }

    public function test_course_can_be_updated_via_endpoint(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::create(['name' => 'manage all courses']);
        $user = User::factory()->create();
        $user->givePermissionTo('manage all courses');

        $course = Course::factory()->create();

        $data = [
            'title' => 'Updated Course',
            'description' => 'Updated description',
            'objectives' => 'Updated objectives',
            'status' => 'published',
            'program_type' => 'regular',
        ];

        $response = $this->actingAs($user)->patch(route('courses.update', $course), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('courses', array_merge(['id' => $course->id], $data));
    }

    public function test_course_can_be_deleted_via_endpoint(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::create(['name' => 'manage all courses']);
        $user = User::factory()->create();
        $user->givePermissionTo('manage all courses');

        $course = Course::factory()->create();

        $response = $this->actingAs($user)->delete(route('courses.destroy', $course));

        $response->assertRedirect();
        $this->assertModelMissing($course);
    }

    public function test_academic_course_endpoint_cannot_change_commerce_configuration(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('manage all courses');
        $user = User::factory()->create();
        $user->givePermissionTo('manage all courses');
        $course = Course::factory()->create([
            'visibility' => 'private',
            'price' => null,
        ]);

        $this->actingAs($user)->patch(route('courses.update', $course), [
            'title' => 'Course Akademik',
            'status' => 'published',
            'program_type' => 'regular',
            'visibility' => 'catalog',
            'price' => 999000,
            'sales_profile' => [
                'sales_status' => 'published',
                'headline' => 'Payload yang harus diabaikan',
            ],
        ])->assertRedirect();

        $course->refresh();
        $this->assertSame('private', $course->visibility);
        $this->assertNull($course->price);
        $this->assertNull($course->salesProfile);
    }

    public function test_changing_regular_course_to_avpn_disables_existing_commerce(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('manage all courses');
        $user = User::factory()->create();
        $user->givePermissionTo('manage all courses');
        $course = Course::factory()->create([
            'visibility' => 'catalog',
            'price' => 150000,
            'short_description' => 'Dijual',
            'requires_payment_verification' => true,
        ]);
        $course->salesProfile()->create([
            'slug' => 'course-regular-dijual',
            'sales_status' => 'published',
        ]);

        $this->actingAs($user)->patch(route('courses.update', $course), [
            'title' => $course->title,
            'status' => 'published',
            'program_type' => 'avpn_ai',
        ])->assertRedirect();

        $course->refresh();
        $this->assertSame('avpn_ai', $course->program_type);
        $this->assertSame('private', $course->visibility);
        $this->assertNull($course->price);
        $this->assertNull($course->short_description);
        $this->assertFalse($course->requires_payment_verification);
        $this->assertSame('hidden', $course->salesProfile->sales_status);
    }
}
