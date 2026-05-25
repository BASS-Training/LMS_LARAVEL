<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\EssaySubmission;
use Illuminate\Http\Request;

class CourseResultsApiController extends Controller
{
    public function index(Request $request, Course $course)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if (!($user->can('manage all courses') || $user->isInstructorFor($course) || $user->isEventOrganizerFor($course) || $user->isEnrolled($course))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda belum terdaftar di course ini.',
            ], 403);
        }

        $quizAttempts = QuizAttempt::where('user_id', $user->id)
            ->whereHas('quiz.lesson.course', function ($query) use ($course) {
                $query->where('id', $course->id);
            })
            ->with(['quiz.questions', 'quiz.lesson.contents', 'answers.question.options'])
            ->orderByDesc('completed_at')
            ->get();

        $essaySubmissions = EssaySubmission::where('user_id', $user->id)
            ->whereHas('content.lesson.course', function ($query) use ($course) {
                $query->where('id', $course->id);
            })
            ->with(['content.essayQuestions', 'answers'])
            ->latest()
            ->get();

        $results = [];

        foreach ($quizAttempts as $attempt) {
            $quiz = $attempt->quiz;
            $content = $quiz?->lesson?->contents?->firstWhere('quiz_id', $quiz->id);
            $totalMarks = $quiz?->questions?->sum(fn ($question) => $question->marks ?? 1) ?: 0;

            $attemptQuestions = $attempt->answers->map(function ($answer, $index) use ($quiz) {
                $question = $quiz?->questions?->firstWhere('id', $answer->question_id);
                $selectedIndex = null;

                if ($question && $question->relationLoaded('options')) {
                    $selectedIndex = $question->options->search(function ($option) use ($answer) {
                        return (string) $option->id === (string) $answer->option_id;
                    });
                    if ($selectedIndex === false) {
                        $selectedIndex = null;
                    }
                }

                return [
                    'questionIndex' => $index,
                    'questionText' => $question?->question_text ?? '',
                    'options' => $question?->options?->pluck('option_text')->values()->all() ?? [],
                    'correctOptionIndex' => $question?->options?->search(fn ($option) => $option->is_correct) ?: null,
                    'selectedOptionIndex' => $selectedIndex,
                ];
            })->values()->all();

            $results[] = [
                'id' => (string) $attempt->id,
                'courseId' => (string) $course->id,
                'courseTitle' => $course->title,
                'lessonId' => (string) ($content?->id ?? $quiz?->lesson_id ?? ''),
                'lessonTitle' => $content?->title ?? $quiz?->title ?? '',
                'lessonType' => 'quiz',
                'attemptNumber' => $this->getQuizAttemptNumber($user->id, $attempt->quiz_id, $attempt->completed_at),
                'submittedAt' => optional($attempt->completed_at ?? $attempt->started_at)?->toISOString(),
                'graded' => true,
                'score' => (int) ($attempt->score ?? 0),
                'maxScore' => (int) ($totalMarks ?: count($quiz?->questions ?? []) ?: 1),
                'passed' => (bool) $attempt->passed,
                'questions' => $attemptQuestions,
            ];
        }

        foreach ($essaySubmissions as $submission) {
            $content = $submission->content;
            $questions = $content?->essayQuestions?->values() ?? collect();

            $attemptQuestions = $submission->answers->values()->map(function ($answer, $index) use ($questions) {
                $question = $questions->firstWhere('id', $answer->question_id);

                return [
                    'questionIndex' => $index,
                    'questionText' => $question?->question ?? '',
                    'options' => [],
                    'correctOptionIndex' => null,
                    'selectedOptionIndex' => null,
                    'writtenAnswer' => $answer->answer,
                ];
            })->values()->all();

            $results[] = [
                'id' => (string) $submission->id,
                'courseId' => (string) $course->id,
                'courseTitle' => $course->title,
                'lessonId' => (string) ($content?->id ?? ''),
                'lessonTitle' => $content?->title ?? '',
                'lessonType' => 'essay',
                'attemptNumber' => $this->getEssayAttemptNumber($user->id, $submission->content_id, $submission->created_at),
                'submittedAt' => optional($submission->created_at)?->toISOString(),
                'graded' => (bool) $submission->is_fully_graded,
                'score' => $submission->total_score !== null ? (float) $submission->total_score : null,
                'maxScore' => $submission->max_total_score !== null ? (float) $submission->max_total_score : null,
                'passed' => null,
                'questions' => $attemptQuestions,
            ];
        }

        usort($results, function ($a, $b) {
            return strcmp($b['submittedAt'] ?? '', $a['submittedAt'] ?? '');
        });

        return response()->json([
            'status' => 'success',
            'data' => $results,
        ]);
    }

    private function getQuizAttemptNumber(int $userId, int $quizId, $completedAt): int
    {
        return QuizAttempt::where('user_id', $userId)
            ->where('quiz_id', $quizId)
            ->where(function ($query) use ($completedAt) {
                if ($completedAt) {
                    $query->where('completed_at', '<=', $completedAt);
                }
            })
            ->count();
    }

    private function getEssayAttemptNumber(int $userId, int $contentId, $createdAt): int
    {
        return EssaySubmission::where('user_id', $userId)
            ->where('content_id', $contentId)
            ->where(function ($query) use ($createdAt) {
                if ($createdAt) {
                    $query->where('created_at', '<=', $createdAt);
                }
            })
            ->count();
    }
}
