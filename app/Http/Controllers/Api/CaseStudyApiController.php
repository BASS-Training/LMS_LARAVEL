<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\CaseStudySubmission;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class CaseStudyApiController extends Controller
{
    /**
     * Ambil template studi kasus + draft/submission milik user (mobile pakai content.id sebagai lessonId).
     */
    public function getByLesson(Content $content)
    {
        if ($content->type !== 'case_study') {
            return response()->json([
                'status' => 'error',
                'message' => 'Case study content not found',
            ], 404);
        }

        if ($accessError = $this->ensureAccess($content, request())) {
            return $accessError;
        }

        $content->loadMissing('lesson.course');
        $user = request()->user();

        $submission = CaseStudySubmission::where('user_id', $user->id)
            ->where('content_id', $content->id)
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => (string) $content->id,
                'title' => $content->title,
                'description' => trim((string) ($content->description ?? '')),
                'lessonId' => (string) $content->id,
                'courseId' => (string) $content->lesson?->course?->id,
                'allowAnswerDownload' => (bool) $content->allow_answer_download,
                'scoringEnabled' => (bool) $content->scoring_enabled,
                'template' => $content->case_study_template,
                'submission' => $submission ? [
                    'submissionId' => (string) $submission->id,
                    'status' => $submission->status,
                    'answers' => $submission->answers ?? (object) [],
                    'score' => $submission->score,
                    'feedback' => $submission->feedback,
                    'submittedAt' => optional($submission->submitted_at)?->toISOString(),
                ] : null,
            ],
        ]);
    }

    public function submit(Request $request, Content $content)
    {
        return $this->persist($request, $content, 'submitted');
    }

    public function autosave(Request $request, Content $content)
    {
        return $this->persist($request, $content, 'draft');
    }

    private function persist(Request $request, Content $content, string $intendedStatus)
    {
        if ($content->type !== 'case_study') {
            return response()->json(['status' => 'error', 'message' => 'Invalid content type'], 422);
        }

        if ($accessError = $this->ensureAccess($content, $request)) {
            return $accessError;
        }

        $payload = $request->validate([
            'answers' => 'required|array',
        ]);

        $user = $request->user();
        $answers = $payload['answers'];

        $existing = CaseStudySubmission::where('user_id', $user->id)
            ->where('content_id', $content->id)
            ->first();

        // Draft tidak boleh menurunkan status submitted/graded.
        $status = $intendedStatus;
        if ($intendedStatus === 'draft' && $existing && in_array($existing->status, ['submitted', 'graded'], true)) {
            $status = $existing->status;
        }

        $attributes = ['answers' => $answers, 'status' => $status];
        if ($intendedStatus === 'submitted') {
            $attributes['submitted_at'] = now();
            $attributes['graded_at'] = null;
            $attributes['pdf_path'] = null;
        }

        $submission = CaseStudySubmission::updateOrCreate(
            ['user_id' => $user->id, 'content_id' => $content->id],
            $attributes
        );

        if ($intendedStatus === 'submitted') {
            $user->completedContents()->syncWithoutDetaching([
                $content->id => ['completed' => true, 'completed_at' => now()],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => $intendedStatus === 'submitted' ? 'Jawaban dikumpulkan' : 'Draft tersimpan',
            'data' => [
                'submissionId' => (string) $submission->id,
                'status' => $submission->status,
            ],
        ], $intendedStatus === 'submitted' ? 201 : 200);
    }

    /**
     * Stream PDF jawaban peserta (untuk in-app viewer & tombol download di mobile).
     */
    public function download(Content $content)
    {
        if ($content->type !== 'case_study') {
            return response()->json(['status' => 'error', 'message' => 'Invalid content type'], 422);
        }

        if ($accessError = $this->ensureAccess($content, request())) {
            return $accessError;
        }

        $user = request()->user();
        $submission = CaseStudySubmission::where('user_id', $user->id)
            ->where('content_id', $content->id)
            ->first();

        if (!$submission || !in_array($submission->status, ['submitted', 'graded'], true)) {
            return response()->json(['status' => 'error', 'message' => 'Jawaban belum dikumpulkan'], 403);
        }

        if (!$content->allow_answer_download) {
            return response()->json(['status' => 'error', 'message' => 'Pengunduhan tidak diizinkan'], 403);
        }

        $pdf = Pdf::loadView('case-studies.pdf', [
            'content' => $content,
            'template' => $content->case_study_template,
            'answers' => $submission->answers ?? [],
            'submission' => $submission,
            'participant' => $user,
        ])->setPaper('a4', 'portrait');

        $filename = 'studi-kasus-' . $content->id . '.pdf';

        return $pdf->download($filename);
    }

    private function ensureAccess(Content $content, Request $request): ?\Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $content->loadMissing('lesson.course');
        $course = $content->lesson?->course;

        if (!$course) {
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
