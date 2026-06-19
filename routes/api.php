<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\EnrollmentApiController;
use App\Http\Controllers\Api\CourseApiController;
use App\Http\Controllers\Api\CourseResultsApiController;
use App\Http\Controllers\Api\EssayApiController;
use App\Http\Controllers\Api\CaseStudyApiController;
use App\Http\Controllers\Api\FeedbackApiController;
use App\Http\Controllers\Api\LessonProgressApiController;
use App\Http\Controllers\Api\QuizApiController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\DiscussionApiController;
use App\Http\Controllers\Api\InstructorApiController;
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Controllers\Api\AgendaApiController;
use App\Http\Controllers\Api\ProfileApiController;

Route::post('/mobile/auth/login', [AuthApiController::class, 'login']);
Route::post('/mobile/auth/register', [AuthApiController::class, 'register']);

Route::middleware('mobile.api.user')->group(function () {
    Route::get('/mobile/auth/me', [AuthApiController::class, 'me']);
    Route::post('/mobile/auth/logout', [AuthApiController::class, 'logout']);

    // Update profil (data dasar + foto). POST karena multipart upload file.
    Route::post('/mobile/profile', [ProfileApiController::class, 'update']);

    Route::get('/mobile/courses', [CourseApiController::class, 'index']);
    // Saved courses (bookmark) — mobile-only. Letakkan sebelum route {course}
    // agar segmen literal "saved" tidak tertangkap sebagai parameter course.
    Route::get('/mobile/courses/saved', [CourseApiController::class, 'saved']);
    Route::post('/mobile/courses/{course}/save', [CourseApiController::class, 'toggleSave']);
    Route::get('/documents/{path}', [DocumentController::class, 'show'])->where('path', '.*');
    Route::get('/mobile/courses/{course}/results', [CourseResultsApiController::class, 'index']);
    Route::post('/mobile/enroll', [EnrollmentApiController::class, 'enroll']);
    Route::post('/mobile/lessons/{content}/complete', [LessonProgressApiController::class, 'complete']);

    // Quiz endpoints (mobile expects content.id as lessonId)
    Route::get('/mobile/quizzes/by-lesson/{contentId}', [QuizApiController::class, 'getByLesson']);

    Route::post('/mobile/quizzes/{quiz}/attempts', [QuizApiController::class, 'startAttempt']);
    Route::post('/mobile/quizzes/{quiz}/attempts/{attempt}/submit', [QuizApiController::class, 'submitAttempt']);

    Route::post('/mobile/essays/{content}/submit', [EssayApiController::class, 'submit']);
    Route::post('/mobile/essays/{content}/draft', [EssayApiController::class, 'autosave']);

    Route::get('/mobile/essays/by-lesson/{content}', [EssayApiController::class, 'getByLesson']);

    // Studi Kasus (case_study)
    Route::get('/mobile/case-studies/by-lesson/{content}', [CaseStudyApiController::class, 'getByLesson']);
    Route::post('/mobile/case-studies/{content}/submit', [CaseStudyApiController::class, 'submit']);
    Route::post('/mobile/case-studies/{content}/draft', [CaseStudyApiController::class, 'autosave']);
    Route::get('/mobile/case-studies/{content}/download', [CaseStudyApiController::class, 'download']);

    // Feedback (form survei, tanpa penilaian)
    Route::get('/mobile/feedback/by-lesson/{content}', [FeedbackApiController::class, 'getByLesson']);
    Route::post('/mobile/feedback/{content}/submit', [FeedbackApiController::class, 'submit']);

    // Discussions (topic + replies) — shared tables with the web, so posts sync both ways.
    // Feed agregat lintas course (hub diskusi mobile) — letakkan sebelum route param.
    Route::get('/mobile/discussions', [DiscussionApiController::class, 'feed']);
    Route::get('/mobile/discussions/structure', [DiscussionApiController::class, 'structure']);
    Route::get('/mobile/lessons/{content}/discussions', [DiscussionApiController::class, 'index']);
    Route::post('/mobile/lessons/{content}/discussions', [DiscussionApiController::class, 'store']);
    Route::post('/mobile/discussions/{discussion}/replies', [DiscussionApiController::class, 'storeReply']);

    // Agenda: sesi terjadwal (Zoom) mendatang & berlangsung lintas course.
    Route::get('/mobile/agenda', [AgendaApiController::class, 'index']);

    // Notifikasi (gabungan: notifikasi DB + pengumuman web).
    Route::get('/mobile/notifications', [NotificationApiController::class, 'index']);
    Route::get('/mobile/notifications/unread-count', [NotificationApiController::class, 'unreadCount']);
    Route::post('/mobile/notifications/mark-read', [NotificationApiController::class, 'markRead']);
    Route::post('/mobile/notifications/mark-all-read', [NotificationApiController::class, 'markAllRead']);

    // Instructor / admin (mobile): lihat peserta & progres, dan nilai essay / studi kasus.
    // Menulis ke tabel yang sama dengan web → grading tersinkron dua arah.
    Route::get('/mobile/instructor/dashboard', [InstructorApiController::class, 'dashboard']);
    Route::get('/mobile/instructor/grading-queue', [InstructorApiController::class, 'globalGradingQueue']);
    Route::get('/mobile/courses/{course}/participants', [InstructorApiController::class, 'participants']);
    Route::get('/mobile/courses/{course}/participants/{participant}/progress', [InstructorApiController::class, 'participantProgress']);
    Route::get('/mobile/courses/{course}/grading-queue', [InstructorApiController::class, 'gradingQueue']);
    Route::get('/mobile/essays/submissions/{submission}', [InstructorApiController::class, 'essaySubmission']);
    Route::post('/mobile/essays/submissions/{submission}/grade', [InstructorApiController::class, 'gradeEssay']);
    Route::get('/mobile/case-studies/submissions/{submission}', [InstructorApiController::class, 'caseStudySubmission']);
    Route::post('/mobile/case-studies/submissions/{submission}/grade', [InstructorApiController::class, 'gradeCaseStudy']);
});
