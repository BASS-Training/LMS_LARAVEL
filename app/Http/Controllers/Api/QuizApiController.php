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
        $requestPath = $request->path();
        $requestUrl = $request->fullUrl();

        Log::info(
            'QuizApiController@getByLesson called lessonId=' . $lessonId .
                ' path=' . $requestPath .
                ' url=' . $requestUrl
        );

        $content = Content::with('quiz.questions.options')->find($lessonId);
        Log::info('QuizApiController@getByLesson content lookup', [
            'content_found' => (bool) $content,
            'content_type' => $content->type ?? null,
            'content_quiz_id' => $content->quiz_id ?? null,
            'content_lesson_id' => $content->lesson_id ?? null,
            'content_title' => $content->title ?? null,
        ]);

        $quiz = null;

        if ($content && $content->quiz_id && $content->quiz) {
            // Load quiz even if not published in dev environment; keep strict checks optional
            $quiz = $content->quiz->loadMissing('questions.options');
            Log::info('QuizApiController@getByLesson quiz resolved from content.quiz', [
                'quiz_id' => $quiz->id,
                'quiz_title' => $quiz->title,
                'quiz_status' => $quiz->status,
                'questions_count' => $quiz->questions->count(),
            ]);
        } else {
            $lessonLookupId = $content->lesson_id ?? $lessonId;

            // Fallback: find quiz by lesson_id (do not filter by published status in dev)
            $quiz = Quiz::where('lesson_id', $lessonLookupId)
                ->with('questions.options')
                ->first();

            Log::info('QuizApiController@getByLesson quiz fallback lookup', [
                'lesson_lookup_id' => $lessonLookupId,
                'quiz_found' => (bool) $quiz,
                'quiz_id' => $quiz->id ?? null,
                'quiz_title' => $quiz->title ?? null,
                'quiz_status' => $quiz->status ?? null,
                'quiz_lesson_id' => $quiz->lesson_id ?? null,
                'questions_count' => $quiz?->questions?->count() ?? 0,
            ]);
        }

        if (!$quiz) {
            Log::warning('QuizApiController@getByLesson quiz not found', [
                'lessonId' => $lessonId,
                'content_exists' => (bool) $content,
                'content_type' => $content->type ?? null,
                'content_quiz_id' => $content->quiz_id ?? null,
            ]);

            return response()->json(['status' => 'error', 'message' => 'Quiz not found'], 404);
        }

        // Check if quiz is published (block draft quizzes)
        if ($quiz->status === 'draft') {
            Log::warning('QuizApiController@getByLesson quiz is draft', [
                'quiz_id' => $quiz->id,
                'quiz_title' => $quiz->title,
                'quiz_status' => $quiz->status,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Kuis sedang dalam status draft dan tidak tersedia untuk peserta',
            ], 403);
        }

        $questions = $quiz->questions->map(function ($q) {
            $options = $q->options->map(function ($opt) {
                return [
                    'id' => (string) $opt->id,
                    'text' => $opt->option_text,
                ];
            })->values()->toArray();

            // Determine correct index (for client-side evaluation if needed)
            $correctIndex = null;
            foreach ($q->options as $idx => $opt) {
                if ($opt->is_correct) {
                    $correctIndex = $idx;
                    break;
                }
            }

            return [
                'id' => (string) $q->id,
                'text' => $q->question_text,
                'type' => $q->type,
                'marks' => $q->marks ?? 1,
                'options' => $options,
                'correctIndex' => $correctIndex,
            ];
        })->values()->toArray();

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
            if (empty($ans['question_id'])) continue;

            $question = Question::with('options')->find($ans['question_id']);
            if (!$question) continue;

            $optionToSaveId = null;

            if ($question->type === 'multiple_choice' && !empty($ans['option_id'])) {
                $selectedOption = $question->options->find($ans['option_id']);
                if ($selectedOption) {
                    $optionToSaveId = $selectedOption->id;
                    if ($selectedOption->is_correct) {
                        $score += $question->marks ?? 1;
                    }
                }
            } elseif ($question->type === 'true_false' && !empty($ans['answer_text'])) {
                $selectedOption = $question->options->firstWhere('option_text', $ans['answer_text']);
                if ($selectedOption) {
                    $optionToSaveId = $selectedOption->id;
                    if ($selectedOption->is_correct) {
                        $score += $question->marks ?? 1;
                    }
                }
            }

            if ($optionToSaveId !== null) {
                QuestionAnswer::create([
                    'user_id' => $userId,
                    'quiz_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'option_id' => $optionToSaveId,
                ]);
            }
        }

        // Compute total marks and passing
        $totalMarks = collect($quiz->questions)->sum(function ($q) {
            return $q->marks ?? 1;
        });
        $passingMarks = ($totalMarks * ($quiz->passing_percentage ?? 70)) / 100;

        $attempt->score = $score;
        $attempt->passed = ($score >= $passingMarks);
        $attempt->completed_at = now();
        $attempt->save();

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
}
