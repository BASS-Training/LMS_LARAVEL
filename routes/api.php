<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\CourseApiController;
use App\Http\Controllers\Api\EssayApiController;
use App\Http\Controllers\Api\QuizApiController;

Route::post('/mobile/auth/login', [AuthApiController::class, 'login']);
Route::post('/mobile/auth/register', [AuthApiController::class, 'register']);
Route::middleware('mobile.api.user')->group(function () {
    Route::get('/mobile/auth/me', [AuthApiController::class, 'me']);
    Route::post('/mobile/auth/logout', [AuthApiController::class, 'logout']);

    Route::post('/mobile/essays/{content}/submit', [EssayApiController::class, 'submit']);
});

Route::get('/mobile/courses', [CourseApiController::class, 'index']);
// Quiz endpoints (mobile expects content.id as lessonId)
Route::get('/mobile/quizzes/by-lesson/{contentId}', [QuizApiController::class, 'getByLesson']);
Route::middleware('mobile.api.user')->group(function () {
    Route::post('/mobile/quizzes/{quiz}/attempts', [QuizApiController::class, 'startAttempt']);
    Route::post('/mobile/quizzes/{quiz}/attempts/{attempt}/submit', [QuizApiController::class, 'submitAttempt']);
});

Route::get('/mobile/essays/by-lesson/{content}', [EssayApiController::class, 'getByLesson']);
