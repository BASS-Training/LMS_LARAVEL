<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class QuizImportFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_manager_sees_all_lessons(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['manage own courses', 'manage all courses', 'create quizzes'] as $permission) {
            Permission::create(['name' => $permission]);
        }

        $manager = User::factory()->create();
        $manager->givePermissionTo('manage own courses', 'manage all courses', 'create quizzes');

        $firstLesson = Lesson::factory()->for(Course::factory())->create();
        $secondLesson = Lesson::factory()->for(Course::factory())->create();

        $response = $this->actingAs($manager)->get(route('quizzes.import-form'));

        $response->assertOk();
        $response->assertViewHas('lessons', function ($lessons) use ($firstLesson, $secondLesson) {
            return $lessons->modelKeys() === [$firstLesson->id, $secondLesson->id];
        });
    }

    public function test_instructor_only_sees_lessons_from_assigned_courses(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::create(['name' => 'manage own courses']);
        Permission::create(['name' => 'create quizzes']);

        $instructor = User::factory()->create();
        $instructor->givePermissionTo('manage own courses', 'create quizzes');

        $assignedCourse = Course::factory()->create();
        $assignedCourse->instructors()->attach($instructor);
        $assignedLesson = Lesson::factory()->for($assignedCourse)->create();

        $otherCourse = Course::factory()->create();
        $otherLesson = Lesson::factory()->for($otherCourse)->create();

        $response = $this->actingAs($instructor)->get(route('quizzes.import-form'));

        $response->assertOk();
        $response->assertViewHas('lessons', function ($lessons) use ($assignedLesson, $otherLesson) {
            return $lessons->modelKeys() === [$assignedLesson->id]
                && !$lessons->contains($otherLesson);
        });
    }
}
