<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\EnrollmentApiController;
use App\Http\Controllers\Api\CourseApiController;
use App\Http\Controllers\Api\CourseResultsApiController;
use App\Http\Controllers\Api\EssayApiController;
use App\Http\Controllers\Api\LessonProgressApiController;
use App\Http\Controllers\Api\QuizApiController;
use App\Http\Controllers\Api\DocumentController;

Route::post('/mobile/auth/login', [AuthApiController::class, 'login']);
Route::post('/mobile/auth/register', [AuthApiController::class, 'register']);

Route::middleware('mobile.api.user')->group(function () {
    Route::get('/mobile/auth/me', [AuthApiController::class, 'me']);
    Route::post('/mobile/auth/logout', [AuthApiController::class, 'logout']);

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
});
