<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\CaseStudySubmission;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CaseStudyController extends Controller
{
    /**
     * Simpan / kumpulkan jawaban studi kasus peserta.
     */
    public function store(Request $request, Content $content)
    {
        if ($content->type !== 'case_study') {
            return back()->with('error', 'Tipe konten tidak valid.');
        }

        $user = Auth::user();
        $answers = $this->normalizeAnswers($request->input('answers', []));

        $submission = CaseStudySubmission::updateOrCreate(
            ['user_id' => $user->id, 'content_id' => $content->id],
            [
                'answers' => $answers,
                'status' => 'submitted',
                'submitted_at' => now(),
                'graded_at' => null,
                'pdf_path' => null,
            ]
        );

        // Tandai konten selesai.
        $user->completedContents()->syncWithoutDetaching([
            $content->id => ['completed' => true, 'completed_at' => now()],
        ]);

        return redirect()->route('contents.show', $content)
            ->with('success', 'Jawaban studi kasus berhasil dikumpulkan.');
    }

    /**
     * Autosave draft jawaban (tidak mengubah status jika sudah submitted).
     */
    public function autosave(Request $request, Content $content)
    {
        if ($content->type !== 'case_study') {
            return response()->json(['message' => 'Tipe konten tidak valid.'], 422);
        }

        $user = Auth::user();
        $answers = $this->normalizeAnswers($request->input('answers', []));

        $existing = CaseStudySubmission::where('user_id', $user->id)
            ->where('content_id', $content->id)
            ->first();

        // Jangan timpa status submitted/graded menjadi draft.
        $status = ($existing && in_array($existing->status, ['submitted', 'graded'], true))
            ? $existing->status
            : 'draft';

        $submission = CaseStudySubmission::updateOrCreate(
            ['user_id' => $user->id, 'content_id' => $content->id],
            ['answers' => $answers, 'status' => $status]
        );

        return response()->json([
            'message' => 'Draft tersimpan.',
            'saved_at' => now()->toISOString(),
            'status' => $submission->status,
        ]);
    }

    /**
     * Unduh jawaban peserta sebagai PDF (jika diizinkan & sudah dikumpulkan).
     */
    public function download(Content $content, ?CaseStudySubmission $submission = null)
    {
        $user = Auth::user();

        // Jika submission tidak dikirim eksplisit, ambil milik user yang login.
        if (!$submission || !$submission->exists) {
            $submission = CaseStudySubmission::where('content_id', $content->id)
                ->where('user_id', $user->id)
                ->first();
        }

        abort_if(!$submission, 404, 'Jawaban tidak ditemukan.');

        // Otorisasi: pemilik jawaban, atau pengelola course (instruktur/admin).
        $isOwner = $submission->user_id === $user->id;
        $canManage = $user->can('manage own courses') || $user->can('update contents');
        abort_unless($isOwner || $canManage, 403);

        // Peserta hanya boleh unduh jika diizinkan & sudah submit.
        if ($isOwner && !$canManage) {
            abort_unless($content->allow_answer_download, 403, 'Pengunduhan jawaban tidak diizinkan.');
            abort_unless($submission->isSubmitted(), 403, 'Jawaban belum dikumpulkan.');
        }

        $template = $content->case_study_template;
        $answers = $submission->answers ?? [];

        $pdf = Pdf::loadView('case-studies.pdf', [
            'content' => $content,
            'template' => $template,
            'answers' => $answers,
            'submission' => $submission,
            'participant' => $submission->user,
        ])->setPaper('a4', 'portrait');

        $filename = 'studi-kasus-' . $content->id . '-' . ($submission->user->name ?? 'peserta') . '.pdf';
        $filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $filename);

        return $pdf->download($filename);
    }

    /**
     * Daftar pengumpulan studi kasus untuk sebuah konten (untuk instruktur/admin).
     */
    public function submissions(Content $content)
    {
        abort_if($content->type !== 'case_study', 404);
        $this->authorize('update', $content->lesson->course);

        $submissions = $content->caseStudySubmissions()
            ->with('user')
            ->orderByDesc('submitted_at')
            ->orderByDesc('updated_at')
            ->get();

        return view('case-studies.submissions', compact('content', 'submissions'));
    }

    /**
     * Tampilkan satu jawaban peserta untuk ditinjau & dinilai.
     */
    public function review(Content $content, CaseStudySubmission $submission)
    {
        abort_if($content->type !== 'case_study', 404);
        abort_if($submission->content_id !== $content->id, 404);
        $this->authorize('update', $content->lesson->course);

        $submission->load('user');
        $template = $content->case_study_template;
        $answers = $submission->answers ?? [];

        return view('case-studies.review', compact('content', 'submission', 'template', 'answers'));
    }

    /**
     * Simpan nilai & feedback (status -> graded).
     */
    public function grade(Request $request, Content $content, CaseStudySubmission $submission)
    {
        abort_if($content->type !== 'case_study', 404);
        abort_if($submission->content_id !== $content->id, 404);
        $this->authorize('update', $content->lesson->course);

        $validated = $request->validate([
            'score' => 'nullable|integer|min:0|max:100',
            'feedback' => 'nullable|string|max:5000',
        ]);

        $submission->update([
            'score' => $content->scoring_enabled ? ($validated['score'] ?? null) : null,
            'feedback' => $validated['feedback'] ?? null,
            'status' => 'graded',
            'graded_at' => now(),
            'graded_by' => Auth::id(),
        ]);

        return redirect()->route('case-studies.submissions', $content)
            ->with('success', 'Penilaian tersimpan.');
    }

    /**
     * Pastikan struktur answers berupa array (mengatasi input form bertingkat).
     */
    private function normalizeAnswers($answers): array
    {
        if (is_string($answers)) {
            $decoded = json_decode($answers, true);
            return is_array($decoded) ? $decoded : [];
        }
        return is_array($answers) ? $answers : [];
    }
}
