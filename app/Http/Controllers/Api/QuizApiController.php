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

        $questions = $this->transformQuestions($quiz);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => (string) $quiz->id,
                'title' => $quiz->title,
                'totalQuestions' => count($questions),
                'timeLimit' => $quiz->time_limit ?? 0,
                'passingScore' => (int) ($quiz->passing_percentage ?? 70),
                'questions' => $questions,
            ],
        ], 200);
    }

    /**
     * Start attempt for a quiz (creates QuizAttempt)
     * Accepts optional user_id in request body; if not present, attempt is anonymous
     */
    public function startAttempt(Request $request, Quiz $quiz)
    {
        $userId = $request->input('user_id');

        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $userId,
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
     * Expected payload: { user_id, answers: [{ question_id, option_id, answer_text }] }
     */
    public function submitAttempt(Request $request, Quiz $quiz, QuizAttempt $attempt)
    {
        if ($quiz->questions->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Quiz has no questions',
            ], 422);
        }

        $payload = $request->validate([
            'user_id' => 'nullable|integer',
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:questions,id',
            'answers.*.option_id' => 'nullable|exists:options,id',
            'answers.*.answer_text' => 'nullable|string',
        ]);

        $userId = $payload['user_id'] ?? null;
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
                    'user_id' => $userId,
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
}
