<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CourseApiController;
use App\Http\Controllers\Api\EssayApiController;
use App\Http\Controllers\Api\QuizApiController;

Route::get('/mobile/courses', [CourseApiController::class, 'index']);
// Quiz endpoints (mobile expects content.id as lessonId)
Route::get('/mobile/quizzes/by-lesson/{contentId}', [QuizApiController::class, 'getByLesson']);
Route::post('/mobile/quizzes/{quiz}/attempts', [QuizApiController::class, 'startAttempt']);
Route::post('/mobile/quizzes/{quiz}/attempts/{attempt}/submit', [QuizApiController::class, 'submitAttempt']);

Route::get('/mobile/essays/by-lesson/{content}', [EssayApiController::class, 'getByLesson']);
Route::post('/mobile/essays/{content}/submit', [EssayApiController::class, 'submit']);
