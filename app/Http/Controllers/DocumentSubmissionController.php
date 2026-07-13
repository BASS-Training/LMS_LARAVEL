<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\DocumentSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Pengumpulan tugas berupa dokumen oleh peserta (konten tipe 'document' dengan
 * collect_submission = true), plus penilaian oleh instruktur/admin.
 *
 * Model "coba lagi otomatis":
 *   - Peserta menyiapkan attempt (draft): upload / ganti / hapus file bebas.
 *   - Menekan "Kumpulkan" -> status 'submitted' (TERKUNCI, menunggu nilai).
 *   - Instruktur menilai -> 'passed' (final) atau 'failed' (belum lulus).
 *   - Bila 'failed', peserta boleh membuat attempt baru (upload lagi).
 */
class DocumentSubmissionController extends Controller
{
    /** Ekstensi default bila konten tidak menentukan sendiri. */
    private const DEFAULT_TYPES = 'pdf,doc,docx,ppt,pptx,xls,xlsx,txt,jpg,jpeg,png,zip,rar';

    /** Batas ukuran default (MB). */
    private const DEFAULT_MAX_MB = 20;

    /**
     * Upload / ganti file untuk attempt aktif peserta.
     * Membuat attempt baru otomatis bila attempt sebelumnya sudah 'failed'.
     */
    public function upload(Request $request, Content $content)
    {
        abort_unless($content->collectsSubmission(), 404, 'Pengumpulan tidak aktif untuk konten ini.');

        $user = Auth::user();
        $allowedTypes = $this->allowedTypes($content);
        $maxKb = $this->maxSizeMb($content) * 1024;

        $request->validate([
            'file' => "required|file|mimes:{$allowedTypes}|max:{$maxKb}",
        ], [
            'file.mimes' => 'Tipe file tidak diizinkan. Diperbolehkan: ' . strtoupper(str_replace(',', ', ', $allowedTypes)) . '.',
            'file.max' => 'Ukuran file melebihi batas maksimum ' . $this->maxSizeMb($content) . ' MB.',
        ]);

        $submission = $this->resolveDraftAttempt($content, $user->id);

        if ($submission === null) {
            return back()->with('error', 'Pengumpulan Anda sedang menunggu penilaian atau sudah lulus.');
        }

        // Hapus file lama bila mengganti dalam draft yang sama.
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

        return back()->with('success', 'File berhasil diupload. Jangan lupa tekan "Kumpulkan" bila sudah final.');
    }

    /**
     * Hapus file pada attempt draft (belum dikumpulkan).
     */
    public function removeFile(Content $content)
    {
        abort_unless($content->collectsSubmission(), 404);

        $user = Auth::user();
        $submission = $content->documentSubmissions()
            ->where('user_id', $user->id)
            ->orderByDesc('attempt')
            ->first();

        abort_if(!$submission, 404, 'Belum ada file.');
        abort_unless($submission->isDraft(), 403, 'File sudah dikumpulkan dan tidak bisa dihapus.');

        if ($submission->file_path) {
            Storage::disk('public')->delete($submission->file_path);
        }
        $submission->update(['file_path' => null, 'original_name' => null, 'file_size' => null, 'mime_type' => null]);

        return back()->with('success', 'File dihapus.');
    }

    /**
     * Kumpulkan (kunci) attempt draft peserta.
     */
    public function submit(Content $content)
    {
        abort_unless($content->collectsSubmission(), 404);

        $user = Auth::user();
        $submission = $content->documentSubmissions()
            ->where('user_id', $user->id)
            ->orderByDesc('attempt')
            ->first();

        abort_if(!$submission, 404, 'Belum ada file untuk dikumpulkan.');
        abort_unless($submission->isDraft(), 403, 'Pengumpulan sudah terkunci.');

        if (!$submission->file_path) {
            return back()->with('error', 'Unggah file terlebih dahulu sebelum mengumpulkan.');
        }

        $submission->update(['status' => 'submitted', 'submitted_at' => now()]);

        // Tandai konten selesai agar peserta bisa melanjutkan (konsisten dgn
        // case_study). Penilaian lulus/belum-lulus terpisah dari completion.
        $user->completedContents()->syncWithoutDetaching([
            $content->id => ['completed' => true, 'completed_at' => now()],
        ]);

        return back()->with('success', 'Tugas berhasil dikumpulkan. Menunggu penilaian instruktur.');
    }

