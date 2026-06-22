<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\FeedbackSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeedbackApiController extends Controller
{
    /**
     * Ambil definisi form feedback + tanggapan milik user (mobile pakai
     * content.id sebagai lessonId, sama seperti tipe konten lain).
     */
    public function getByLesson(Content $content)
    {
        if ($content->type !== 'feedback') {
            return response()->json(['status' => 'error', 'message' => 'Feedback content not found'], 404);
        }

        if ($accessError = $this->ensureAccess($content, request())) {
            return $accessError;
        }

        $content->loadMissing('lesson.course', 'feedbackQuestions');
        $user = request()->user();

        $submission = FeedbackSubmission::where('user_id', $user->id)
            ->where('content_id', $content->id)
            ->with('answers')
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => (string) $content->id,
                'title' => $content->title,
                'description' => trim((string) ($content->description ?? '')),
                'lessonId' => (string) $content->id,
                'courseId' => (string) $content->lesson?->course?->id,
                'isAnonymous' => (bool) $content->is_anonymous,
                'questions' => $content->feedbackQuestions->map(fn ($q) => [
                    'id' => (string) $q->id,
                    'type' => $q->type,
                    'question' => $q->question,
                    'helpText' => $q->help_text,
                    'isRequired' => (bool) $q->is_required,
                    'config' => $q->config ?? (object) [],
                ])->values(),
                'submission' => $submission ? [
                    'submissionId' => (string) $submission->id,
                    'status' => $submission->status,
                    'submittedAt' => optional($submission->submitted_at)?->toISOString(),
                    'answers' => $submission->answers->mapWithKeys(fn ($a) => [
                        (string) $a->question_id => [
                            'rating' => $a->rating_value,
                            'text' => $a->text_value,
                            'choice' => $a->choice_value ?? [],
                        ],
                    ]),
                ] : null,
            ],
        ]);
    }

    /**
     * Simpan tanggapan feedback dari mobile. answers = map questionId => value
     * (rating: int, text: string, single_choice: optionId, multi_choice: [ids]).
     */
    public function submit(Request $request, Content $content)
    {
        if ($content->type !== 'feedback') {
            return response()->json(['status' => 'error', 'message' => 'Invalid content type'], 422);
        }

        if ($accessError = $this->ensureAccess($content, $request)) {
            return $accessError;
        }

        $user = $request->user();
        $questions = $content->feedbackQuestions()->get();
        $answers = $request->input('answers', []);

        // Validasi pertanyaan wajib.
        $missing = [];
        foreach ($questions as $q) {
            if ($q->is_required && $this->isEmptyAnswer($answers[$q->id] ?? null)) {
                $missing[] = $q->question;
            }
        }
        if (! empty($missing)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pertanyaan wajib belum diisi.',
                'missing' => $missing,
            ], 422);
        }

        $submission = DB::transaction(function () use ($content, $user, $questions, $answers) {
            $submission = FeedbackSubmission::updateOrCreate(
                ['user_id' => $user->id, 'content_id' => $content->id],
                ['status' => 'submitted', 'submitted_at' => now()]
            );

            $submission->answers()->delete();

            foreach ($questions as $q) {
                $raw = $answers[$q->id] ?? null;
                $rating = null;
                $text = null;
                $choice = null;

                switch ($q->type) {
                    case 'rating':
                        $rating = is_numeric($raw) ? (int) $raw : null;
                        break;
                    case 'text':
                        $text = is_string($raw) ? trim($raw) : null;
                        $text = ($text === '') ? null : $text;
                        break;
                    case 'single_choice':
                        $choice = ($raw !== null && $raw !== '') ? [(string) $raw] : null;
                        break;
                    case 'multi_choice':
                        $choice = is_array($raw)
                            ? array_values(array_filter(array_map('strval', $raw), fn ($v) => $v !== ''))
                            : null;
                        $choice = empty($choice) ? null : $choice;
                        break;
                }

                if ($rating === null && $text === null && empty($choice)) {
                    continue;
                }

                $submission->answers()->create([
                    'question_id' => $q->id,
                    'rating_value' => $rating,
                    'text_value' => $text,
                    'choice_value' => $choice,
                ]);
            }

            return $submission;
        });

        $user->completedContents()->syncWithoutDetaching([
            $content->id => ['completed' => true, 'completed_at' => now()],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Tanggapan dikirim',
            'data' => [
                'submissionId' => (string) $submission->id,
                'status' => $submission->status,
            ],
        ], 201);
    }

    private function isEmptyAnswer($raw): bool
    {
        if ($raw === null || $raw === '') {
            return true;
        }
        if (is_array($raw)) {
            return count(array_filter($raw, fn ($v) => $v !== '' && $v !== null)) === 0;
        }

        return false;
    }

    private function ensureAccess(Content $content, Request $request): ?\Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $content->loadMissing('lesson.course');
        $course = $content->lesson?->course;

        if (! $course) {
            return response()->json(['status' => 'error', 'message' => 'Course tidak ditemukan.'], 404);
        }

        if (
            $user->can('manage all courses') ||
            $user->isInstructorFor($course) ||
            $user->isEventOrganizerFor($course) ||
            $user->isEnrolled($course)
        ) {
            return null;
        }

        return response()->json(['status' => 'error', 'message' => 'Anda belum terdaftar di course ini.'], 403);
    }
}
