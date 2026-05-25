<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Content;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\Option;
use App\Models\QuizAttempt;
use App\Models\QuestionAnswer;
use Illuminate\Support\Facades\Log;

class QuizApiController extends Controller
{
    /**
     * Return quiz for a given lesson id
     */
    public function getByLesson(Request $request, $lessonId)
    {
        Log::info('QuizApiController@getByLesson called', ['lessonId' => $lessonId]);

        $quiz = $this->resolveQuizForLesson($lessonId);

        if (!$quiz) {
            Log::warning('QuizApiController@getByLesson quiz not found', ['lessonId' => $lessonId]);

            return response()->json(['status' => 'error', 'message' => 'Quiz not found'], 404);
        }

        // Check if quiz is published (block draft quizzes)
        if ($quiz->status === 'draft') {
            Log::warning('QuizApiController@getByLesson quiz is draft', ['lessonId' => $lessonId, 'quizId' => $quiz->id]);

            return response()->json([
                'status' => 'error',
                'message' => 'Kuis sedang dalam status draft dan tidak tersedia untuk peserta',
            ], 403);
        }

        if ($accessError = $this->ensureQuizAccess($request, $quiz)) {
            return $accessError;
        }

        $questions = $this->transformQuestions($quiz);

        // attach user-specific attempt/complete info
        $user = $request->user();
        $userAttempt = null;
        $completed = false;
        if ($user) {
            $latest = QuizAttempt::where('quiz_id', $quiz->id)
                ->where('user_id', $user->id)
                ->latest()
                ->first();
            if ($latest) {
                $userAttempt = [
                    'attemptId' => (string) $latest->id,
                    'score' => $latest->score,
                    'passed' => (bool) $latest->passed,
                    'completedAt' => optional($latest->completed_at)?->toISOString(),
                ];
            }

            // determine content completion (if related content exists)
            $content = Content::where('quiz_id', $quiz->id)->first();
            if ($content) {
                $completed = $user->completedContents()->wherePivot('content_id', $content->id)->exists();
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => (string) $quiz->id,
                'title' => $quiz->title,
                'totalQuestions' => count($questions),
                'timeLimit' => $quiz->time_limit ?? 0,
                'passingScore' => (int) ($quiz->passing_percentage ?? 70),
                'questions' => $questions,
                'userAttempt' => $userAttempt,
                'completed' => $completed,
            ],
        ], 200);
    }

    /**
     * Start attempt for a quiz (creates QuizAttempt)
     */
    public function startAttempt(Request $request, Quiz $quiz)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($accessError = $this->ensureQuizAccess($request, $quiz)) {
            return $accessError;
        }

        // Prevent new attempts when user already has a passing attempt
        $existingPassed = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->where('passed', true)
            ->exists();

        if ($existingPassed) {
            return response()->json([
                'status' => 'error',
                'message' => 'You have already passed this quiz and cannot start a new attempt.',
            ], 403);
        }

        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $user->id,
            'started_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'attemptId' => (string) $attempt->id,
                'startedAt' => $attempt->started_at->toISOString(),
            ],
        ], 201);
    }

    /**
     * Submit answers for an attempt and save into question_answers
     */
    public function submitAttempt(Request $request, Quiz $quiz, QuizAttempt $attempt)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($accessError = $this->ensureQuizAccess($request, $quiz)) {
            return $accessError;
        }

        if ((int) $attempt->user_id !== (int) $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Attempt does not belong to the authenticated user.',
            ], 403);
        }

        if ($quiz->questions->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Quiz has no questions',
            ], 422);
        }

        $payload = $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:questions,id',
            'answers.*.option_id' => 'nullable|exists:options,id',
            'answers.*.answer_text' => 'nullable|string',
        ]);

        $answers = $payload['answers'];

        $score = 0;

        foreach ($answers as $ans) {
            if (empty($ans['question_id'])) {
                continue;
            }

            $question = Question::with('options')->find($ans['question_id']);
            if (!$question) {
                continue;
            }

            $selectedOption = $this->resolveSelectedOption($question, $ans);
            if ($selectedOption) {
                QuestionAnswer::create([
                    'user_id' => $user->id,
                    'quiz_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'option_id' => $selectedOption->id,
                ]);

                if ($selectedOption->is_correct) {
                    $score += $question->marks ?? 1;
                }
            }
        }

        // Compute total marks and passing
        $totalMarks = collect($quiz->questions)->sum(fn($question) => $question->marks ?? 1);
        $passingMarks = ($totalMarks * ($quiz->passing_percentage ?? 70)) / 100;

        $attempt->update([
            'score' => $score,
            'passed' => $score >= $passingMarks,
            'completed_at' => now(),
        ]);

        // If passed, mark related content as completed for the user
        if ($attempt->passed) {
            $content = Content::where('quiz_id', $quiz->id)->first();
            if ($content) {
                $user->completedContents()->syncWithoutDetaching([
                    $content->id => ['completed' => true, 'completed_at' => now()],
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'score' => $score,
                'total' => $totalMarks,
                'passed' => $attempt->passed,
                'percentage' => $totalMarks > 0 ? round(($score / $totalMarks) * 100, 2) : 0,
                'attemptId' => (string) $attempt->id,
            ],
        ], 200);
    }

    private function resolveQuizForLesson($lessonId): ?Quiz
    {
        $content = Content::with('quiz.questions.options')->find($lessonId);

        if ($content?->quiz_id && $content->quiz) {
            return $content->quiz->loadMissing('questions.options');
        }

        $lessonLookupId = $content?->lesson_id ?? $lessonId;

        return Quiz::where('lesson_id', $lessonLookupId)
            ->with('questions.options')
            ->first();
    }

    private function transformQuestions(Quiz $quiz): array
    {
        return $quiz->questions->map(function (Question $question) {
            $options = $question->options->map(function (Option $option) {
                return [
                    'id' => (string) $option->id,
                    'text' => $option->option_text,
                ];
            })->values()->all();

            $correctIndex = $question->options->search(function (Option $option) {
                return $option->is_correct;
            });

            return [
                'id' => (string) $question->id,
                'text' => $question->question_text,
                'type' => $question->type,
                'marks' => $question->marks ?? 1,
                'options' => $options,
                'correctIndex' => $correctIndex === false ? null : $correctIndex,
            ];
        })->values()->all();
    }

    private function resolveSelectedOption(Question $question, array $answer): ?Option
    {
        if ($question->type === 'multiple_choice' && !empty($answer['option_id'])) {
            return $question->options->find($answer['option_id']);
        }

        if ($question->type === 'true_false' && !empty($answer['answer_text'])) {
            return $question->options->firstWhere('option_text', $answer['answer_text']);
        }

        return null;
    }

    private function ensureQuizAccess(Request $request, Quiz $quiz): ?\Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $quiz->loadMissing('lesson.course');
        $course = $quiz->lesson?->course;

        if (!$course) {
            return response()->json([
                'status' => 'error',
                'message' => 'Course for this quiz was not found.',
            ], 404);
        }

        if (
            $user->can('manage all courses') ||
            $user->isInstructorFor($course) ||
            $user->isEventOrganizerFor($course) ||
            $user->isEnrolled($course)
        ) {
            return null;
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Anda belum terdaftar di course ini.',
        ], 403);
    }
}