    /**
     * Unduh file sebuah submission (pemilik atau pengelola course).
     */
    public function download(Content $content, DocumentSubmission $submission)
    {
        abort_if($submission->content_id !== $content->id, 404);

        $user = Auth::user();
        $isOwner = $submission->user_id === $user->id;
        $canManage = $user->can('manage own courses') || $user->can('update contents');
        abort_unless($isOwner || $canManage, 403);
        abort_if(!$submission->file_path || !Storage::disk('public')->exists($submission->file_path), 404, 'File tidak ditemukan.');

        return Storage::disk('public')->download(
            $submission->file_path,
            $submission->original_name ?: basename($submission->file_path)
        );
    }

    /**
     * Daftar pengumpulan (instruktur/admin) — attempt terbaru tiap peserta.
     */
    public function submissions(Content $content)
    {
        abort_if($content->type !== 'document', 404);
        $this->authorize('grade', $content->lesson->course);

        // Semua attempt, dikelompokkan per peserta (dipakai di view).
        $submissions = $content->documentSubmissions()
            ->with(['user', 'grader'])
            ->orderBy('user_id')
            ->orderByDesc('attempt')
            ->get();

        return view('document-submissions.index', compact('content', 'submissions'));
    }

    /**
     * Simpan penilaian: lulus / belum lulus (+ nilai & feedback).
     */
    public function grade(Request $request, Content $content, DocumentSubmission $submission)
    {
        abort_if($submission->content_id !== $content->id, 404);
        $this->authorize('grade', $content->lesson->course);

        $validated = $request->validate([
            'result' => 'required|in:passed,failed',
            'score' => 'nullable|integer|min:0|max:100',
            'feedback' => 'nullable|string|max:5000',
        ]);

        // Hanya attempt yang sudah dikumpulkan yang bisa dinilai.
        abort_unless($submission->isSubmitted() || $submission->isGraded(), 422, 'Attempt ini belum dikumpulkan.');

        $submission->update([
            'status' => $validated['result'],
            'score' => $content->isScoringEnabled() ? ($validated['score'] ?? null) : null,
            'feedback' => $validated['feedback'] ?? null,
            'graded_at' => now(),
            'graded_by' => Auth::id(),
        ]);

        $msg = $validated['result'] === 'passed'
            ? 'Peserta dinilai LULUS.'
            : 'Peserta dinilai belum lulus — peserta dapat mengunggah percobaan berikutnya.';

        return back()->with('success', $msg);
    }

    // ----------------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------------

    /**
     * Ambil attempt draft yang bisa diisi peserta, atau buat attempt baru bila
     * attempt terakhir sudah 'failed'. Mengembalikan null bila terkunci
     * (submitted/passed) sehingga peserta belum boleh mengunggah.
     */
    private function resolveDraftAttempt(Content $content, int $userId): ?DocumentSubmission
    {
        $latest = $content->documentSubmissions()
            ->where('user_id', $userId)
            ->orderByDesc('attempt')
            ->first();

        if ($latest === null) {
            return DocumentSubmission::create([
                'user_id' => $userId,
                'content_id' => $content->id,
                'attempt' => 1,
                'status' => 'draft',
            ]);
        }

        if ($latest->isDraft()) {
            return $latest;
        }

        if ($latest->isFailed()) {
            return DocumentSubmission::create([
                'user_id' => $userId,
                'content_id' => $content->id,
                'attempt' => $latest->attempt + 1,
                'status' => 'draft',
            ]);
        }

        // submitted (menunggu nilai) atau passed (final) -> terkunci.
        return null;
    }

    private function allowedTypes(Content $content): string
    {
        $types = trim((string) ($content->submission_allowed_types ?? ''));
        return $types !== '' ? $types : self::DEFAULT_TYPES;
    }

    private function maxSizeMb(Content $content): int
    {
        return (int) ($content->submission_max_size_mb ?: self::DEFAULT_MAX_MB);
    }
}
