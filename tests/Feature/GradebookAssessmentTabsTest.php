<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Course;
use App\Models\DocumentSubmission;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class GradebookAssessmentTabsTest extends TestCase
{
    use RefreshDatabase;

    public function test_gradebook_shows_document_submission_and_quiz_tabs(): void
    {
        Permission::findOrCreate('grade quizzes');

        $instructor = User::factory()->create();
        $instructor->givePermissionTo('grade quizzes');
        $participant = User::factory()->create();
        $course = Course::factory()->create();
        $course->instructors()->attach($instructor);
        $course->enrolledUsers()->attach($participant);
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        $document = Content::factory()->create([
            'lesson_id' => $lesson->id,
            'title' => 'Tugas Dokumen',
            'type' => 'document',
            'collect_submission' => true,
        ]);
        DocumentSubmission::create([
            'user_id' => $participant->id,
            'content_id' => $document->id,
            'attempt' => 1,
            'status' => 'submitted',
            'original_name' => 'tugas.pdf',
            'submitted_at' => now(),
        ]);

        $quiz = Quiz::create([
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
            'user_id' => $instructor->id,
            'title' => 'Quiz Dasar',
            'passing_percentage' => 70,
            'status' => 'published',
        ]);
        Question::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'Pertanyaan quiz',
            'type' => 'multiple_choice',
            'marks' => 10,
        ]);
        Content::factory()->create([
            'lesson_id' => $lesson->id,
            'title' => 'Quiz Dasar',
            'type' => 'quiz',
            'quiz_id' => $quiz->id,
            'order' => 2,
        ]);
        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $participant->id,
            'score' => 8,
            'passed' => true,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($instructor)->get(route('courses.gradebook', [
            'course' => $course,
            'tab' => 'documents',
        ]));

        $response->assertOk()
            ->assertSee('Pengumpulan Dokumen')
            ->assertSee('Tugas Dokumen')
            ->assertSee('1 menunggu penilaian')
            ->assertSee('Nilai Quiz')
            ->assertSee('Quiz Dasar')
            ->assertSee('80.0%');
    }

    public function test_legacy_scores_tab_opens_the_quiz_tab_in_gradebook(): void
    {
        Permission::findOrCreate('grade quizzes');

        $instructor = User::factory()->create();
        $instructor->givePermissionTo('grade quizzes');
        $course = Course::factory()->create();
        $course->instructors()->attach($instructor);

        $this->actingAs($instructor)
            ->get(route('courses.gradebook', ['course' => $course, 'tab' => 'scores']))
            ->assertOk()
            ->assertSee("activeTab: 'quizzes'", false);
    }
}
