<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\EssayAnswer;
use App\Models\EssaySubmission;
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

        $payload = $request->validate([
            'answers' => 'required|array|min:1',
            'answers.*.question_id' => 'required|exists:essay_questions,id',
            'answers.*.answer' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $questions = $content->essayQuestions()->get()->keyBy('id');

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

            foreach ($payload['answers'] as $answerData) {
                $questionId = (int) $answerData['question_id'];
                if (!$questions->has($questionId)) {
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
}
