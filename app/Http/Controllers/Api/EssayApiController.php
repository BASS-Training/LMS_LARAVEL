<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\EssayAnswer;
use App\Models\EssaySubmission;
use App\Models\EssayQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EssayApiController extends Controller
{
    public function getByLesson(Content $content)
    {
        if ($content->type !== 'essay') {
            return response()->json([
                'status' => 'error',
                'message' => 'Essay content not found',
            ], 404);
        }

        $content->load(['lesson.course', 'essayQuestions' => function ($query) {
            $query->orderBy('order');
        }]);

        if ($accessError = $this->ensureEssayAccess($content, request())) {
            return $accessError;
        }

        // attach user's submission/draft info
        $user = request()->user();
        $submission = null;
        if ($user) {
            $submission = EssaySubmission::with('answers')
                ->where('user_id', $user->id)
                ->where('content_id', $content->id)
                ->first();
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => (string) $content->id,
                'title' => $content->title,
                'description' => trim((string) ($content->description ?? $content->body ?? '')),
                'lessonId' => (string) $content->id,
                'courseId' => (string) $content->lesson?->course?->id,
                'totalQuestions' => $content->essayQuestions->count(),
                'questions' => $content->essayQuestions->map(function ($question) {
                    return [
                        'id' => (string) $question->id,
                        'text' => $question->question,
                        'order' => (int) $question->order,
                        'maxScore' => (int) $question->max_score,
                    ];
                })->values(),
                'submission' => $submission ? [
                    'submissionId' => (string) $submission->id,
                    'status' => $submission->status,
                    'submittedAt' => optional($submission->created_at)?->toISOString(),
                    'answers' => $submission->answers->map(function ($a) {
                        return [
                            'question_id' => (string) $a->question_id,
                            'answer' => $a->answer,
                        ];
                    })->values(),
                ] : null,
            ],
        ]);
    }

    public function submit(Request $request, Content $content)
    {
        if ($content->type !== 'essay') {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid content type',
            ], 422);
        }

        if ($accessError = $this->ensureEssayAccess($content, $request)) {
            return $accessError;
        }

        $payload = $request->validate([
            'answers' => 'required|array|min:1',
            'answers.*.question_id' => 'required',
            'answers.*.answer' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $questions = $content->essayQuestions()->orderBy('order')->get()->values();

        DB::transaction(function () use ($content, $payload, $user, $questions) {
            $submission = EssaySubmission::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'content_id' => $content->id,
                ],
                [
                    'status' => 'submitted',
                    'graded_at' => null,
                ]
            );

            $submission->answers()->delete();

            foreach ($payload['answers'] as $index => $answerData) {
                $questionId = $this->resolveEssayQuestionId(
                    $questions,
                    $answerData['question_id'] ?? null,
                    $index
                );

                if (!$questionId) {
                    continue;
                }

                EssayAnswer::create([
                    'submission_id' => $submission->id,
                    'question_id' => $questionId,
                    'answer' => trim((string) $answerData['answer']),
                ]);
            }

            $user->completedContents()->syncWithoutDetaching([
                $content->id => ['completed' => true, 'completed_at' => now()],
            ]);
        });

        $submission = EssaySubmission::with(['answers.question', 'content'])
            ->where('user_id', $user->id)
            ->where('content_id', $content->id)
            ->firstOrFail();

        return response()->json([
            'status' => 'success',
            'data' => [
                'submissionId' => (string) $submission->id,
                'answerCount' => $submission->answers->count(),
                'totalQuestions' => $content->essayQuestions()->count(),
                'submittedAt' => optional($submission->created_at)?->toISOString(),
            ],
        ], 201);
    }

    /**
     * Autosave draft answers for essay (mobile autosave)
     */
    public function autosave(Request $request, Content $content)
    {
        if ($content->type !== 'essay') {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid content type',
            ], 422);
        }

        if ($accessError = $this->ensureEssayAccess($content, $request)) {
            return $accessError;
        }

        $payload = $request->validate([
            'answers' => 'required|array|min:1',
            'answers.*.question_id' => 'required',
            'answers.*.answer' => ['required', 'string'],
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $questions = $content->essayQuestions()->orderBy('order')->get()->values();

        DB::transaction(function () use ($content, $payload, $user, $questions) {
            $submission = EssaySubmission::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'content_id' => $content->id,
                ],
                [
                    'status' => 'draft',
                    'graded_at' => null,
                ]
            );

            $submission->answers()->delete();

            foreach ($payload['answers'] as $index => $answerData) {
                $questionId = $this->resolveEssayQuestionId(
                    $questions,
                    $answerData['question_id'] ?? null,
                    $index
                );

                if (!$questionId) {
                    continue;
                }

                EssayAnswer::create([
                    'submission_id' => $submission->id,
                    'question_id' => $questionId,
                    'answer' => trim((string) $answerData['answer']),
                ]);
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Draft saved',
        ], 200);
    }

    private function ensureEssayAccess(Content $content, Request $request): ?\Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $content->loadMissing('lesson.course');
        $course = $content->lesson?->course;

        if (!$course) {
            return response()->json([
                'status' => 'error',
                'message' => 'Course for this essay was not found.',
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

    private function resolveEssayQuestionId($questions, $rawQuestionId, int $fallbackIndex): ?int
    {
        if (is_numeric($rawQuestionId)) {
            $candidate = (int) $rawQuestionId;

            if ($questions->contains('id', $candidate)) {
                return $candidate;
            }

            if ($candidate >= 0 && $candidate < $questions->count()) {
                return (int) $questions[$candidate]->id;
            }
        }

        if ($fallbackIndex >= 0 && $fallbackIndex < $questions->count()) {
            return (int) $questions[$fallbackIndex]->id;
        }

        return null;
    }
}
