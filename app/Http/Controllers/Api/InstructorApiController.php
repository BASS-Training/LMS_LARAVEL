<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use App\Models\EssayAnswer;
use App\Models\EssaySubmission;
use App\Models\CaseStudySubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Mobile endpoints for the instructor / admin role.
 *
 * Scope (mirrors the web, kept intentionally limited for mobile):
 * - list participants + their progress for a course
 * - list essay & case-study submissions that need grading
 * - view a submission and grade it (score + feedback)
 *
 * All writes go to the SAME tables the web grading screens use
 * (essay_answers / essay_submissions / case_study_submissions), so grades made
 * from mobile show up on the web instantly and vice-versa.
 */
class InstructorApiController extends Controller
{
    /**
     * Daftar peserta + progres ringkas untuk sebuah course.
     */
    public function participants(Request $request, Course $course)
    {
        $this->authorizeCourse($request->user(), $course);

        $participants = $course->enrolledUsers()
            ->orderBy('name')
            ->get();

        $pending = $this->pendingGradingCountsByUser($course);

        $data = $participants->map(function (User $participant) use ($course, $pending) {
            $progress = $participant->getProgressForCourse($course);

            return [
                'id' => (string) $participant->id,
                'name' => $participant->name,
                'email' => $participant->email,
                'progressPercentage' => (float) ($progress['progress_percentage'] ?? 0),
                'completedContents' => (int) ($progress['completed_contents'] ?? 0),
                'totalContents' => (int) ($progress['total_contents'] ?? 0),
                'completedLessons' => (int) ($progress['completed_lessons'] ?? 0),
                'pendingGrading' => (int) ($pending[$participant->id] ?? 0),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * Antrian penilaian: semua submission essay + studi kasus dalam course,
     * masing-masing ditandai 'pending' (perlu dinilai) atau 'graded'.
     */
    public function gradingQueue(Request $request, Course $course)
    {
        $this->authorizeCourse($request->user(), $course);

        $contentIds = $course->lessons()
            ->with('contents:id,lesson_id,title,type,scoring_enabled,requires_review,grading_mode')
            ->get()
            ->pluck('contents')
            ->flatten();

        $essayContentIds = $contentIds->where('type', 'essay')->pluck('id')->all();
        $caseContentIds = $contentIds->where('type', 'case_study')->pluck('id')->all();
        $lessonTitleByContentId = $course->lessons()->with('contents:id,lesson_id')->get()
            ->flatMap(fn ($lesson) => $lesson->contents->mapWithKeys(
                fn ($c) => [$c->id => $lesson->title]
            ))->all();

        $items = [];

        if (!empty($essayContentIds)) {
            $essaySubs = EssaySubmission::whereIn('content_id', $essayContentIds)
                ->with(['user:id,name', 'content', 'answers'])
                ->latest()
                ->get();

            foreach ($essaySubs as $sub) {
                if ($sub->answers->isEmpty()) {
                    continue; // belum benar-benar mengerjakan
                }
                $items[] = [
                    'id' => 'essay-' . $sub->id,
                    'submissionId' => (string) $sub->id,
                    'type' => 'essay',
                    'participantId' => (string) $sub->user_id,
                    'participantName' => $sub->user?->name ?? 'Peserta',
                    'contentId' => (string) $sub->content_id,
                    'contentTitle' => $sub->content?->title ?? 'Essay',
                    'lessonTitle' => $lessonTitleByContentId[$sub->content_id] ?? '',
                    'scoringEnabled' => (bool) ($sub->content?->scoring_enabled ?? true),
                    'status' => $sub->isProcessedByInstructor() ? 'graded' : 'pending',
                    'submittedAt' => optional($sub->created_at)?->toISOString(),
                ];
            }
        }

        if (!empty($caseContentIds)) {
            $caseSubs = CaseStudySubmission::whereIn('content_id', $caseContentIds)
                ->whereIn('status', ['submitted', 'graded'])
                ->with(['user:id,name', 'content'])
                ->latest('submitted_at')
                ->get();

            foreach ($caseSubs as $sub) {
                $items[] = [
                    'id' => 'cs-' . $sub->id,
                    'submissionId' => (string) $sub->id,
                    'type' => 'case_study',
                    'participantId' => (string) $sub->user_id,
                    'participantName' => $sub->user?->name ?? 'Peserta',
                    'contentId' => (string) $sub->content_id,
                    'contentTitle' => $sub->content?->title ?? 'Studi Kasus',
                    'lessonTitle' => $lessonTitleByContentId[$sub->content_id] ?? '',
                    'scoringEnabled' => (bool) ($sub->content?->scoring_enabled ?? true),
                    'status' => $sub->status === 'graded' ? 'graded' : 'pending',
                    'submittedAt' => optional($sub->submitted_at ?? $sub->updated_at)?->toISOString(),
                ];
            }
        }

        // Pending dulu, lalu terbaru.
        usort($items, function ($a, $b) {
            if ($a['status'] !== $b['status']) {
                return $a['status'] === 'pending' ? -1 : 1;
            }
            return strcmp($b['submittedAt'] ?? '', $a['submittedAt'] ?? '');
        });

        return response()->json([
            'status' => 'success',
            'data' => array_values($items),
        ]);
    }

    /**
     * Detail satu submission essay untuk dinilai (pertanyaan + jawaban + nilai/feedback saat ini).
     */
    public function essaySubmission(Request $request, EssaySubmission $submission)
    {
        $course = $submission->content?->lesson?->course;
        abort_if(!$course, 404, 'Course tidak ditemukan.');
        $this->authorizeCourse($request->user(), $course);

        $content = $submission->content;
        $submission->load(['user:id,name', 'answers']);
        $questions = $content->allEssayQuestions()->get()->keyBy('id');

        $answers = $submission->answers->values()->map(function ($answer) use ($questions) {
            $q = $questions->get($answer->question_id);
            return [
                'answerId' => (string) $answer->id,
                'questionId' => (string) $answer->question_id,
                'question' => $q?->question ?? '',
                'maxScore' => (int) ($q?->max_score ?? 0),
                'answer' => $answer->answer ?? '',
                'score' => $answer->score,
                'feedback' => $answer->feedback,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'submissionId' => (string) $submission->id,
                'participantName' => $submission->user?->name ?? 'Peserta',
                'contentId' => (string) $content->id,
                'contentTitle' => $content->title,
                'scoringEnabled' => (bool) ($content->scoring_enabled ?? true),
                'gradingMode' => $content->grading_mode ?? 'individual',
                'requiresReview' => (bool) ($content->requires_review ?? true),
                'status' => $submission->status,
                'isFullyGraded' => (bool) $submission->is_fully_graded,
                'totalScore' => $submission->total_score,
                'maxTotalScore' => $submission->max_total_score,
                'answers' => $answers,
            ],
        ]);
    }

    /**
     * Simpan nilai/feedback essay. Mengikuti aturan web: per-soal vs keseluruhan,
     * dengan atau tanpa scoring.
     */
    public function gradeEssay(Request $request, EssaySubmission $submission)
    {
        $course = $submission->content?->lesson?->course;
        abort_if(!$course, 404, 'Course tidak ditemukan.');
        $this->authorizeCourse($request->user(), $course);

        $content = $submission->content;
        $scoringEnabled = (bool) ($content->scoring_enabled ?? true);
        $overallMode = ($content->grading_mode ?? 'individual') === 'overall';

        $validated = $request->validate([
            'overallScore' => 'nullable|integer|min:0',
            'overallFeedback' => 'nullable|string',
            'grades' => 'nullable|array',
            'grades.*.answerId' => 'required_with:grades|integer',
            'grades.*.score' => 'nullable|integer|min:0',
            'grades.*.feedback' => 'nullable|string',
        ]);

        DB::transaction(function () use ($submission, $content, $scoringEnabled, $overallMode, $validated) {
            if ($overallMode) {
                $this->applyOverallGrade($submission, $scoringEnabled, $validated);
            } else {
                $this->applyIndividualGrades($submission, $content, $scoringEnabled, $validated);
            }
        });

        $submission->refresh()->load('answers');

        return response()->json([
            'status' => 'success',
            'message' => 'Penilaian tersimpan',
            'data' => [
                'submissionId' => (string) $submission->id,
                'status' => $submission->status,
                'isFullyGraded' => (bool) $submission->is_fully_graded,
                'totalScore' => $submission->total_score,
                'maxTotalScore' => $submission->max_total_score,
            ],
        ]);
    }

    private function applyOverallGrade(EssaySubmission $submission, bool $scoringEnabled, array $validated): void
    {
        $answers = $submission->answers;
        if ($answers->isEmpty()) {
            return;
        }

        $first = $answers->first();
        $firstUpdate = ['feedback' => $validated['overallFeedback'] ?? null];
        if ($scoringEnabled) {
            $firstUpdate['score'] = $validated['overallScore'] ?? 0;
        }
        $first->update($firstUpdate);

        // Sisanya: tandai dinilai keseluruhan (samakan dgn perilaku web).
        foreach ($answers->skip(1) as $answer) {
            $rest = ['feedback' => 'Dinilai secara keseluruhan. Lihat feedback pada soal pertama.'];
            if ($scoringEnabled) {
                $rest['score'] = 0;
            }
            $answer->update($rest);
        }

        $submission->update([
            'graded_at' => now(),
            'status' => $scoringEnabled ? 'graded' : 'reviewed',
        ]);
    }

    private function applyIndividualGrades(EssaySubmission $submission, $content, bool $scoringEnabled, array $validated): void
    {
        $grades = collect($validated['grades'] ?? []);

        foreach ($grades as $grade) {
            $answer = EssayAnswer::where('id', $grade['answerId'])
                ->where('submission_id', $submission->id)
                ->first();
            if (!$answer) {
                continue;
            }

            $update = [];
            if (array_key_exists('feedback', $grade)) {
                $update['feedback'] = $grade['feedback'];
            }
            if ($scoringEnabled && array_key_exists('score', $grade) && $grade['score'] !== null) {
                $update['score'] = (int) $grade['score'];
            }
            if (!empty($update)) {
                $answer->update($update);
            }
        }

        $totalQuestions = $content->essayQuestions()->count();

        if ($scoringEnabled) {
            $gradedAnswers = $submission->answers()->whereNotNull('score')->count();
            if ($totalQuestions > 0 && $gradedAnswers >= $totalQuestions) {
                $submission->update(['graded_at' => now(), 'status' => 'graded']);
            }
        } else {
            $withFeedback = $submission->answers()->whereNotNull('feedback')->count();
            if ($totalQuestions > 0 && $withFeedback >= $totalQuestions) {
                $submission->update(['graded_at' => now(), 'status' => 'reviewed']);
            }
        }
    }

    /**
     * Detail satu submission studi kasus untuk ditinjau (template + jawaban + nilai/feedback).
     */
    public function caseStudySubmission(Request $request, CaseStudySubmission $submission)
    {
        $course = $submission->content?->lesson?->course;
        abort_if(!$course, 404, 'Course tidak ditemukan.');
        $this->authorizeCourse($request->user(), $course);

        $content = $submission->content;
        $submission->load('user:id,name');

        return response()->json([
            'status' => 'success',
            'data' => [
                'submissionId' => (string) $submission->id,
                'participantName' => $submission->user?->name ?? 'Peserta',
                'contentId' => (string) $content->id,
                'contentTitle' => $content->title,
                'scoringEnabled' => (bool) ($content->scoring_enabled ?? true),
                'status' => $submission->status,
                'score' => $submission->score,
                'feedback' => $submission->feedback,
                'template' => $content->case_study_template,
                'answers' => $submission->answers ?? (object) [],
                'submittedAt' => optional($submission->submitted_at)?->toISOString(),
            ],
        ]);
    }

    /**
     * Simpan nilai & feedback studi kasus (status -> graded). Mirror CaseStudyController@grade.
     */
    public function gradeCaseStudy(Request $request, CaseStudySubmission $submission)
    {
        $course = $submission->content?->lesson?->course;
        abort_if(!$course, 404, 'Course tidak ditemukan.');
        $this->authorizeCourse($request->user(), $course);

        $content = $submission->content;

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

        return response()->json([
            'status' => 'success',
            'message' => 'Penilaian tersimpan',
            'data' => [
                'submissionId' => (string) $submission->id,
                'status' => $submission->status,
                'score' => $submission->score,
                'feedback' => $submission->feedback,
            ],
        ]);
    }

    /**
     * Instructor hanya boleh course yang diampu; admin (manage all courses) semua.
     */
    private function authorizeCourse(?User $user, Course $course): void
    {
        abort_if(!$user, 401, 'Unauthenticated.');

        if ($user->can('manage all courses') || $user->isInstructorFor($course)) {
            return;
        }

        abort(403, 'Anda bukan instruktur untuk course ini.');
    }

    /**
     * Hitung jumlah submission yang perlu dinilai (essay + studi kasus) per user
     * untuk course ini, dalam beberapa query (hindari N+1).
     */
    private function pendingGradingCountsByUser(Course $course): array
    {
        $contents = $course->lessons()->with('contents:id,lesson_id,type,scoring_enabled,requires_review,grading_mode')
            ->get()->pluck('contents')->flatten();

        $counts = [];

        // Essay: perlu dinilai jika belum diproses instruktur.
        $essayContentIds = $contents->where('type', 'essay')->pluck('id')->all();
        if (!empty($essayContentIds)) {
            $essaySubs = EssaySubmission::whereIn('content_id', $essayContentIds)
                ->with(['content', 'answers'])
                ->get();
            foreach ($essaySubs as $sub) {
                if ($sub->answers->isNotEmpty() && !$sub->isProcessedByInstructor()) {
                    $counts[$sub->user_id] = ($counts[$sub->user_id] ?? 0) + 1;
                }
            }
        }

        // Studi kasus: perlu dinilai jika status 'submitted'.
        $caseContentIds = $contents->where('type', 'case_study')->pluck('id')->all();
        if (!empty($caseContentIds)) {
            $caseRows = CaseStudySubmission::whereIn('content_id', $caseContentIds)
                ->where('status', 'submitted')
                ->get(['user_id']);
            foreach ($caseRows as $row) {
                $counts[$row->user_id] = ($counts[$row->user_id] ?? 0) + 1;
            }
        }

        return $counts;
    }
}
