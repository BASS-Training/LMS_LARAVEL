<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\DocumentSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * API mobile untuk pengumpulan tugas dokumen (konten tipe 'document' dengan
 * collect_submission = true). Menyediakan alur peserta (upload/kumpulkan) dan
 * instruktur (lihat & nilai). Mengikuti pola CaseStudyApiController.
 */
class DocumentSubmissionApiController extends Controller
{
    private const DEFAULT_TYPES = 'pdf,doc,docx,ppt,pptx,xls,xlsx,txt,jpg,jpeg,png,zip,rar';
    private const DEFAULT_MAX_MB = 20;

    // ---------------------------------------------------------------- PESERTA

    /** Config + semua attempt milik peserta yang login. */
    public function getByLesson(Content $content): JsonResponse
    {
        if (!$content->collectsSubmission()) {
            return response()->json(['status' => 'error', 'message' => 'Pengumpulan tidak aktif.'], 404);
        }
        if ($err = $this->ensureAccess($content, request())) {
            return $err;
        }

        $user = request()->user();
        $subs = $content->documentSubmissions()
            ->where('user_id', $user->id)
            ->orderBy('attempt')
            ->get();
        $latest = $subs->last();

        $latestStatus = $latest?->status ?? 'none';
        $canUpload = !$latest || in_array($latestStatus, ['draft', 'failed'], true);
        $nextAttempt = $latest ? ($latestStatus === 'failed' ? $latest->attempt + 1 : $latest->attempt) : 1;

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => (string) $content->id,
                'title' => $content->title,
                'description' => trim((string) ($content->description ?? '')),
                'lessonId' => (string) $content->id,
                'courseId' => (string) $content->lesson?->course?->id,
                'collectSubmission' => true,
                'instructions' => $content->submission_instructions,
                'maxSizeMb' => $this->maxSizeMb($content),
                'allowedTypes' => $this->allowedTypes($content),
                'scoringEnabled' => (bool) $content->isScoringEnabled(),
                'activeStatus' => $latestStatus,
                'canUpload' => $canUpload,
                'nextAttempt' => $nextAttempt,
                'submissions' => $subs->map(fn ($s) => $this->serialize($s))->values(),
            ],
        ]);
    }

    /** Upload / ganti file (multipart 'file'). Membuat attempt baru bila perlu. */
    public function upload(Request $request, Content $content): JsonResponse
    {
        if (!$content->collectsSubmission()) {
            return response()->json(['status' => 'error', 'message' => 'Pengumpulan tidak aktif.'], 404);
        }
        if ($err = $this->ensureAccess($content, $request)) {
            return $err;
        }

        $allowed = $this->allowedTypes($content);
        $maxKb = $this->maxSizeMb($content) * 1024;
        $request->validate([
            'file' => "required|file|mimes:{$allowed}|max:{$maxKb}",
        ]);

        $user = $request->user();
        $submission = $this->resolveDraftAttempt($content, $user->id);
        if ($submission === null) {
            return response()->json(['status' => 'error', 'message' => 'Pengumpulan sedang menunggu penilaian atau sudah lulus.'], 422);
        }

        if ($submission->file_path) {
            Storage::disk('public')->delete($submission->file_path);
        }

        $file = $request->file('file');
        $path = $file->store("document_submissions/{$content->id}/{$user->id}", 'public');
        $submission->update([
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getClientMimeType(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'File diupload.',
            'data' => $this->serialize($submission->fresh()),
        ], 201);
    }

    /** Hapus file pada attempt draft. */
    public function removeFile(Content $content): JsonResponse
    {
        if (!$content->collectsSubmission()) {
            return response()->json(['status' => 'error', 'message' => 'Pengumpulan tidak aktif.'], 404);
        }
        if ($err = $this->ensureAccess($content, request())) {
            return $err;
        }

        $user = request()->user();
        $submission = $content->documentSubmissions()
            ->where('user_id', $user->id)
            ->orderByDesc('attempt')
            ->first();

        if (!$submission || !$submission->isDraft()) {
            return response()->json(['status' => 'error', 'message' => 'Tidak ada file draft untuk dihapus.'], 422);
        }
        if ($submission->file_path) {
            Storage::disk('public')->delete($submission->file_path);
        }
        $submission->update(['file_path' => null, 'original_name' => null, 'file_size' => null, 'mime_type' => null]);

        return response()->json(['status' => 'success', 'message' => 'File dihapus.', 'data' => $this->serialize($submission->fresh())]);
    }

    /** Kumpulkan (kunci) attempt draft. */
    public function submit(Content $content): JsonResponse
    {
        if (!$content->collectsSubmission()) {
            return response()->json(['status' => 'error', 'message' => 'Pengumpulan tidak aktif.'], 404);
        }
        if ($err = $this->ensureAccess($content, request())) {
            return $err;
        }

        $user = request()->user();
        $submission = $content->documentSubmissions()
            ->where('user_id', $user->id)
            ->orderByDesc('attempt')
            ->first();

        if (!$submission || !$submission->isDraft()) {
            return response()->json(['status' => 'error', 'message' => 'Tidak ada tugas untuk dikumpulkan.'], 422);
        }
        if (!$submission->file_path) {
            return response()->json(['status' => 'error', 'message' => 'Unggah file terlebih dahulu.'], 422);
        }

        $submission->update(['status' => 'submitted', 'submitted_at' => now()]);
        // Bila wajib lulus, penyelesaian menunggu nilai LULUS (di grade()).
        if (!$content->requiresSubmissionPass()) {
            $user->completedContents()->syncWithoutDetaching([
                $content->id => ['completed' => true, 'completed_at' => now()],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Tugas dikumpulkan.',
            'data' => $this->serialize($submission->fresh()),
        ], 201);
    }

    // ------------------------------------------------------------- INSTRUKTUR

    /** Daftar pengumpulan seluruh peserta (dikelompokkan per peserta). */
    public function manage(Content $content): JsonResponse
    {
        if ($content->type !== 'document') {
            return response()->json(['status' => 'error', 'message' => 'Tipe konten tidak valid.'], 404);
        }
        if ($err = $this->ensureManage($content, request())) {
            return $err;
        }

        $subs = $content->documentSubmissions()->with('user')->orderByDesc('attempt')->get();

        $participants = $subs->groupBy('user_id')->map(function ($attempts) use ($content) {
            $attempts = $attempts->sortByDesc('attempt')->values();
            $latest = $attempts->first();
            return [
                'userId' => (string) $latest->user_id,
                'name' => $latest->user->name ?? '-',
                'email' => $latest->user->email ?? '',
                'attemptCount' => $attempts->count(),
                'latestStatus' => $latest->status,
                'submissions' => $attempts->map(fn ($s) => $this->serialize($s))->values(),
            ];
        })->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'contentId' => (string) $content->id,
                'title' => $content->title,
                'scoringEnabled' => (bool) $content->isScoringEnabled(),
                'participants' => $participants,
            ],
        ]);
    }

    /** Simpan penilaian lulus/belum-lulus (+ nilai & feedback). */
    public function grade(Request $request, DocumentSubmission $submission): JsonResponse
    {
        $content = $submission->content;
        if (!$content) {
            return response()->json(['status' => 'error', 'message' => 'Konten tidak ditemukan.'], 404);
        }
        if ($err = $this->ensureManage($content, $request)) {
            return $err;
        }

        $validated = $request->validate([
            'result' => 'required|in:passed,failed',
            'score' => 'nullable|integer|min:0|max:100',
            'feedback' => 'nullable|string|max:5000',
        ]);

        if (!in_array($submission->status, ['submitted', 'passed', 'failed'], true)) {
            return response()->json(['status' => 'error', 'message' => 'Attempt ini belum dikumpulkan.'], 422);
        }

        $submission->update([
            'status' => $validated['result'],
            'score' => $content->isScoringEnabled() ? ($validated['score'] ?? null) : null,
            'feedback' => $validated['feedback'] ?? null,
            'graded_at' => now(),
            'graded_by' => $request->user()->id,
        ]);

        if ($content->requiresSubmissionPass()) {
            if ($validated['result'] === 'passed') {
                $submission->user->completedContents()->syncWithoutDetaching([
                    $content->id => ['completed' => true, 'completed_at' => now()],
                ]);
            } else {
                $submission->user->completedContents()->updateExistingPivot($content->id, ['completed' => false]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => $validated['result'] === 'passed' ? 'Dinilai lulus.' : 'Dinilai belum lulus.',
            'data' => $this->serialize($submission->fresh()),
        ]);
    }

    // ---------------------------------------------------------------- HELPERS

    private function serialize(DocumentSubmission $s): array
    {
        return [
            'submissionId' => (string) $s->id,
            'attempt' => (int) $s->attempt,
            'status' => $s->status,
            'originalName' => $s->original_name,
            'fileUrl' => $s->file_path ? asset('storage/' . $s->file_path) : null,
            'fileSize' => $s->file_size !== null ? (int) $s->file_size : null,
            'score' => $s->score,
            'feedback' => $s->feedback,
            'submittedAt' => optional($s->submitted_at)?->toISOString(),
            'gradedAt' => optional($s->graded_at)?->toISOString(),
        ];
    }

    private function resolveDraftAttempt(Content $content, int $userId): ?DocumentSubmission
    {
        $latest = $content->documentSubmissions()
            ->where('user_id', $userId)
            ->orderByDesc('attempt')
            ->first();

        if ($latest === null) {
            return DocumentSubmission::create([
                'user_id' => $userId, 'content_id' => $content->id, 'attempt' => 1, 'status' => 'draft',
            ]);
        }
        if ($latest->isDraft()) {
            return $latest;
        }
        if ($latest->isFailed()) {
            return DocumentSubmission::create([
                'user_id' => $userId, 'content_id' => $content->id, 'attempt' => $latest->attempt + 1, 'status' => 'draft',
            ]);
        }
        return null; // submitted / passed -> terkunci
    }

    private function allowedTypes(Content $content): string
    {
        $t = trim((string) ($content->submission_allowed_types ?? ''));
        return $t !== '' ? $t : self::DEFAULT_TYPES;
    }

    private function maxSizeMb(Content $content): int
    {
        return (int) ($content->submission_max_size_mb ?: self::DEFAULT_MAX_MB);
    }

    /** Akses peserta (enrolled) atau pengelola. */
    private function ensureAccess(Content $content, Request $request): ?JsonResponse
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

    /** Hanya pengelola course (instruktur/admin) yang boleh menilai. */
    private function ensureManage(Content $content, Request $request): ?JsonResponse
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
        if ($user->can('manage all courses') || $user->isInstructorFor($course)) {
            return null;
        }
        return response()->json(['status' => 'error', 'message' => 'Tidak diizinkan.'], 403);
    }
}
