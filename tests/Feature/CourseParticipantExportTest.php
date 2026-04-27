<?php

namespace Tests\Feature;

use App\Jobs\ExportCourseParticipantsJob;
use App\Models\Course;
use App\Models\CourseClass;
use App\Models\ExportHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CourseParticipantExportTest extends TestCase
{
    use RefreshDatabase;

    private function createPermissions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['manage all courses', 'view progress reports', 'manage own courses', 'attempt quizzes'] as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }
    }

    private function makeAdmin(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage all courses', 'view progress reports');
        return $user;
    }

    private function makeInstructor(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage own courses');
        return $user;
    }

    private function makeParticipant(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('attempt quizzes');
        return $user;
    }

    public function test_admin_can_export_all_participants(): void
    {
        $this->createPermissions();
        Queue::fake();

        $admin  = $this->makeAdmin();
        $course = Course::factory()->create();
        $p1     = $this->makeParticipant();
        $p2     = $this->makeParticipant();
        $course->enrolledUsers()->attach([$p1->id, $p2->id]);

        $response = $this->actingAs($admin)
            ->post(route('courses.export.participants', $course), ['filter' => 'all']);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        Queue::assertPushed(ExportCourseParticipantsJob::class);
        $this->assertDatabaseHas('export_histories', [
            'user_id'   => $admin->id,
            'course_id' => $course->id,
            'filter'    => 'all',
            'status'    => 'processing',
        ]);
    }

    public function test_admin_can_export_participants_by_class(): void
    {
        $this->createPermissions();
        Queue::fake();

        $admin  = $this->makeAdmin();
        $course = Course::factory()->create();
        $class  = CourseClass::factory()->for($course)->create();
        $p1     = $this->makeParticipant();
        $class->participants()->attach($p1->id);

        $response = $this->actingAs($admin)
            ->post(route('courses.export.participants', $course), [
                'filter'   => 'class',
                'class_id' => $class->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        Queue::assertPushed(ExportCourseParticipantsJob::class);
        $this->assertDatabaseHas('export_histories', [
            'user_id'         => $admin->id,
            'course_id'       => $course->id,
            'filter'          => 'class',
            'course_class_id' => $class->id,
        ]);
    }

    public function test_export_with_invalid_class_id_is_rejected(): void
    {
        $this->createPermissions();
        Queue::fake();

        $admin       = $this->makeAdmin();
        $course      = Course::factory()->create();
        $otherCourse = Course::factory()->create();
        $otherClass  = CourseClass::factory()->for($otherCourse)->create();

        $response = $this->actingAs($admin)
            ->post(route('courses.export.participants', $course), [
                'filter'   => 'class',
                'class_id' => $otherClass->id, // belongs to a different course
            ]);

        $response->assertStatus(403);
        Queue::assertNotPushed(ExportCourseParticipantsJob::class);
    }

    public function test_instructor_can_export_own_class(): void
    {
        $this->createPermissions();
        Queue::fake();

        $instructor = $this->makeInstructor();
        $course     = Course::factory()->create();
        $course->instructors()->attach($instructor->id);
        $class = CourseClass::factory()->for($course)->create();
        $class->instructors()->attach($instructor->id);
        $p1 = $this->makeParticipant();
        $class->participants()->attach($p1->id);

        $response = $this->actingAs($instructor)
            ->post(route('courses.export.participants', $course), [
                'filter'   => 'class',
                'class_id' => $class->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        Queue::assertPushed(ExportCourseParticipantsJob::class);
    }

    public function test_instructor_cannot_export_all_participants(): void
    {
        $this->createPermissions();
        Queue::fake();

        $instructor = $this->makeInstructor();
        $course     = Course::factory()->create();
        $course->instructors()->attach($instructor->id);

        $response = $this->actingAs($instructor)
            ->post(route('courses.export.participants', $course), ['filter' => 'all']);

        $response->assertStatus(403);
        Queue::assertNotPushed(ExportCourseParticipantsJob::class);
    }

    public function test_instructor_cannot_export_another_instructors_class(): void
    {
        $this->createPermissions();
        Queue::fake();

        $instructor1 = $this->makeInstructor();
        $instructor2 = $this->makeInstructor();
        $course      = Course::factory()->create();
        $course->instructors()->attach([$instructor1->id, $instructor2->id]);
        $class = CourseClass::factory()->for($course)->create();
        $class->instructors()->attach($instructor2->id); // only instructor2 teaches this class

        $response = $this->actingAs($instructor1)
            ->post(route('courses.export.participants', $course), [
                'filter'   => 'class',
                'class_id' => $class->id,
            ]);

        $response->assertStatus(403);
        Queue::assertNotPushed(ExportCourseParticipantsJob::class);
    }

    public function test_export_without_class_id_when_filter_is_class_fails(): void
    {
        $this->createPermissions();
        Queue::fake();

        $admin  = $this->makeAdmin();
        $course = Course::factory()->create();

        $response = $this->actingAs($admin)
            ->post(route('courses.export.participants', $course), [
                'filter' => 'class',
                // class_id missing
            ]);

        // Should abort with 422
        $response->assertStatus(422);
        Queue::assertNotPushed(ExportCourseParticipantsJob::class);
    }

    public function test_participants_count_endpoint_returns_correct_count(): void
    {
        $this->createPermissions();

        $admin  = $this->makeAdmin();
        $course = Course::factory()->create();
        $p1     = $this->makeParticipant();
        $p2     = $this->makeParticipant();
        $course->enrolledUsers()->attach([$p1->id, $p2->id]);

        $response = $this->actingAs($admin)
            ->getJson(route('courses.participants.count', $course) . '?filter=all');

        $response->assertOk()->assertJson(['count' => 2]);
    }

    public function test_only_owner_can_download_export(): void
    {
        $this->createPermissions();

        $admin  = $this->makeAdmin();
        $other  = $this->makeAdmin();
        $course = Course::factory()->create();

        $export = ExportHistory::create([
            'user_id'   => $admin->id,
            'course_id' => $course->id,
            'filter'    => 'all',
            'status'    => 'done',
            'file_path' => 'exports/test.xlsx',
        ]);

        $response = $this->actingAs($other)
            ->get(route('exports.download', $export));

        $response->assertStatus(403);
    }
}
