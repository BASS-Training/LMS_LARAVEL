<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\FeedbackSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FeedbackController extends Controller
{
    /**
     * Simpan tanggapan form feedback peserta (tanpa penilaian). Boleh diisi
     * ulang selama konten masih terbuka — jawaban lama diganti.
     */
    public function store(Request $request, Content $content)
    {
        abort_if($content->type !== 'feedback', 404);

        $user = Auth::user();
        $questions = $content->feedbackQuestions()->get();
        $answers = $request->input('answers', []); // answers[questionId] => value

        // Validasi pertanyaan wajib.
        $missing = [];
        foreach ($questions as $q) {
            if (! $q->is_required) {
                continue;
            }
            if ($this->isEmptyAnswer($answers[$q->id] ?? null)) {
                $missing[] = $q->question;
            }
        }
        if (! empty($missing)) {
            $preview = implode('; ', array_slice($missing, 0, 3)).(count($missing) > 3 ? '…' : '');

            return back()->with('error', 'Pertanyaan wajib belum diisi: '.$preview)->withInput();
        }

        DB::transaction(function () use ($content, $user, $questions, $answers) {
            $submission = FeedbackSubmission::updateOrCreate(
                ['user_id' => $user->id, 'content_id' => $content->id],
                ['status' => 'submitted', 'submitted_at' => now()]
            );

            // Ganti jawaban lama.
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

                // Lewati jawaban yang benar-benar kosong.
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
        });

        // Tandai konten selesai (feedback tidak butuh review).
        $user->completedContents()->syncWithoutDetaching([
            $content->id => ['completed' => true, 'completed_at' => now()],
        ]);

        return redirect()->route('contents.show', $content)
            ->with('success', 'Terima kasih! Tanggapan Anda berhasil dikirim.');
    }

    /**
     * Ringkasan agregat tanggapan untuk instruktur/admin (rata-rata rating,
     * distribusi pilihan, daftar jawaban teks). Menghormati flag anonim secara
     * default — hanya menampilkan agregat, bukan identitas penjawab.
     */
    public function results(Content $content)
    {
        abort_if($content->type !== 'feedback', 404);
        $this->authorize('grade', $content->lesson->course);

        $questions = $content->feedbackQuestions()->with('answers')->get();
        $submissionsCount = $content->feedbackSubmissions()->count();

        $summary = [];
        foreach ($questions as $q) {
            $entry = ['question' => $q, 'type' => $q->type, 'count' => 0];

            if ($q->type === 'rating') {
                $ratings = $q->answers->pluck('rating_value')->filter(fn ($v) => $v !== null);
                $max = (int) ($q->config['max'] ?? 5);
                $dist = [];
                for ($i = 1; $i <= $max; $i++) {
                    $dist[$i] = $ratings->filter(fn ($v) => (int) $v === $i)->count();
                }
                $entry['count'] = $ratings->count();
                $entry['avg'] = $ratings->count() ? round($ratings->avg(), 2) : null;
                $entry['dist'] = $dist;
                $entry['max'] = $max;
            } elseif (in_array($q->type, ['single_choice', 'multi_choice'], true)) {
                $tally = [];
                foreach (($q->config['options'] ?? []) as $opt) {
                    $tally[$opt['id']] = ['label' => $opt['label'] ?? $opt['id'], 'count' => 0];
                }
                foreach ($q->answers as $a) {
                    foreach (($a->choice_value ?? []) as $oid) {
                        if (isset($tally[$oid])) {
                            $tally[$oid]['count']++;
                        }
                    }
                }
                $entry['tally'] = $tally;
                $entry['count'] = $q->answers->count();
            } else { // text
                $texts = $q->answers->pluck('text_value')->filter()->values();
                $entry['texts'] = $texts;
                $entry['count'] = $texts->count();
            }

            $summary[] = $entry;
        }

        return view('feedback.results', compact('content', 'summary', 'submissionsCount'));
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
}
