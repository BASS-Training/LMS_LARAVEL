<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Course;
use App\Models\CourseClass;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CourseShowPayloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['manage all courses', 'manage own courses', 'view courses', 'view progress reports', 'attempt quizzes'] as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }
        Role::findOrCreate('event-organizer');
    }

    private function makeAdmin(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['manage all courses', 'view progress reports']);

        return $user;
    }

    private function makeInstructor(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['manage own courses', 'view courses']);

        return $user;
    }

    public function test_lesson_content_and_period_titles_render_without_secrets(): void
    {
        $admin = $this->makeAdmin();
        $course = Course::factory()->create(['title' => 'Kursus Optimasi Payload']);

        $lesson = Lesson::factory()->create([
            'course_id' => $course->id,
            'title' => 'Judul Lesson Payload',
            'description' => 'Deskripsi lesson payload',
        ]);

        Content::factory()->create([
            'lesson_id' => $lesson->id,
            'title' => 'Judul Content Payload',
            'type' => 'text',
            'body' => 'RAHASIA_BODY_KONTEN_XYZ',
        ]);

        CourseClass::factory()->create([
            'course_id' => $course->id,
            'name' => 'Kelas Payload Alpha',
            'status' => 'active',
            'description' => 'Deskripsi kelas payload',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(30),
            'enrollment_token' => 'TOKENRAHASIA123',
            'class_code' => 'CLASSRAH4',
            'max_participants' => 50,
            'program_type' => 'regular',
            'token_enabled' => true,
            'token_expires_at' => now()->addDay(),
            'token_type' => 'random',
        ]);

        $response = $this->actingAs($admin)->get(route('courses.show', $course));

        $response->assertOk();
        $response->assertSee('Kursus Optimasi Payload');
        $response->assertSee('Judul Lesson Payload');
        $response->assertSee('Deskripsi lesson payload');
        $response->assertSee('Judul Content Payload');
        $response->assertSee('Kelas Payload Alpha');
        $response->assertSee('Deskripsi kelas payload');
        $response->assertDontSee('RAHASIA_BODY_KONTEN_XYZ');
        $response->assertDontSee('TOKENRAHASIA123');
        $response->assertDontSee('CLASSRAH4');
    }

    public function test_instructor_only_sees_assigned_periods(): void
    {
        $instructor = $this->makeInstructor();
        $course = Course::factory()->create();
        $course->instructors()->attach($instructor->id);

        $assigned = CourseClass::factory()->create([
            'course_id' => $course->id,
            'name' => 'Kelas Instruktur Satu',
            'status' => 'active',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(30),
        ]);

        CourseClass::factory()->create([
            'course_id' => $course->id,
            'name' => 'Kelas Instruktur Dua',
            'status' => 'upcoming',
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(40),
        ]);

        $assigned->instructors()->attach($instructor->id);

        $response = $this->actingAs($instructor)->get(route('courses.show', $course));

        $response->assertOk();
        $response->assertSee('Kelas Instruktur Satu');
        $response->assertDontSee('Kelas Instruktur Dua');
    }

    public function test_active_period_in_date_range_shows_chat_button(): void
    {
        $admin = $this->makeAdmin();
        $course = Course::factory()->create();

        CourseClass::factory()->create([
            'course_id' => $course->id,
            'status' => 'active',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(30),
        ]);

        $response = $this->actingAs($admin)->get(route('courses.show', $course));

        $response->assertOk();
        $response->assertSee('Buka Chat');
    }

    public function test_missing_active_period_hides_chat_button(): void
    {
        $admin = $this->makeAdmin();
        $course = Course::factory()->create();

        CourseClass::factory()->create([
            'course_id' => $course->id,
            'status' => 'completed',
            'start_date' => now()->subDays(40),
            'end_date' => now()->subDays(10),
        ]);

        $response = $this->actingAs($admin)->get(route('courses.show', $course));

        $response->assertOk();
        $response->assertDontSee('Buka Chat');
    }
}
