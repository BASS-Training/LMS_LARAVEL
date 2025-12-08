<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\CertificateController;
use App\Jobs\BulkForceCompleteJob;
use App\Jobs\BulkGenerateCertificatesJob;
use App\Models\Course;
use App\Models\User;
use App\Models\Content;
use App\Models\QuizAttempt;
use App\Models\EssaySubmission;
use App\Models\EssayAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ForceCompleteController extends Controller
{
    /**
     * Show the Force Complete interface
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Course::class);

        // Batasi jumlah data per halaman agar halaman tetap ringan
        $perPage = (int) $request->get('per_page', 50);
        $perPage = max(10, min($perPage, 200));

        // Ambil daftar kursus minimal (id, title) untuk dropdown agar ringan
        $courses = Course::query()->select('id', 'title')->orderBy('title')->get();
        $selectedCourse = null;
        $participants = collect();

        if ($request->has('course_id') && $request->course_id) {
            $selectedCourse = Course::with(['lessons.contents' => function ($q) {
                $q->select('id', 'lesson_id', 'type', 'quiz_id', 'scoring_enabled', 'grading_mode', 'requires_review', 'order');
            }])->find($request->course_id);

            if ($selectedCourse) {
                // Hitung konten kursus sekali
                $allContents = $selectedCourse->lessons->flatMap(function ($lesson) {
                    return $lesson->contents;
                });

                $totalContents = $allContents->count();
                $allContentIds = $allContents->pluck('id');
                $quizIds = $allContents->where('type', 'quiz')->pluck('quiz_id')->filter();
                $essayContentIds = $allContents->where('type', 'essay')->pluck('id');
                $regularContentIds = $allContents->whereNotIn('type', ['quiz', 'essay'])->pluck('id');

                // Preload jumlah pertanyaan essay per content untuk evaluasi cepat
                $essayQuestionCounts = \App\Models\EssayQuestion::whereIn('content_id', $essayContentIds)
                    ->select('content_id', DB::raw('COUNT(*) as qcount'))
                    ->groupBy('content_id')
                    ->pluck('qcount', 'content_id');

                // Ambil peserta dengan pagination agar request ringan
                $participantsPaginator = $selectedCourse->enrolledUsers()
                    ->select('users.id', 'users.name', 'users.email')
                    ->orderBy('users.name')
                    ->paginate($perPage)
                    ->withQueryString();

                // Jika ada peserta di halaman ini, hitung progres dengan query agregasi
                if ($participantsPaginator->count() > 0) {
                    $participantIds = $participantsPaginator->getCollection()->pluck('id');

                    // Map content completion untuk konten non-quiz/non-essay
                    $completedContentMap = DB::table('content_user')
                        ->whereIn('content_id', $regularContentIds)
                        ->whereIn('user_id', $participantIds)
                        ->where('completed', true)
                        ->select('user_id', 'content_id')
                        ->get()
                        ->groupBy('user_id')
                        ->map(function ($rows) {
                            return array_fill_keys($rows->pluck('content_id')->all(), true);
                        })
                        ->toArray();

                    // Map quiz attempts yang sudah lulus
                    $quizPassedMap = [];
                    if ($quizIds->isNotEmpty()) {
                        $quizPassedMap = DB::table('quiz_attempts')
                            ->whereIn('quiz_id', $quizIds)
                            ->whereIn('user_id', $participantIds)
                            ->where('passed', true)
                            ->select('user_id', 'quiz_id')
                            ->get()
                            ->groupBy('user_id')
                            ->map(function ($rows) {
                                return array_fill_keys($rows->pluck('quiz_id')->unique()->all(), true);
                            })
                            ->toArray();
                    }

                    // Map status essay completion per user-content
                    $essayCompletionMap = [];
                    if ($essayContentIds->isNotEmpty()) {
                        $essaySubmissions = DB::table('essay_submissions as es')
                            ->join('contents as c', 'c.id', '=', 'es.content_id')
                            ->whereIn('es.content_id', $essayContentIds)
                            ->whereIn('es.user_id', $participantIds)
                            ->select(
                                'es.id',
                                'es.user_id',
                                'es.content_id',
                                'es.status',
                                'es.graded_at',
                                'c.scoring_enabled',
                                'c.grading_mode',
                                'c.requires_review'
                            )
                            ->get();

                        $answerCounts = DB::table('essay_answers as ea')
                            ->join('essay_submissions as es', 'es.id', '=', 'ea.submission_id')
                            ->whereIn('es.user_id', $participantIds)
                            ->whereIn('es.content_id', $essayContentIds)
                            ->select(
                                'es.user_id',
                                'es.content_id',
                                DB::raw('COUNT(*) as total_answers'),
                                DB::raw('SUM(CASE WHEN ea.score IS NOT NULL THEN 1 ELSE 0 END) as scored_answers'),
                                DB::raw('SUM(CASE WHEN ea.feedback IS NOT NULL AND ea.feedback <> \'\' THEN 1 ELSE 0 END) as feedback_answers')
                            )
                            ->groupBy('es.user_id', 'es.content_id')
                            ->get()
                            ->keyBy(function ($row) {
                                return $row->user_id . '_' . $row->content_id;
                            });

                        foreach ($essaySubmissions as $submission) {
                            $key = $submission->user_id . '_' . $submission->content_id;
                            $counts = $answerCounts[$key] ?? null;

                            $totalQuestions = (int)($essayQuestionCounts->get($submission->content_id, 0));
                            $requiresReview = ($submission->requires_review ?? true) ? true : false;
                            $scoringEnabled = $submission->scoring_enabled ?? true;
                            $gradingMode = $submission->grading_mode ?? 'individual';

                            $totalAnswers = $counts->total_answers ?? 0;
                            $scoredAnswers = $counts->scored_answers ?? 0;
                            $feedbackAnswers = $counts->feedback_answers ?? 0;

                            $isCompleted = false;
                            if ($totalQuestions === 0) {
                                $isCompleted = $totalAnswers > 0;
                            } elseif (!$requiresReview) {
                                $isCompleted = $totalAnswers > 0;
                            } else {
                                if (!$scoringEnabled) {
                                    $isCompleted = $gradingMode === 'overall'
                                        ? $feedbackAnswers > 0
                                        : $feedbackAnswers >= $totalQuestions;
                                } else {
                                    $isCompleted = $gradingMode === 'overall'
                                        ? $scoredAnswers > 0
                                        : $scoredAnswers >= $totalQuestions;
                                }
                            }

                            $essayCompletionMap[$submission->user_id][$submission->content_id] = $isCompleted;
                        }
                    }

                    // Transform collection dengan progres yang sudah dihitung
                    $participantsPaginator->setCollection(
                        $participantsPaginator->getCollection()->transform(function ($user) use (
                            $allContents,
                            $totalContents,
                            $completedContentMap,
                            $quizPassedMap,
                            $essayCompletionMap
                        ) {
                            $completed = 0;

                            foreach ($allContents as $content) {
                                $isCompleted = false;

                                if ($content->type === 'quiz' && $content->quiz_id) {
                                    $quizPassed = $quizPassedMap[$user->id] ?? [];
                                    $isCompleted = isset($quizPassed[$content->quiz_id]);
                                } elseif ($content->type === 'essay') {
                                    $isCompleted = $essayCompletionMap[$user->id][$content->id] ?? false;
                                } else {
                                    $completedContents = $completedContentMap[$user->id] ?? [];
                                    $isCompleted = isset($completedContents[$content->id]);
                                }

                                if ($isCompleted) {
                                    $completed++;
                                }
                            }

                            $percentage = $totalContents > 0 ? round(($completed / $totalContents) * 100, 2) : 0;

                            return [
                                'user' => $user,
                                'progress' => [
                                    'progress_percentage' => $percentage,
                                    'completed_count' => $completed,
                                    'total_count' => $totalContents,
                                ],
                            ];
                        })
                    );
                }

                $participants = $participantsPaginator;
            }
        }

        $totalParticipants = ($participants instanceof \Illuminate\Pagination\LengthAwarePaginator)
            ? $participants->total()
            : ($participants ? $participants->count() : 0);

        return view('admin.force-complete.index', compact('courses', 'selectedCourse', 'participants', 'perPage', 'totalParticipants'));
    }

    /**
     * Force-complete all contents for a single participant in a course
     */
    public function processForceComplete(Request $request)
    {
        $this->authorize('update', Course::class);

        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'user_id' => 'required|exists:users,id',
            'generate_certificate' => 'nullable|boolean',
        ]);

        $course = Course::findOrFail($request->course_id);
        $user = User::findOrFail($request->user_id);

        try {
            DB::transaction(function () use ($course, $user) {
                $this->forceCompleteUserInCourse($user, $course);
            });

            // Optionally generate certificate
            if ($request->boolean('generate_certificate')) {
                CertificateController::generateForUser($course, $user);
            }

            return redirect()->back()->with('success', 'Semua konten untuk peserta ' . $user->name . ' telah ditandai selesai.');
        } catch (\Exception $e) {
            Log::error('Force complete error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat force complete: ' . $e->getMessage());
        }
    }

    /**
     * Force-complete all contents for all participants in a course
     */
    public function processForceCompleteAll(Request $request)
    {
        $this->authorize('update', Course::class);

        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'generate_certificate' => 'nullable|boolean',
        ]);

        $course = Course::findOrFail($request->course_id);

        try {
            DB::transaction(function () use ($course) {
                foreach ($course->enrolledUsers as $participant) {
                    $this->forceCompleteUserInCourse($participant, $course);
                }
            });

            if ($request->boolean('generate_certificate')) {
                foreach ($course->enrolledUsers as $participant) {
                    CertificateController::generateForUser($course, $participant);
                }
            }

            return redirect()->back()->with('success', 'Semua peserta pada kursus ' . $course->title . ' telah ditandai selesai.');
        } catch (\Exception $e) {
            Log::error('Force complete all error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat force complete massal: ' . $e->getMessage());
        }
    }

    /**
     * Bulk force complete selected participants (with queue)
     */
    public function bulkForceComplete(Request $request)
    {
        $this->authorize('update', Course::class);

        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'generate_certificate' => 'nullable|boolean',
            'use_queue' => 'nullable|boolean',
        ]);

        $course = Course::findOrFail($request->course_id);
        $userIds = $request->user_ids;
        $generateCertificate = $request->boolean('generate_certificate');
        $useQueue = $request->boolean('use_queue', true); // Default to queue

        // If more than 50 users or use_queue is true, use job queue
        if ($useQueue || count($userIds) > 50) {
            $batchId = uniqid('bulk_fc_', true);

            // Split into chunks of 50 users per job
            $chunks = array_chunk($userIds, 50);

            foreach ($chunks as $chunk) {
                BulkForceCompleteJob::dispatch($course, $chunk, $generateCertificate, $batchId);
            }

            Log::info("Bulk force complete queued", [
                'batch_id' => $batchId,
                'course_id' => $course->id,
                'total_users' => count($userIds),
                'jobs_created' => count($chunks)
            ]);

            return redirect()->back()->with('success',
                'Proses force complete untuk ' . count($userIds) . ' peserta telah dijadwalkan. ' .
                'Prosesnya akan berjalan di background. Silakan cek log untuk progress. Batch ID: ' . $batchId
            );
        }

        // Process immediately for small batches
        try {
            $processed = 0;
            foreach ($userIds as $userId) {
                $user = User::find($userId);
                if ($user) {
                    DB::transaction(function () use ($user, $course) {
                        $this->forceCompleteUserInCourse($user, $course);
                    });

                    if ($generateCertificate) {
                        CertificateController::generateForUser($course, $user);
                    }
                    $processed++;
                }
            }

            return redirect()->back()->with('success',
                'Berhasil force complete ' . $processed . ' peserta dari ' . count($userIds) . ' yang dipilih.'
            );
        } catch (\Exception $e) {
            Log::error('Bulk force complete error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Bulk generate certificates for selected participants (with queue)
     */
    public function bulkGenerateCertificates(Request $request)
    {
        $this->authorize('update', Course::class);

        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'use_queue' => 'nullable|boolean',
        ]);

        $course = Course::findOrFail($request->course_id);
        $userIds = $request->user_ids;
        $useQueue = $request->boolean('use_queue', true);

        if (!$course->certificate_template_id) {
            return redirect()->back()->with('error', 'Kursus belum memiliki template sertifikat.');
        }

        // If more than 50 users or use_queue is true, use job queue
        if ($useQueue || count($userIds) > 50) {
            $batchId = uniqid('bulk_cert_', true);

            // Split into chunks of 50 users per job
            $chunks = array_chunk($userIds, 50);

            foreach ($chunks as $chunk) {
                BulkGenerateCertificatesJob::dispatch($course, $chunk, $batchId);
            }

            Log::info("Bulk certificate generation queued", [
                'batch_id' => $batchId,
                'course_id' => $course->id,
                'total_users' => count($userIds),
                'jobs_created' => count($chunks)
            ]);

            return redirect()->back()->with('success',
                'Proses generate sertifikat untuk ' . count($userIds) . ' peserta telah dijadwalkan. ' .
                'Prosesnya akan berjalan di background. Batch ID: ' . $batchId
            );
        }

        // Process immediately for small batches
        try {
            $generated = 0;
            foreach ($userIds as $userId) {
                $user = User::find($userId);
                if ($user) {
                    $certificate = CertificateController::generateForUser($course, $user);
                    if ($certificate) {
                        $generated++;
                    }
                }
            }

            return redirect()->back()->with('success',
                'Berhasil generate ' . $generated . ' sertifikat dari ' . count($userIds) . ' peserta yang dipilih.'
            );
        } catch (\Exception $e) {
            Log::error('Bulk certificate generation error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Core logic: mark all contents completed for a user in a course
     */
    private function forceCompleteUserInCourse(User $user, Course $course): void
    {
        $lessons = $course->lessons()->with(['contents.quiz', 'contents.essayQuestions'])->get();

        foreach ($lessons as $lesson) {
            foreach ($lesson->contents as $content) {
                $this->completeContentForUser($user, $content);
            }
        }
    }

    private function completeContentForUser(User $user, Content $content): void
    {
        switch ($content->type) {
            case 'quiz':
                $this->forcePassQuiz($user, $content);
                break;
            case 'essay':
                $this->forceCompleteEssay($user, $content);
                break;
            default:
                // Mark generic content as completed via pivot
                $user->completedContents()->syncWithoutDetaching([
                    $content->id => [
                        'completed' => true,
                        'completed_at' => now(),
                    ],
                ]);
                break;
        }
    }

    private function forcePassQuiz(User $user, Content $content): void
    {
        if (!$content->quiz_id || !$content->quiz) return;

        // If user already has a passed attempt, skip
        $alreadyPassed = $user->quizAttempts()
            ->where('quiz_id', $content->quiz_id)
            ->where('passed', true)
            ->exists();

        if ($alreadyPassed) return;

        $quiz = $content->quiz;

        // ✅ FIX: Hitung pass_marks dari passing_percentage
        // ⚠️ CRITICAL FIX: Load questions jika belum ter-load
        if (!$quiz->relationLoaded('questions')) {
            $quiz->load('questions');
        }

        $totalMarks = $quiz->questions->sum('marks') ?: 100;
        $passingMarks = ($totalMarks * ($quiz->passing_percentage ?? 70)) / 100;

        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $user->id,
            'score' => max($passingMarks, 0),
            'passed' => true,
            'started_at' => now(),
            'completed_at' => now(),
        ]);
    }

    private function forceCompleteEssay(User $user, Content $content): void
    {
        // Ensure submission exists
        $submission = EssaySubmission::firstOrCreate([
            'user_id' => $user->id,
            'content_id' => $content->id,
        ]);

        // Ensure answers exist (at least one). Create per active question if any.
        $questions = $content->essayQuestions()->get();

        if ($questions->count() > 0) {
            foreach ($questions as $question) {
                EssayAnswer::firstOrCreate([
                    'submission_id' => $submission->id,
                    'question_id' => $question->id,
                ], [
                    'answer' => 'Completed offline due to incident',
                ]);
            }
        } else {
            // Legacy model without questions: create a single placeholder answer if none exists
            if ($submission->answers()->count() === 0) {
                EssayAnswer::create([
                    'submission_id' => $submission->id,
                    'question_id' => null,
                    'answer' => 'Completed offline due to incident',
                ]);
            }
        }

        // Apply grading completion similar to AutoGradeController
        if ($content->requires_review === false) {
            // No review needed: just mark graded_at and status
            $submission->update([
                'graded_at' => now(),
                'status' => 'reviewed',
            ]);
            return;
        }

        if ($content->scoring_enabled) {
            if ($content->grading_mode === 'overall') {
                $firstAnswer = $submission->answers()->first();
                if ($firstAnswer) {
                    $firstAnswer->update([
                        'score' => $firstAnswer->score ?? 0,
                        'feedback' => $firstAnswer->feedback ?? 'Force completed due to incident',
                    ]);
                }
            } else {
                foreach ($submission->answers as $answer) {
                    if ($answer->score === null) {
                        $answer->update([
                            'score' => 0,
                            'feedback' => $answer->feedback ?? 'Force completed due to incident',
                        ]);
                    }
                }
            }
        } else {
            if ($content->grading_mode === 'overall') {
                $firstAnswer = $submission->answers()->first();
                if ($firstAnswer && empty($firstAnswer->feedback)) {
                    $firstAnswer->update([
                        'feedback' => 'Force completed due to incident',
                    ]);
                }
            } else {
                foreach ($submission->answers as $answer) {
                    if (empty($answer->feedback)) {
                        $answer->update([
                            'feedback' => 'Force completed due to incident',
                        ]);
                    }
                }
            }
        }

        $submission->update([
            'graded_at' => now(),
            'status' => $content->scoring_enabled ? 'graded' : 'reviewed',
        ]);

        // Also mark the content as completed in pivot for consistency
        $user->completedContents()->syncWithoutDetaching([
            $content->id => [
                'completed' => true,
                'completed_at' => now(),
            ],
        ]);
    }
}
