<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course; // Memanggil kerangka tabel Course
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourseApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Course::query();

        if ($user->can('manage all courses')) {
            // show all courses
        } elseif ($user->can('manage own courses')) {
            $query->whereHas('instructors', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        } elseif ($user->can('view progress reports')) {
            $query->whereHas('eventOrganizers', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        } else {
            $query->where('status', 'published')
                ->whereHas('enrolledUsers', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });

            if (!$user->isAvpnApproved()) {
                $query->where('program_type', '!=', 'avpn_ai');
            }
        }

        $savedIds = $user->savedCourses()->pluck('courses.id')->all();

        $courses = $query->with(['lessons.contents.quiz', 'lessons.contents.images', 'instructors'])
            ->latest()
            ->get();

        $completionCtx = $this->buildCompletionContext($user, $courses);

        $courses = $courses->map(
            fn (Course $course) => $this->transformCourse($course, $user, $savedIds, $completionCtx)
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengambil daftar course beserta section dan lesson',
            'data' => $courses,
        ]);
    }

    /**
     * Daftar kursus yang disimpan (bookmark) oleh user. Khusus mobile.
     */
    public function saved(Request $request)
    {
        $user = $request->user();

        $courses = $user->savedCourses()
            ->with(['lessons.contents.quiz', 'lessons.contents.images', 'instructors'])
            ->latest('saved_courses.created_at')
            ->get();

        $savedIds = $courses->pluck('id')->all();

        $completionCtx = $this->buildCompletionContext($user, $courses);

        $data = $courses->map(
            fn (Course $course) => $this->transformCourse($course, $user, $savedIds, $completionCtx)
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengambil daftar course tersimpan',
            'data' => $data,
        ]);
    }

    /**
     * Toggle simpan/hapus kursus dari koleksi user. Khusus mobile.
     */
    public function toggleSave(Request $request, Course $course)
    {
        $user = $request->user();

        $alreadySaved = $user->savedCourses()->where('course_id', $course->id)->exists();

        if ($alreadySaved) {
            $user->savedCourses()->detach($course->id);
            $isSaved = false;
        } else {
            $user->savedCourses()->syncWithoutDetaching([$course->id]);
            $isSaved = true;
        }

        return response()->json([
            'status' => 'success',
            'message' => $isSaved ? 'Course disimpan ke koleksi' : 'Course dihapus dari koleksi',
            'data' => [
                'course_id' => (string) $course->id,
                'is_saved' => $isSaved,
            ],
        ]);
    }

    /**
     * Bentuk payload satu course (dipakai index & saved) agar konsisten.
     */
    /**
     * Pra-hitung data penyelesaian user untuk SEMUA konten sekaligus (menghindari
     * N+1: tanpa ini, isCompleted dihitung per-konten dengan beberapa query).
     * Mengembalikan set-set lookup yang dipakai oleh isContentCompletedFast().
     */
    private function buildCompletionContext(User $user, $courses): array
    {
        $contentIds = $courses
            ->pluck('lessons')->flatten()
            ->pluck('contents')->flatten()
            ->pluck('id')->filter()->unique()->values()->all();

        if (empty($contentIds)) {
            return [
                'completed' => [],
                'passedQuiz' => [],
                'essay' => [],
                'caseStudy' => [],
                'attendance' => [],
            ];
        }

        $completed = DB::table('content_user')
            ->where('user_id', $user->id)
            ->where('completed', true)
            ->whereIn('content_id', $contentIds)
            ->pluck('content_id')->all();

        $passedQuiz = DB::table('quiz_attempts')
            ->where('user_id', $user->id)
            ->where('passed', true)
            ->distinct()->pluck('quiz_id')->all();

        $essay = DB::table('essay_submissions as s')
            ->join('essay_answers as a', 'a.submission_id', '=', 's.id')
            ->where('s.user_id', $user->id)
            ->whereIn('s.content_id', $contentIds)
            ->distinct()->pluck('s.content_id')->all();

        $caseStudy = DB::table('case_study_submissions')
            ->where('user_id', $user->id)
            ->whereIn('status', ['submitted', 'graded'])
            ->whereIn('content_id', $contentIds)
            ->pluck('content_id')->all();

        $attendance = DB::table('attendances')
            ->where('user_id', $user->id)
            ->whereIn('content_id', $contentIds)
            ->get(['content_id', 'status', 'duration_minutes'])
            ->keyBy('content_id');

        return [
            'completed' => array_flip($completed),
            'passedQuiz' => array_flip($passedQuiz),
            'essay' => array_flip($essay),
            'caseStudy' => array_flip($caseStudy),
            'attendance' => $attendance,
        ];
    }

    /**
     * Versi in-memory dari User::hasCompletedContent (logikanya disamakan persis),
     * memakai konteks dari buildCompletionContext agar tanpa query per-konten.
     */
    private function isContentCompletedFast($content, array $ctx): bool
    {
        $id = $content->id;

        if ($content->is_optional ?? false) {
            return isset($ctx['completed'][$id]);
        }

        if ($content->attendance_required ?? false) {
            $att = $ctx['attendance'][$id] ?? null;
            if (!$att) {
                return false;
            }
            if (!in_array($att->status, ['present', 'excused'], true)) {
                return false;
            }
            if ($content->min_attendance_minutes
                && (int) ($att->duration_minutes ?? 0) < (int) $content->min_attendance_minutes) {
                return false;
            }
        }

        switch ($content->type) {
            case 'quiz':
                return $content->quiz_id && isset($ctx['passedQuiz'][$content->quiz_id]);
            case 'essay':
                return isset($ctx['essay'][$id]);
            case 'case_study':
                return isset($ctx['caseStudy'][$id]);
            default:
                return isset($ctx['completed'][$id]);
        }
    }

    private function transformCourse(Course $course, User $user, array $savedIds, array $completionCtx = []): array
    {
        return [
            'id' => (string) $course->id,
            'title' => $course->title,
            'description' => $course->description ?? '',
            // Kirim SEMUA instruktur (dipisah koma). Mobile akan memecahnya
            // menjadi daftar dan menampilkan masing-masing.
            'instructor' => $course->instructors->pluck('name')->filter()->implode(', '),
            'color' => '#6C5CE7',
            'icon' => '📚',
            'chaptersCount' => $course->lessons->count(),
            'duration' => '0 min',
            'is_saved' => in_array($course->id, $savedIds),
            'sections' => $course->lessons->values()->map(function ($lesson, $index) use ($course, $user) {
                return [
                    'id' => (string) $lesson->id,
                    'courseId' => (string) $course->id,
                    'sectionNumber' => $index + 1,
                    'title' => $lesson->title,
                    'description' => $lesson->description ?? '',
                    'lessons' => $lesson->contents->filter(function ($content) {
                        // Hide draft quizzes from peserta listing
                        if ($content->quiz_id && $content->quiz && $content->quiz->status === 'draft') {
                            return false;
                        }

                        return true;
                    })->map(function ($content) use ($course, $lesson, $user) {
                        // Prefer explicit content.file_path, fallback to first attached document's file_path
                        $filePath = null;
                        if (!empty($content->file_path)) {
                            $filePath = $content->file_path;
                        } else {
                            $firstDoc = $content->documents()->first();
                            if ($firstDoc && !empty($firstDoc->file_path)) {
                                $filePath = $firstDoc->file_path;
                            }
                        }

                        // Return the public storage URL so the mobile PDF viewer can fetch it without auth.
                        $documentUrl = $filePath ? asset('storage/' . $filePath) : null;

                        return [
                            'id' => (string) $content->id,
                            'courseId' => (string) $course->id,
                            'title' => $content->title,
                            'content' => in_array($content->type, ['video', 'text', 'document'])
                                ? ($content->body ?? '')
                                : ($content->description ?? ''),
                            // Template case_study bisa sangat besar; jangan kirim di
                            // daftar course (mobile memuatnya via endpoint by-lesson).
                            'body' => $content->type === 'case_study'
                                ? ''
                                : ($content->body ?? ''),
                            'duration' => '0 min',
                            'type' => $content->type ?? 'text',
                            'quizId' => $content->quiz_id ? (string) $content->quiz_id : null,
                            'youtubeVideoId' => $content->youtube_video_id,
                            'filePath' => $documentUrl,
                            'documentUrl' => $documentUrl,
                            'documentAccessType' => $content->document_access_type,
                            'allowAnswerDownload' => (bool) ($content->allow_answer_download ?? false),
                            'isCompleted' => $user ? $this->isContentCompletedFast($content, $completionCtx) : false,
                            'zoomLink' => $content->type === 'zoom'
                                ? (json_decode($content->body ?? '{}', true)['link'] ?? null)
                                : null,
                            'zoomMeetingId' => $content->type === 'zoom'
                                ? (json_decode($content->body ?? '{}', true)['meeting_id'] ?? null)
                                : null,
                            'zoomPassword' => $content->type === 'zoom'
                                ? (json_decode($content->body ?? '{}', true)['password'] ?? null)
                                : null,
                            'scheduledStart' => $content->scheduled_start?->toISOString(),
                            'scheduledEnd' => $content->scheduled_end?->toISOString(),
                            'imageUrls' => $content->type === 'image'
                                ? $content->images->sortBy('order')->map(function ($img) {
                                    return asset('storage/' . $img->file_path);
                                })->values()->toArray()
                                : [],
                            'lessonId' => (string) $lesson->id,
                        ];
                    })->values(),
                ];
            })->values(),
        ];
    }
}
